<?php

namespace App\Models;

use Database\Factories\VariantAttributeValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'product_variant_id',
    'attribute_id',
    'attribute_value_id',
    'text_value',
    'number_value',
    'boolean_value',
])]
class VariantAttributeValue extends Model
{
    /** @use HasFactory<VariantAttributeValueFactory> */
    use HasFactory;

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (VariantAttributeValue $assignment): void {
            $assignment->validateBusinessRules();
        });
    }

    /**
     * Get the product variant associated with this attribute assignment.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the attribute associated with this assignment.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Get the predefined attribute value (for select and multiselect types).
     *
     * @return BelongsTo<AttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    /**
     * Validate business rules for variant attribute assignment.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        $errors = [];

        // 1. Variant validation
        if (blank($this->product_variant_id)) {
            $errors['product_variant_id'] = ['The product variant is required.'];
        } else {
            $variant = ProductVariant::withTrashed()->find($this->product_variant_id);
            if (! $variant) {
                $errors['product_variant_id'] = ['The selected product variant does not exist.'];
            } elseif ($variant->trashed()) {
                $errors['product_variant_id'] = ['Cannot assign attributes to a deleted product variant.'];
            }
        }

        // 2. Attribute validation
        $attribute = null;
        if (blank($this->attribute_id)) {
            $errors['attribute_id'] = ['The attribute is required.'];
        } else {
            $attribute = Attribute::withTrashed()->find($this->attribute_id);
            if (! $attribute) {
                $errors['attribute_id'] = ['The selected attribute does not exist.'];
            } elseif ((! $this->exists || $this->isDirty('attribute_id')) && $attribute->trashed()) {
                $errors['attribute_id'] = ['Cannot assign a deleted attribute.'];
            } elseif ((! $this->exists || $this->isDirty('attribute_id')) && ! $attribute->is_active) {
                $errors['attribute_id'] = ['Cannot assign an inactive attribute.'];
            }
        }

        // If attribute or variant does not exist, throw accumulated errors immediately
        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // 3. Scope validation: Variant assignment requires variant scope
        if ($attribute->scope !== Attribute::SCOPE_VARIANT) {
            $errors['attribute_id'] = ['Cannot assign a product-scoped attribute to a variant.'];
            throw ValidationException::withMessages($errors);
        }

        // 4. Type & Storage validation
        switch ($attribute->type) {
            case Attribute::ATTRIBUTE_TYPE_TEXT:
            case Attribute::ATTRIBUTE_TYPE_TEXTAREA:
                if ($this->text_value === null || trim((string) $this->text_value) === '') {
                    $errors['text_value'] = ['The text value is required.'];
                }
                if ($this->attribute_value_id !== null || $this->number_value !== null || $this->boolean_value !== null) {
                    $errors['text_value'] = ['Invalid value storage for text attribute.'];
                }
                break;

            case Attribute::ATTRIBUTE_TYPE_NUMBER:
                if ($this->number_value === null || ! is_numeric($this->number_value)) {
                    $errors['number_value'] = ['The number value is required and must be numeric.'];
                }
                if ($this->attribute_value_id !== null || $this->text_value !== null || $this->boolean_value !== null) {
                    $errors['number_value'] = ['Invalid value storage for number attribute.'];
                }
                break;

            case Attribute::ATTRIBUTE_TYPE_BOOLEAN:
                if ($this->boolean_value === null) {
                    $errors['boolean_value'] = ['The boolean value is required.'];
                }
                if ($this->attribute_value_id !== null || $this->text_value !== null || $this->number_value !== null) {
                    $errors['boolean_value'] = ['Invalid value storage for boolean attribute.'];
                }
                break;

            case Attribute::ATTRIBUTE_TYPE_SELECT:
            case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                if ($this->attribute_value_id === null) {
                    $errors['attribute_value_id'] = ['The attribute value is required.'];
                } else {
                    $attrValue = AttributeValue::withTrashed()->find($this->attribute_value_id);
                    if (! $attrValue) {
                        $errors['attribute_value_id'] = ['The selected attribute value does not exist.'];
                    } elseif ((int) $attrValue->attribute_id !== (int) $this->attribute_id) {
                        $errors['attribute_value_id'] = ['The selected attribute value does not belong to this attribute.'];
                    } elseif ((! $this->exists || $this->isDirty('attribute_value_id')) && $attrValue->trashed()) {
                        $errors['attribute_value_id'] = ['Cannot assign a deleted attribute value.'];
                    } elseif ((! $this->exists || $this->isDirty('attribute_value_id')) && ! $attrValue->is_active) {
                        $errors['attribute_value_id'] = ['Cannot assign an inactive attribute value.'];
                    }
                }
                if ($this->text_value !== null || $this->number_value !== null || $this->boolean_value !== null) {
                    $errors['attribute_value_id'] = ['Invalid value storage for select attribute.'];
                }
                break;
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // 5. Duplicate assignment prevention
        if ($attribute->type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
            $duplicateQuery = static::where('product_variant_id', $this->product_variant_id)
                ->where('attribute_id', $this->attribute_id)
                ->where('attribute_value_id', $this->attribute_value_id);

            if ($this->exists) {
                $duplicateQuery->where('id', '!=', $this->id);
            }

            if ($duplicateQuery->exists()) {
                $errors['attribute_value_id'] = ['This attribute value has already been assigned to the variant.'];
            }
        } else {
            $duplicateQuery = static::where('product_variant_id', $this->product_variant_id)
                ->where('attribute_id', $this->attribute_id);

            if ($this->exists) {
                $duplicateQuery->where('id', '!=', $this->id);
            }

            if ($duplicateQuery->exists()) {
                $errors['attribute_id'] = ['This attribute has already been assigned to the variant.'];
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
            'product_variant_id' => 'integer',
            'attribute_id' => 'integer',
            'attribute_value_id' => 'integer',
            'number_value' => 'decimal:4',
            'boolean_value' => 'boolean',
        ];
    }
}
