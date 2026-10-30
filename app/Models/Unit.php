<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'code', 'description', 'is_active', 'sort_order'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Unit $unit): void {
            $unit->validateBusinessRules();
        });

        static::deleting(function (Unit $unit): void {
            if (! $unit->canBeDeleted()) {
                throw new DomainException('Cannot delete this unit because it is assigned to products.');
            }
        });
    }

    /**
     * Get the product variants using this unit.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Determine whether this unit can be safely deleted according to business rules.
     */
    public function canBeDeleted(): bool
    {
        // Deferred Product dependency check will be added here: && ! $this->hasProducts()
        return true;
    }

    /**
     * Validate business rules for unit creation or modification.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        if ($this->name !== null) {
            $this->name = trim($this->name);
        }

        if ($this->code !== null) {
            $this->code = strtolower(trim($this->code));
        }

        $errors = [];

        // 1. Name uniqueness among non-deleted units
        if ($this->name !== null && $this->name !== '') {
            $duplicateNameQuery = static::query()
                ->where('name', $this->name);

            if ($this->exists) {
                $duplicateNameQuery->where('id', '!=', $this->id);
            }

            if ($duplicateNameQuery->exists()) {
                $errors['name'] = ['A unit with this name already exists.'];
            }
        }

        // 2. Global code uniqueness across all records (including soft-deleted)
        if ($this->code !== null && $this->code !== '') {
            $duplicateCodeQuery = static::withTrashed()
                ->where('code', $this->code);

            if ($this->exists) {
                $duplicateCodeQuery->where('id', '!=', $this->id);
            }

            if ($duplicateCodeQuery->exists()) {
                $errors['code'] = ['A unit with this code already exists.'];
            }

            // 3. Code format validation (alpha_dash: letters, numbers, dashes, underscores)
            if (! preg_match('/^[a-z0-9_-]+$/i', $this->code)) {
                $errors['code'] = ['The code may only contain letters, numbers, dashes, and underscores.'];
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
