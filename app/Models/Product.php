<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'slug', 'short_description', 'description', 'meta_title', 'meta_description', 'is_active', 'is_featured', 'brand_id'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->validateBusinessRules();
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

        // 3. Brand validation: optional, but if supplied must exist, not be soft-deleted, and be active
        if ($this->brand_id !== null) {
            $brand = Brand::withTrashed()->find($this->brand_id);

            if (! $brand) {
                $errors['brand_id'] = ['The selected brand does not exist.'];
            } elseif ($brand->trashed()) {
                $errors['brand_id'] = ['Cannot assign a soft-deleted brand to a product.'];
            } elseif (! $brand->is_active) {
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
}
