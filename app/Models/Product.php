<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['name', 'slug', 'short_description', 'description', 'meta_title', 'meta_description', 'is_active', 'is_featured', 'brand_id'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
    ];

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->validateBusinessRules();

            if ($product->exists && $product->isDirty('is_active') && $product->is_active) {
                $product->validateActiveState();
            }
        });

        static::restoring(function (Product $product): void {
            if ($product->is_active) {
                $product->validateActiveState();
            }
        });
    }

    /**
     * Get the brand associated with the product.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the categories assigned to the product.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    /**
     * Get the variants belonging to the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the default variant of the product.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Get the dynamic attribute value assignments for the product.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the distinct attributes assigned to the product.
     *
     * @return BelongsToMany<Attribute, $this>
     */
    public function assignedAttributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute_values')
            ->distinct()
            ->withTimestamps();
    }

    /**
     * Get all media items for the product.
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get ordered media relationship for the product.
     *
     * @return MorphMany<Media, $this>
     */
    public function orderedMedia(): MorphMany
    {
        return $this->media()->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get the primary media for the product.
     */
    public function primaryMedia(): ?Media
    {
        if ($this->relationLoaded('media')) {
            return $this->media->firstWhere('is_primary', true);
        }

        return $this->media()->where('is_primary', true)->first();
    }

    /**
     * Get resolved media gallery for storefront/display.
     *
     * @return Collection<int, Media>
     */
    public function getResolvedMedia(): Collection
    {
        if ($this->relationLoaded('media')) {
            return $this->media->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])->values();
        }

        return $this->orderedMedia()->get();
    }

    /**
     * Get the resolved primary media for storefront/display.
     */
    public function getResolvedPrimaryMedia(): ?Media
    {
        $primary = $this->primaryMedia();

        if ($primary) {
            return $primary;
        }

        // Fallback: media with lowest sort_order
        if ($this->relationLoaded('media')) {
            return $this->media->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])->first();
        }

        return $this->orderedMedia()->first();
    }

    /**
     * Explicitly add a media record to this product.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function addMedia(array $attributes): Media
    {
        return Media::createForOwner($this, $attributes);
    }

    /**
     * Set a specific media record belonging to this product as primary.
     *
     * @throws DomainException
     */
    public function setPrimaryMedia(int|Media $media): Media
    {
        $mediaModel = is_int($media) ? Media::query()->findOrFail($media) : $media;
        $mediaModel->validateOwnership($this);

        return $mediaModel->setPrimary();
    }

    /**
     * Safely delete a media record belonging to this product.
     *
     * NOTE: DB transactions are not filesystem transactions. Physical file cleanup
     * will be handled in a later lifecycle phase.
     *
     * @throws DomainException
     */
    public function deleteMedia(int|Media $media): bool
    {
        $mediaModel = is_int($media) ? Media::query()->findOrFail($media) : $media;
        $mediaModel->validateOwnership($this);

        return $mediaModel->deleteSafely();
    }

    /**
     * Reorder the product's complete media gallery to 1..N sort_order.
     *
     * @param  array<int>  $mediaIds
     *
     * @throws InvalidArgumentException
     */
    public function reorderMedia(array $mediaIds): static
    {
        if (count($mediaIds) !== count(array_unique($mediaIds))) {
            throw new InvalidArgumentException('Duplicate media IDs provided for reordering.');
        }

        return DB::transaction(function () use ($mediaIds): static {
            $currentMedia = $this->media()->lockForUpdate()->get(['id', 'sort_order', 'is_primary']);
            $currentIds = $currentMedia->pluck('id')->all();

            if ($currentMedia->isEmpty()) {
                if (! empty($mediaIds)) {
                    throw new InvalidArgumentException('Cannot reorder media when product has no media records.');
                }

                return $this;
            }

            if (empty($mediaIds)) {
                throw new InvalidArgumentException('Reorder list cannot be empty when product has media records.');
            }

            $requestedIds = array_map('intval', $mediaIds);
            $existingIds = array_map('intval', $currentIds);

            $diffMissing = array_diff($existingIds, $requestedIds);
            $diffExtra = array_diff($requestedIds, $existingIds);

            if (! empty($diffMissing) || ! empty($diffExtra)) {
                throw new InvalidArgumentException('Reorder media list must represent the complete current media set without missing, unknown, or foreign IDs.');
            }

            foreach ($requestedIds as $index => $id) {
                $newSortOrder = $index + 1;
                Media::query()
                    ->where('id', $id)
                    ->where('mediable_type', $this->getMorphClass())
                    ->where('mediable_id', $this->getKey())
                    ->update(['sort_order' => $newSortOrder]);
            }

            return $this;
        });
    }

    /**
     * Update alt_text for a media record belonging to this product.
     *
     * @throws DomainException
     */
    public function updateMediaAltText(int|Media $media, ?string $altText): Media
    {
        $mediaModel = is_int($media) ? Media::query()->findOrFail($media) : $media;
        $mediaModel->validateOwnership($this);

        return $mediaModel->updateAltText($altText);
    }

    /**
     * Validate that all active required product-scope attributes are assigned.
     *
     * @throws ValidationException
     */
    public function validateRequiredAttributes(): void
    {
        $requiredAttributes = Attribute::query()
            ->where('scope', Attribute::SCOPE_PRODUCT)
            ->where('is_active', true)
            ->where('is_required', true)
            ->get();

        $assignedAttributeIds = $this->productAttributeValues()
            ->pluck('attribute_id')
            ->unique()
            ->all();

        foreach ($requiredAttributes as $attribute) {
            if (! in_array($attribute->id, $assignedAttributeIds, true)) {
                throw ValidationException::withMessages([
                    'attributes' => ["Required product attribute '{$attribute->name}' is missing."],
                ]);
            }
        }
    }

    /**
     * Validate the active product state invariants.
     *
     * @throws ValidationException
     */
    public function validateActiveState(): void
    {
        // 1. Must have at least one active, non-deleted variant
        $activeVariants = $this->variants()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        if ($activeVariants->isEmpty()) {
            throw ValidationException::withMessages([
                'is_active' => ['An active product must have at least one active variant.'],
            ]);
        }

        // 2. Must have exactly one active, non-deleted default variant
        $defaultVariants = $activeVariants->where('is_default', true);
        if ($defaultVariants->count() !== 1) {
            throw ValidationException::withMessages([
                'is_active' => ['An active product must have exactly one active default variant.'],
            ]);
        }

        // 3. Must have at least one active, non-deleted category
        $hasActiveCategory = $this->categories()
            ->where('is_active', true)
            ->whereNull('categories.deleted_at')
            ->exists();

        if (! $hasActiveCategory) {
            throw ValidationException::withMessages([
                'categories' => ['An active product must have at least one active category.'],
            ]);
        }

        // 4. If brand is assigned, brand must exist, not be soft-deleted, and be active
        if ($this->brand_id !== null) {
            $brand = Brand::withTrashed()->find($this->brand_id);
            if (! $brand || $brand->trashed() || ! $brand->is_active) {
                throw ValidationException::withMessages([
                    'brand_id' => ['An active product cannot have an invalid, inactive, or soft-deleted brand.'],
                ]);
            }
        }

        // 5. Every active variant must have a valid, active, non-deleted unit
        foreach ($activeVariants as $variant) {
            $unit = Unit::withTrashed()->find($variant->unit_id);
            if (! $unit || $unit->trashed() || ! $unit->is_active) {
                throw ValidationException::withMessages([
                    'unit_id' => ["Variant '{$variant->sku}' has an invalid, inactive, or soft-deleted unit."],
                ]);
            }
        }

        // 6. Required Product-scope Attributes
        $this->validateRequiredAttributes();

        // 7. Required Variant-scope Attributes for all active variants
        foreach ($activeVariants as $variant) {
            $variant->validateRequiredAttributes();
        }
    }

    /**
     * Transition the product to active state.
     *
     * @throws ValidationException
     */
    public function activate(): static
    {
        return DB::transaction(function () {
            $this->is_active = true;
            $this->validateActiveState();
            $this->save();

            return $this;
        });
    }

    /**
     * Transition the product to inactive state.
     */
    public function deactivate(): static
    {
        return DB::transaction(function () {
            $this->is_active = false;
            $this->is_featured = false;
            $this->save();

            return $this;
        });
    }

    /**
     * Synchronize categories for the product.
     *
     * @param  array<int>  $categoryIds
     * @return $this
     *
     * @throws ValidationException
     */
    public function syncCategories(array $categoryIds): static
    {
        return DB::transaction(function () use ($categoryIds) {
            if ($this->is_active && empty($categoryIds)) {
                throw ValidationException::withMessages([
                    'categories' => ['Cannot remove all categories from an active product.'],
                ]);
            }

            if (! empty($categoryIds)) {
                static::validateCategoryAssignment($categoryIds);
            }

            $this->categories()->sync($categoryIds);

            if ($this->is_active) {
                $hasActiveCategory = $this->categories()
                    ->where('is_active', true)
                    ->whereNull('categories.deleted_at')
                    ->exists();

                if (! $hasActiveCategory) {
                    throw ValidationException::withMessages([
                        'categories' => ['An active product must have at least one active category.'],
                    ]);
                }
            }

            return $this;
        });
    }

    /**
     * Attach a single category to the product.
     *
     * @return $this
     *
     * @throws ValidationException
     */
    public function attachCategory(int|Category $category): static
    {
        $categoryId = $category instanceof Category ? $category->id : (int) $category;

        return DB::transaction(function () use ($categoryId) {
            $currentIds = $this->categories()->pluck('categories.id')->all();

            if (in_array($categoryId, $currentIds, true)) {
                throw ValidationException::withMessages([
                    'categories' => ['Category is already assigned to this product.'],
                ]);
            }

            $newIds = array_merge($currentIds, [$categoryId]);
            static::validateCategoryAssignment($newIds);

            $this->categories()->attach($categoryId);

            return $this;
        });
    }

    /**
     * Detach a single category from the product.
     *
     * @return $this
     *
     * @throws ValidationException
     */
    public function detachCategory(int|Category $category): static
    {
        $categoryId = $category instanceof Category ? $category->id : (int) $category;

        return DB::transaction(function () use ($categoryId) {
            $currentIds = $this->categories()->pluck('categories.id')->all();
            $newIds = array_values(array_filter($currentIds, fn ($id) => (int) $id !== (int) $categoryId));

            return $this->syncCategories($newIds);
        });
    }

    /**
     * Synchronize the dynamic attribute assignments for the product.
     *
     * @param  array<mixed>  $assignments
     * @return $this
     *
     * @throws ValidationException
     */
    public function syncAttributes(array $assignments): static
    {
        return DB::transaction(function () use ($assignments) {
            $normalized = $this->normalizeAndValidateAssignmentPayload($assignments, Attribute::SCOPE_PRODUCT);

            $this->productAttributeValues()->delete();

            foreach ($normalized as $item) {
                $this->productAttributeValues()->create($item);
            }

            if ($this->is_active) {
                $this->validateRequiredAttributes();
            }

            return $this;
        });
    }

    /**
     * Alias for syncAttributes.
     *
     * @param  array<mixed>  $assignments
     * @return $this
     *
     * @throws ValidationException
     */
    public function syncAssignedAttributes(array $assignments): static
    {
        return $this->syncAttributes($assignments);
    }

    /**
     * Normalize and validate an attribute assignment payload.
     *
     * @param  array<mixed>  $assignments
     * @return array<int, array{attribute_id: int, attribute_value_id: ?int, text_value: ?string, number_value: mixed, boolean_value: ?bool}>
     *
     * @throws ValidationException
     */
    protected function normalizeAndValidateAssignmentPayload(array $assignments, string $expectedScope): array
    {
        $normalized = [];

        foreach ($assignments as $key => $raw) {
            $attrId = null;
            $entry = [];

            if (is_array($raw)) {
                $attrId = $raw['attribute_id'] ?? (is_numeric($key) && (int) $key > 0 ? (int) $key : null);
                $entry = $raw;
            } else {
                $attrId = is_numeric($key) && (int) $key > 0 ? (int) $key : null;
                $entry = ['value' => $raw];
            }

            if (blank($attrId)) {
                throw ValidationException::withMessages([
                    'attributes' => ['Each attribute assignment must specify an attribute ID.'],
                ]);
            }

            $attribute = Attribute::withTrashed()->find($attrId);
            if (! $attribute) {
                throw ValidationException::withMessages([
                    'attributes' => ["The attribute with ID {$attrId} does not exist."],
                ]);
            }

            if ($attribute->scope !== $expectedScope) {
                $target = $expectedScope === Attribute::SCOPE_PRODUCT ? 'product' : 'variant';
                throw ValidationException::withMessages([
                    'attributes' => ["Cannot assign {$attribute->scope}-scoped attribute '{$attribute->name}' to a {$target}."],
                ]);
            }

            if ($attribute->trashed()) {
                throw ValidationException::withMessages([
                    'attributes' => ["Cannot assign deleted attribute '{$attribute->name}'."],
                ]);
            }

            if (! $attribute->is_active) {
                throw ValidationException::withMessages([
                    'attributes' => ["Cannot assign inactive attribute '{$attribute->name}'."],
                ]);
            }

            if ($attribute->type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
                $valIds = [];
                if (isset($entry['attribute_value_ids']) && is_array($entry['attribute_value_ids'])) {
                    $valIds = $entry['attribute_value_ids'];
                } elseif (isset($entry['values']) && is_array($entry['values'])) {
                    $valIds = $entry['values'];
                } elseif (isset($entry['value']) && is_array($entry['value'])) {
                    $valIds = $entry['value'];
                } elseif (array_key_exists('attribute_value_id', $entry)) {
                    $valIds = [$entry['attribute_value_id']];
                } elseif (isset($entry['value']) && ! is_array($entry['value'])) {
                    $valIds = [$entry['value']];
                }

                $hasMixed = (isset($entry['text_value']) && $entry['text_value'] !== null)
                    || (isset($entry['number_value']) && $entry['number_value'] !== null)
                    || (isset($entry['boolean_value']) && $entry['boolean_value'] !== null);

                if ($hasMixed) {
                    throw ValidationException::withMessages([
                        'attributes' => ["Invalid value storage for multiselect attribute '{$attribute->name}'."],
                    ]);
                }

                foreach ($valIds as $valId) {
                    $normalized[] = [
                        'attribute_id' => (int) $attrId,
                        'attribute_value_id' => $valId !== null ? (int) $valId : null,
                        'text_value' => null,
                        'number_value' => null,
                        'boolean_value' => null,
                    ];
                }

                continue;
            }

            $item = [
                'attribute_id' => (int) $attrId,
                'attribute_value_id' => $entry['attribute_value_id'] ?? null,
                'text_value' => $entry['text_value'] ?? null,
                'number_value' => $entry['number_value'] ?? null,
                'boolean_value' => $entry['boolean_value'] ?? null,
            ];

            if (array_key_exists('value', $entry)
                && ! array_key_exists('text_value', $entry)
                && ! array_key_exists('number_value', $entry)
                && ! array_key_exists('boolean_value', $entry)
                && ! array_key_exists('attribute_value_id', $entry)
            ) {
                $val = $entry['value'];
                switch ($attribute->type) {
                    case Attribute::ATTRIBUTE_TYPE_TEXT:
                    case Attribute::ATTRIBUTE_TYPE_TEXTAREA:
                        $item['text_value'] = $val !== null ? (string) $val : null;
                        break;
                    case Attribute::ATTRIBUTE_TYPE_NUMBER:
                        $item['number_value'] = $val;
                        break;
                    case Attribute::ATTRIBUTE_TYPE_BOOLEAN:
                        $item['boolean_value'] = $val;
                        break;
                    case Attribute::ATTRIBUTE_TYPE_SELECT:
                        $item['attribute_value_id'] = $val !== null ? (int) $val : null;
                        break;
                }
            }

            $storageCount = 0;
            if ($item['text_value'] !== null) {
                $storageCount++;
            }
            if ($item['number_value'] !== null) {
                $storageCount++;
            }
            if ($item['boolean_value'] !== null) {
                $storageCount++;
            }
            if ($item['attribute_value_id'] !== null) {
                $storageCount++;
            }

            if ($storageCount > 1) {
                throw ValidationException::withMessages([
                    'attributes' => ["Invalid value storage for attribute '{$attribute->name}'."],
                ]);
            }

            $normalized[] = $item;
        }

        $groupedByAttr = [];
        foreach ($normalized as $item) {
            $groupedByAttr[$item['attribute_id']][] = $item;
        }

        foreach ($groupedByAttr as $attrId => $items) {
            $attribute = Attribute::withTrashed()->find($attrId);

            if ($attribute->type !== Attribute::ATTRIBUTE_TYPE_MULTISELECT && count($items) > 1) {
                throw ValidationException::withMessages([
                    'attributes' => ["Attribute '{$attribute->name}' can only have one value assigned."],
                ]);
            }

            if ($attribute->type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
                $valIds = array_column($items, 'attribute_value_id');
                if (count($valIds) !== count(array_unique($valIds))) {
                    throw ValidationException::withMessages([
                        'attributes' => ["Duplicate value for multiselect attribute '{$attribute->name}'."],
                    ]);
                }
            }

            foreach ($items as $item) {
                switch ($attribute->type) {
                    case Attribute::ATTRIBUTE_TYPE_TEXT:
                    case Attribute::ATTRIBUTE_TYPE_TEXTAREA:
                        if ($item['text_value'] === null || trim((string) $item['text_value']) === '') {
                            throw ValidationException::withMessages([
                                'attributes' => ["The text value is required for '{$attribute->name}'."],
                            ]);
                        }
                        if ($item['attribute_value_id'] !== null || $item['number_value'] !== null || $item['boolean_value'] !== null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Invalid value storage for text attribute '{$attribute->name}'."],
                            ]);
                        }
                        break;

                    case Attribute::ATTRIBUTE_TYPE_NUMBER:
                        if ($item['number_value'] === null || ! is_numeric($item['number_value'])) {
                            throw ValidationException::withMessages([
                                'attributes' => ["The number value is required and must be numeric for '{$attribute->name}'."],
                            ]);
                        }
                        if ($item['attribute_value_id'] !== null || $item['text_value'] !== null || $item['boolean_value'] !== null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Invalid value storage for number attribute '{$attribute->name}'."],
                            ]);
                        }
                        break;

                    case Attribute::ATTRIBUTE_TYPE_BOOLEAN:
                        if ($item['boolean_value'] === null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["The boolean value is required for '{$attribute->name}'."],
                            ]);
                        }
                        if ($item['attribute_value_id'] !== null || $item['text_value'] !== null || $item['number_value'] !== null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Invalid value storage for boolean attribute '{$attribute->name}'."],
                            ]);
                        }
                        break;

                    case Attribute::ATTRIBUTE_TYPE_SELECT:
                    case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                        if ($item['attribute_value_id'] === null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["The attribute value is required for '{$attribute->name}'."],
                            ]);
                        }
                        if ($item['text_value'] !== null || $item['number_value'] !== null || $item['boolean_value'] !== null) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Invalid value storage for select attribute '{$attribute->name}'."],
                            ]);
                        }

                        $attrValue = AttributeValue::withTrashed()->find($item['attribute_value_id']);
                        if (! $attrValue) {
                            throw ValidationException::withMessages([
                                'attributes' => ['The selected attribute value does not exist.'],
                            ]);
                        }
                        if ((int) $attrValue->attribute_id !== (int) $attribute->id) {
                            throw ValidationException::withMessages([
                                'attributes' => ["The selected attribute value '{$attrValue->name}' does not belong to attribute '{$attribute->name}'."],
                            ]);
                        }
                        if ($attrValue->trashed()) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Cannot assign deleted attribute value '{$attrValue->name}'."],
                            ]);
                        }
                        if (! $attrValue->is_active) {
                            throw ValidationException::withMessages([
                                'attributes' => ["Cannot assign inactive attribute value '{$attrValue->name}'."],
                            ]);
                        }
                        break;
                }
            }
        }

        if ($this->is_active) {
            $requiredAttributes = Attribute::query()
                ->where('scope', $expectedScope)
                ->where('is_active', true)
                ->where('is_required', true)
                ->get();

            $submittedAttrIds = array_unique(array_column($normalized, 'attribute_id'));

            foreach ($requiredAttributes as $reqAttr) {
                if (! in_array($reqAttr->id, $submittedAttrIds, true)) {
                    $scopeName = $expectedScope === Attribute::SCOPE_PRODUCT ? 'product' : 'variant';
                    throw ValidationException::withMessages([
                        'attributes' => ["Required {$scopeName} attribute '{$reqAttr->name}' is missing."],
                    ]);
                }
            }
        }

        return $normalized;
    }

    /**
     * Atomically create a product with its required first default variant.
     *
     * @param  array<string, mixed>  $productAttributes
     * @param  array<string, mixed>  $variantAttributes
     * @param  array<int>  $categoryIds
     *
     * @throws ValidationException
     */
    public static function createWithDefaultVariant(array $productAttributes, array $variantAttributes, array $categoryIds = []): static
    {
        return DB::transaction(function () use ($productAttributes, $variantAttributes, $categoryIds) {
            if (! empty($categoryIds)) {
                static::validateCategoryAssignment($categoryIds);
            }

            $product = static::create($productAttributes);

            if (! empty($categoryIds)) {
                $product->categories()->sync($categoryIds);
            }

            $variantAttributes['product_id'] = $product->id;
            $variantAttributes['is_default'] = true;
            if (! array_key_exists('is_active', $variantAttributes)) {
                $variantAttributes['is_active'] = true;
            }

            $product->variants()->create($variantAttributes);

            return $product;
        });
    }

    /**
     * Validate business rules for product creation or modification.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        if ($this->name !== null) {
            $this->name = trim($this->name);
        }

        if ($this->slug !== null) {
            $this->slug = strtolower(trim($this->slug));
        }

        $errors = [];

        // 1. Name is required
        if (blank($this->name)) {
            $errors['name'] = ['The product name is required.'];
        }

        // 2. Slug is required and must be globally unique across ALL products (including soft-deleted)
        if (blank($this->slug)) {
            $errors['slug'] = ['The product slug is required.'];
        } else {
            $duplicateSlugQuery = static::withTrashed()
                ->where('slug', $this->slug);

            if ($this->exists) {
                $duplicateSlugQuery->where('id', '!=', $this->id);
            }

            if ($duplicateSlugQuery->exists()) {
                $errors['slug'] = ['A product with this slug already exists.'];
            }

            // Slug format validation (alpha_dash)
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->slug)) {
                $errors['slug'] = ['The slug may only contain letters, numbers, dashes, and underscores.'];
            }
        }

        // 3. Featured constraint: Inactive product cannot be featured
        if (! $this->is_active && $this->is_featured) {
            $errors['is_featured'] = ['An inactive product cannot be featured.'];
        }

        // 4. Brand validation: optional, but if supplied must exist, not be soft-deleted, and be active for new assignments
        if ($this->brand_id !== null) {
            $brand = Brand::withTrashed()->find($this->brand_id);

            if (! $brand) {
                $errors['brand_id'] = ['The selected brand does not exist.'];
            } elseif ($brand->trashed()) {
                $errors['brand_id'] = ['Cannot assign a soft-deleted brand to a product.'];
            } elseif (! $brand->is_active && (! $this->exists || $this->isDirty('brand_id'))) {
                $errors['brand_id'] = ['Cannot assign an inactive brand to a product.'];
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate category assignments for domain rules.
     *
     * @param  array<int>  $categoryIds
     *
     * @throws ValidationException
     */
    public static function validateCategoryAssignment(array $categoryIds): void
    {
        if (empty($categoryIds)) {
            throw ValidationException::withMessages([
                'categories' => ['A product must have at least one category.'],
            ]);
        }

        // Reject duplicate category IDs in the request
        if (count($categoryIds) !== count(array_unique($categoryIds))) {
            throw ValidationException::withMessages([
                'categories' => ['Duplicate category assignments are not allowed.'],
            ]);
        }

        $categories = Category::withTrashed()
            ->whereIn('id', $categoryIds)
            ->get();

        if ($categories->count() !== count($categoryIds)) {
            throw ValidationException::withMessages([
                'categories' => ['One or more assigned categories do not exist.'],
            ]);
        }

        foreach ($categories as $category) {
            if ($category->trashed()) {
                throw ValidationException::withMessages([
                    'categories' => ['Cannot assign a soft-deleted category to a product.'],
                ]);
            }

            if (! $category->is_active) {
                throw ValidationException::withMessages([
                    'categories' => ['Cannot assign an inactive category to a product.'],
                ]);
            }
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'brand_id' => 'integer',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
