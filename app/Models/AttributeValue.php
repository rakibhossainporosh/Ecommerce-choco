<?php

namespace App\Models;

use Database\Factories\AttributeValueFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'attribute_id',
    'name',
    'slug',
    'is_active',
    'sort_order',
])]
class AttributeValue extends Model
{
    /** @use HasFactory<AttributeValueFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (AttributeValue $value): void {
            $value->validateBusinessRules();
        });

        static::deleting(function (AttributeValue $value): void {
            if ($value->isForceDeleting() && $value->hasAssignments()) {
                throw new DomainException('Cannot force-delete this attribute value because it has associated product or variant assignments.');
            }
        });
    }

    /**
     * Get the attribute that owns the value.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Get the product attribute assignments for this attribute value.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the variant attribute assignments for this attribute value.
     *
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class);
    }

    /**
     * Determine whether this attribute value has product or variant assignments.
     */
    public function hasAssignments(): bool
    {
        return $this->productAttributeValues()->exists()
            || $this->variantAttributeValues()->exists();
    }

    /**
     * Determine whether the attribute value is selectable for assignment.
     */
    public function isSelectable(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    /**
     * Validate business rules for attribute value creation or modification.
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

        // 1. Attribute existence and soft-delete validation
        if (blank($this->attribute_id)) {
            $errors['attribute_id'] = ['The attribute is required.'];
        } else {
            $attribute = Attribute::withTrashed()->find($this->attribute_id);
            if (! $attribute) {
                $errors['attribute_id'] = ['The selected attribute does not exist.'];
            } elseif ($attribute->trashed()) {
                $errors['attribute_id'] = ['Cannot add or update values for a deleted attribute.'];
            }
        }

        // 2. Name is required
        if (blank($this->name)) {
            $errors['name'] = ['The value name is required.'];
        }

        // 3. Slug is required, format alpha_dash, unique per attribute (including soft-deleted values)
        if (blank($this->slug)) {
            $errors['slug'] = ['The value slug is required.'];
        } else {
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->slug)) {
                $errors['slug'] = ['The slug may only contain letters, numbers, dashes, and underscores.'];
            }

            if (! blank($this->attribute_id)) {
                $duplicateSlugQuery = static::withTrashed()
                    ->where('attribute_id', $this->attribute_id)
                    ->where('slug', $this->slug);

                if ($this->exists) {
                    $duplicateSlugQuery->where('id', '!=', $this->id);
                }

                if ($duplicateSlugQuery->exists()) {
                    $errors['slug'] = ['An attribute value with this slug already exists for this attribute.'];
                }
            }
        }

        // 4. Sort order must be non-negative integer
        if ($this->sort_order !== null && (! is_numeric($this->sort_order) || (int) $this->sort_order < 0)) {
            $errors['sort_order'] = ['The sort order must be a non-negative integer.'];
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
            'attribute_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
