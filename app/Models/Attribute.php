<?php

namespace App\Models;

use Database\Factories\AttributeFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name',
    'slug',
    'type',
    'scope',
    'description',
    'is_required',
    'is_active',
    'sort_order',
])]
class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory, SoftDeletes;

    public const ATTRIBUTE_TYPE_TEXT = 'text';

    public const ATTRIBUTE_TYPE_TEXTAREA = 'textarea';

    public const ATTRIBUTE_TYPE_NUMBER = 'number';

    public const ATTRIBUTE_TYPE_BOOLEAN = 'boolean';

    public const ATTRIBUTE_TYPE_SELECT = 'select';

    public const ATTRIBUTE_TYPE_MULTISELECT = 'multiselect';

    public const SCOPE_PRODUCT = 'product';

    public const SCOPE_VARIANT = 'variant';

    public const TYPES = [
        self::ATTRIBUTE_TYPE_TEXT,
        self::ATTRIBUTE_TYPE_TEXTAREA,
        self::ATTRIBUTE_TYPE_NUMBER,
        self::ATTRIBUTE_TYPE_BOOLEAN,
        self::ATTRIBUTE_TYPE_SELECT,
        self::ATTRIBUTE_TYPE_MULTISELECT,
    ];

    public const SCOPES = [
        self::SCOPE_PRODUCT,
        self::SCOPE_VARIANT,
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_required' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Attribute $attribute): void {
            $attribute->validateBusinessRules();
        });

        static::deleting(function (Attribute $attribute): void {
            if (! $attribute->canBeDeleted()) {
                throw new DomainException('Cannot delete this attribute because it has associated attribute values.');
            }

            if ($attribute->isForceDeleting() && $attribute->hasAssignments()) {
                throw new DomainException('Cannot force-delete this attribute because it has associated product or variant assignments.');
            }
        });
    }

    /**
     * Get the values associated with the attribute.
     *
     * @return HasMany<AttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * Get the product attribute assignments for this attribute.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the variant attribute assignments for this attribute.
     *
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class);
    }

    /**
     * Determine whether this attribute has product or variant assignments.
     */
    public function hasAssignments(): bool
    {
        return $this->productAttributeValues()->exists()
            || $this->variantAttributeValues()->exists();
    }

    /**
     * Determine whether this attribute can be safely deleted according to business rules.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->values()->exists();
    }

    /**
     * Determine whether the attribute is selectable for assignment.
     */
    public function isSelectable(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    /**
     * Validate business rules for attribute creation or modification.
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

        // 1. Name is required and unique among non-deleted attributes
        if (blank($this->name)) {
            $errors['name'] = ['The attribute name is required.'];
        } else {
            $duplicateNameQuery = static::query()->where('name', $this->name);
            if ($this->exists) {
                $duplicateNameQuery->where('id', '!=', $this->id);
            }
            if ($duplicateNameQuery->exists()) {
                $errors['name'] = ['An attribute with this name already exists.'];
            }
        }

        // 2. Slug is required, format alpha_dash, globally unique across ALL records (including soft-deleted)
        if (blank($this->slug)) {
            $errors['slug'] = ['The attribute slug is required.'];
        } else {
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->slug)) {
                $errors['slug'] = ['The slug may only contain letters, numbers, dashes, and underscores.'];
            }

            $duplicateSlugQuery = static::withTrashed()->where('slug', $this->slug);
            if ($this->exists) {
                $duplicateSlugQuery->where('id', '!=', $this->id);
            }
            if ($duplicateSlugQuery->exists()) {
                $errors['slug'] = ['An attribute with this slug already exists.'];
            }
        }

        // 3. Type is required and must be one of the six supported types
        if (blank($this->type) || ! in_array($this->type, self::TYPES, true)) {
            $errors['type'] = ['The attribute type is invalid.'];
        }

        // 4. Scope is required and must be product or variant
        if (blank($this->scope) || ! in_array($this->scope, self::SCOPES, true)) {
            $errors['scope'] = ['The attribute scope is invalid.'];
        }

        // 5. Sort order must be non-negative integer
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
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
