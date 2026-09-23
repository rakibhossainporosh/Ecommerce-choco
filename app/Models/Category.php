<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['parent_id', 'name', 'slug', 'description', 'is_active', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Bootstrap model event hooks.
     */
    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $category->validateBusinessRules();
        });

        static::deleting(function (Category $category): void {
            if (! $category->canBeDeleted()) {
                throw new DomainException('Cannot delete this category because it has child categories.');
            }
        });
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Check if this category has any children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category has any active children.
     */
    public function hasActiveChildren(): bool
    {
        return $this->children()->where('is_active', true)->exists();
    }

    /**
     * Determine whether this category can be safely deleted according to business rules.
     */
    public function canBeDeleted(): bool
    {
        // Deferred Product deletion rule will be added here: && ! $this->hasProducts()
        return ! $this->hasChildren();
    }

    /**
     * Get all ancestor IDs of this category in order up to the root.
     *
     * @return array<int>
     */
    public function getAncestorIds(): array
    {
        $ancestorIds = [];
        $currentParentId = $this->parent_id;
        $visited = [];

        while ($currentParentId && ! in_array($currentParentId, $visited, true)) {
            $visited[] = $currentParentId;
            $ancestorIds[] = $currentParentId;

            $parent = static::withoutGlobalScopes()->find($currentParentId);
            $currentParentId = $parent?->parent_id;
        }

        return $ancestorIds;
    }

    /**
     * Get all descendant IDs of this category recursively using cycle-safe BFS traversal.
     *
     * @return array<int>
     */
    public function getDescendantIds(): array
    {
        if (! $this->exists) {
            return [];
        }

        $descendantIds = [];
        $toProcess = [$this->id];
        $visited = [$this->id];

        while (! empty($toProcess)) {
            $currentId = array_shift($toProcess);
            $childIds = static::withoutGlobalScopes()
                ->where('parent_id', $currentId)
                ->pluck('id')
                ->all();

            foreach ($childIds as $childId) {
                if (! in_array($childId, $visited, true)) {
                    $visited[] = $childId;
                    $descendantIds[] = $childId;
                    $toProcess[] = $childId;
                }
            }
        }

        return $descendantIds;
    }

    /**
     * Determine whether this category or the specified parent ID has any inactive ancestor.
     */
    public function hasInactiveAncestor(?int $parentId = null): bool
    {
        $currentParentId = $parentId ?? $this->parent_id;
        $visited = [];

        while ($currentParentId && ! in_array($currentParentId, $visited, true)) {
            $visited[] = $currentParentId;
            $parent = static::withoutGlobalScopes()->find($currentParentId);
            if (! $parent) {
                break;
            }

            if (! $parent->is_active) {
                return true;
            }

            $currentParentId = $parent->parent_id;
        }

        return false;
    }

    /**
     * Validate business rules for category creation or modification.
     *
     * @throws ValidationException
     */
    public function validateBusinessRules(): void
    {
        if ($this->name !== null) {
            $this->name = trim($this->name);
        }

        $errors = [];

        // 1. Self-parent check
        if ($this->exists && $this->parent_id && (int) $this->parent_id === (int) $this->id) {
            $errors['parent_id'] = ['A category cannot be its own parent.'];
        }

        // 2. Deleted parent check
        if ($this->parent_id && static::onlyTrashed()->where('id', $this->parent_id)->exists()) {
            $errors['parent_id'] = ['Cannot assign a deleted category as parent.'];
        }

        // 3. Circular hierarchy check
        if ($this->exists && $this->parent_id && in_array((int) $this->parent_id, $this->getDescendantIds(), true)) {
            $errors['parent_id'] = ['Cannot assign this parent because it would create a circular category hierarchy.'];
        }

        // 4. Inactive parent / ancestor with active child
        if ($this->is_active && $this->parent_id) {
            if ($this->hasInactiveAncestor()) {
                $errors['parent_id'] = ['An active category cannot have an inactive parent.'];
            }
        }

        // 5. Deactivating category with active children
        if ($this->exists && $this->isDirty('is_active') && ! $this->is_active) {
            if ($this->hasActiveChildren()) {
                $errors['is_active'] = ['Cannot deactivate this category because it has active child categories.'];
            }
        }

        // 6. Duplicate category name under same parent
        if ($this->name !== null && ! isset($errors['name'])) {
            $duplicateQuery = static::query()
                ->where('name', $this->name);

            if ($this->parent_id === null) {
                $duplicateQuery->whereNull('parent_id');
            } else {
                $duplicateQuery->where('parent_id', $this->parent_id);
            }

            if ($this->exists) {
                $duplicateQuery->where('id', '!=', $this->id);
            }

            if ($duplicateQuery->exists()) {
                $errors['name'] = ['A category with this name already exists under the selected parent.'];
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
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
