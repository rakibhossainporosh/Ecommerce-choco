<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
            } elseif (! $unit->is_active) {
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
