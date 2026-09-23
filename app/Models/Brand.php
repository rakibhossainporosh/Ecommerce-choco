<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'slug', 'description', 'logo', 'is_active', 'sort_order'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Brand $brand): void {
            $brand->validateBusinessRules();
        });

        static::deleting(function (Brand $brand): void {
            if (! $brand->canBeDeleted()) {
                throw new DomainException('Cannot delete this brand because it has associated products.');
            }
        });
    }

    /**
     * Determine whether this brand can be safely deleted according to business rules.
     */
    public function canBeDeleted(): bool
    {
        // Deferred Product deletion rule will be added here: && ! $this->hasProducts()
        return true;
    }

    /**
     * Validate business rules for brand creation or modification.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        if ($this->name !== null) {
            $this->name = trim($this->name);
        }

        $errors = [];

        // 1. Name uniqueness among non-deleted brands
        if ($this->name !== null && $this->name !== '') {
            $duplicateNameQuery = static::query()
                ->where('name', $this->name);

            if ($this->exists) {
                $duplicateNameQuery->where('id', '!=', $this->id);
            }

            if ($duplicateNameQuery->exists()) {
                $errors['name'] = ['A brand with this name already exists.'];
            }
        }

        // 2. Global slug uniqueness across all records (including soft-deleted)
        if ($this->slug !== null && $this->slug !== '') {
            $duplicateSlugQuery = static::withTrashed()
                ->where('slug', $this->slug);

            if ($this->exists) {
                $duplicateSlugQuery->where('id', '!=', $this->id);
            }

            if ($duplicateSlugQuery->exists()) {
                $errors['slug'] = ['A brand with this slug already exists.'];
            }

            // 3. Slug format validation (alpha_dash: letters, numbers, dashes, underscores)
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->slug)) {
                $errors['slug'] = ['The slug may only contain letters, numbers, dashes, and underscores.'];
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
