<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'product_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'cost_price',
    'selling_price',
    'compare_at_price',
    'unit_quantity',
    'is_default',
    'is_active',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
    ];

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant): void {
            $variant->validateBusinessRules();
        });

        static::deleting(function (ProductVariant $variant): void {
            // 1. If variant is active, verify that at least one other active, non-deleted variant remains
            if ($variant->is_active) {
                $otherActiveCount = static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveCount === 0) {
                    throw new DomainException('Cannot delete the last remaining active variant of a product.');
                }
            }

            // 2. A default Variant cannot be deleted while another active Variant is not already designated as default.
            if ($variant->is_default) {
                $otherDefaultExists = static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->exists();

                if (! $otherDefaultExists) {
                    throw new DomainException('Cannot delete the default variant without another variant designated as default.');
                }
            }
        });

        static::restoring(function (ProductVariant $variant): void {
            // If restoring a variant marked as default, ensure it is active and no other default variant currently exists
            if ($variant->is_default) {
                if (! $variant->is_active) {
                    throw ValidationException::withMessages([
                        'is_default' => ['Cannot restore an inactive variant as the default variant.'],
                    ]);
                }

                $otherDefaultExists = static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_default', true)
                    ->exists();

                if ($otherDefaultExists) {
                    throw ValidationException::withMessages([
                        'is_default' => ['Cannot restore this variant because another variant is already designated as the default.'],
                    ]);
                }
            }
        });
    }

    /**
     * Get the product that owns the variant.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with the variant.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the dynamic attribute value assignments for the variant.
     *
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class);
    }

    /**
     * Get the distinct attributes assigned to the variant.
     *
     * @return BelongsToMany<Attribute, $this>
     */
    public function assignedAttributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'variant_attribute_values', 'product_variant_id', 'attribute_id')
            ->distinct()
            ->withTimestamps();
    }

    /**
     * Validate that all active required variant-scope attributes are assigned.
     *
     * @throws ValidationException
     */
    public function validateRequiredAttributes(): void
    {
        $requiredAttributes = Attribute::query()
            ->where('scope', Attribute::SCOPE_VARIANT)
            ->where('is_active', true)
            ->where('is_required', true)
            ->get();

        $assignedAttributeIds = $this->variantAttributeValues()
            ->pluck('attribute_id')
            ->unique()
            ->all();

        foreach ($requiredAttributes as $attribute) {
            if (! in_array($attribute->id, $assignedAttributeIds, true)) {
                throw ValidationException::withMessages([
                    'attributes' => ["Required variant attribute '{$attribute->name}' is missing."],
                ]);
            }
        }
    }

    /**
     * Validate the active variant state invariants.
     *
     * @throws ValidationException
     */
    public function validateActiveState(): void
    {
        if ($this->trashed()) {
            throw ValidationException::withMessages([
                'is_active' => ['Cannot activate a soft-deleted variant.'],
            ]);
        }

        $product = Product::withTrashed()->find($this->product_id);
        if (! $product || $product->trashed()) {
            throw ValidationException::withMessages([
                'product_id' => ['The associated product does not exist or is soft-deleted.'],
            ]);
        }

        $unit = Unit::withTrashed()->find($this->unit_id);
        if (! $unit || $unit->trashed() || ! $unit->is_active) {
            throw ValidationException::withMessages([
                'unit_id' => ['Cannot activate a variant with an invalid, inactive, or soft-deleted unit.'],
            ]);
        }

        $this->validateRequiredAttributes();
    }

    /**
     * Transition the variant to active state.
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
     * Transition the variant to inactive state.
     */
    public function deactivate(): static
    {
        return DB::transaction(function () {
            $this->is_active = false;
            $this->save();

            return $this;
        });
    }

    /**
     * Synchronize the dynamic attribute assignments for the variant.
     *
     * @param  array<mixed>  $assignments
     * @return $this
     *
     * @throws ValidationException
     */
    public function syncAttributes(array $assignments): static
    {
        return DB::transaction(function () use ($assignments) {
            $normalized = $this->normalizeAndValidateAssignmentPayload($assignments, Attribute::SCOPE_VARIANT);

            $this->variantAttributeValues()->delete();

            foreach ($normalized as $item) {
                $this->variantAttributeValues()->create($item);
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
     * Validate business rules for product variant creation or modification.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        if ($this->name !== null) {
            $this->name = trim($this->name);
        }

        if ($this->sku !== null) {
            $this->sku = trim($this->sku);
        }

        if ($this->barcode !== null) {
            $this->barcode = trim($this->barcode);
            if ($this->barcode === '') {
                $this->barcode = null;
            }
        }

        $errors = [];

        // 1. SKU: required, format alpha_dash, globally unique across all variants (including soft-deleted)
        if (blank($this->sku)) {
            $errors['sku'] = ['The SKU is required.'];
        } else {
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->sku)) {
                $errors['sku'] = ['The SKU may only contain letters, numbers, dashes, and underscores.'];
            }

            $skuQuery = static::withTrashed()->where('sku', $this->sku);
            if ($this->exists) {
                $skuQuery->where('id', '!=', $this->id);
            }
            if ($skuQuery->exists()) {
                $errors['sku'] = ['A variant with this SKU already exists.'];
            }
        }

        // 2. Barcode: optional, globally unique across all variants (including soft-deleted) if supplied
        if ($this->barcode !== null) {
            $barcodeQuery = static::withTrashed()->where('barcode', $this->barcode);
            if ($this->exists) {
                $barcodeQuery->where('id', '!=', $this->id);
            }
            if ($barcodeQuery->exists()) {
                $errors['barcode'] = ['A variant with this barcode already exists.'];
            }
        }

        // 3. Pricing validation
        if ($this->cost_price === null) {
            $errors['cost_price'] = ['The cost price is required.'];
        } elseif (! is_numeric($this->cost_price) || (float) $this->cost_price < 0) {
            $errors['cost_price'] = ['The cost price must be a non-negative number.'];
        }

        if ($this->selling_price === null) {
            $errors['selling_price'] = ['The selling price is required.'];
        } elseif (! is_numeric($this->selling_price) || (float) $this->selling_price < 0) {
            $errors['selling_price'] = ['The selling price must be a non-negative number.'];
        }

        if ($this->compare_at_price !== null) {
            if (! is_numeric($this->compare_at_price) || (float) $this->compare_at_price < 0) {
                $errors['compare_at_price'] = ['The compare at price must be a non-negative number.'];
            } elseif ($this->selling_price !== null && (float) $this->compare_at_price < (float) $this->selling_price) {
                $errors['compare_at_price'] = ['The compare at price must be greater than or equal to the selling price.'];
            }
        }

        // 4. Unit & Unit Quantity validation
        if ($this->unit_id === null) {
            $errors['unit_id'] = ['The unit is required.'];
        } else {
            $unit = Unit::withTrashed()->find($this->unit_id);
            if (! $unit) {
                $errors['unit_id'] = ['The selected unit does not exist.'];
            } elseif ($unit->trashed()) {
                $errors['unit_id'] = ['Cannot assign a soft-deleted unit to a product variant.'];
            } elseif (! $unit->is_active && (! $this->exists || $this->isDirty('unit_id'))) {
                $errors['unit_id'] = ['Cannot assign an inactive unit to a product variant.'];
            }
        }

        if ($this->unit_quantity === null) {
            $errors['unit_quantity'] = ['The unit quantity is required.'];
        } elseif (! is_numeric($this->unit_quantity) || (float) $this->unit_quantity <= 0) {
            $errors['unit_quantity'] = ['The unit quantity must be greater than zero.'];
        }

        // 5. Default variant and active status validation
        if ($this->product_id !== null) {
            // Invariant 2: An inactive variant cannot be designated as default
            if ($this->is_default && ! $this->is_active) {
                $errors['is_default'] = ['An inactive variant cannot be designated as the default variant.'];
            }

            if ($this->is_default) {
                $duplicateDefaultQuery = static::where('product_id', $this->product_id)
                    ->where('is_default', true);

                if ($this->exists) {
                    $duplicateDefaultQuery->where('id', '!=', $this->id);
                }

                if ($duplicateDefaultQuery->exists()) {
                    $errors['is_default'] = ['A product cannot have more than one default variant.'];
                }
            } elseif ($this->exists && $this->isDirty('is_default')) {
                // If removing default flag from this variant, verify another active default variant exists
                $otherDefaultExists = static::where('product_id', $this->product_id)
                    ->where('id', '!=', $this->id)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->exists();

                if (! $otherDefaultExists) {
                    $errors['is_default'] = ['A product must have exactly one default variant.'];
                }
            }

            // 6. Deactivation guards: cannot deactivate default variant or last active variant
            if ($this->exists && $this->isDirty('is_active') && ! $this->is_active) {
                if ($this->is_default) {
                    $errors['is_active'] = ['Cannot deactivate the default variant. Another active variant must be designated as default first.'];
                }

                $otherActiveCount = static::where('product_id', $this->product_id)
                    ->where('id', '!=', $this->id)
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveCount === 0) {
                    $errors['is_active'] = ['Cannot deactivate the last remaining active variant of a product.'];
                }
            }

            // 7. Transition to active validation
            if ($this->exists && $this->isDirty('is_active') && $this->is_active) {
                $this->validateActiveState();
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
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
            'product_id' => 'integer',
            'unit_id' => 'integer',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'unit_quantity' => 'decimal:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
