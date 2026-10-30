<?php

namespace App\Policies;

use App\Models\User;

/**
 * Foundation policy for category authorization.
 *
 * NOTE: Model-specific methods accept `mixed $category = null` as a deliberate
 * temporary foundation decision because the `Category` Eloquent model has not been
 * created yet. Once the `App\Models\Category` model is introduced in a subsequent
 * phase, these parameters must be updated to strictly type-hint `Category $category`.
 */
class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  mixed  $category  Deliberate temporary foundation parameter; update to Category when model is created.
     */
    public function view(User $user, mixed $category = null): bool
    {
        return $user->can('categories.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  mixed  $category  Deliberate temporary foundation parameter; update to Category when model is created.
     */
    public function update(User $user, mixed $category = null): bool
    {
        return $user->can('categories.update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  mixed  $category  Deliberate temporary foundation parameter; update to Category when model is created.
     */
    public function delete(User $user, mixed $category = null): bool
    {
        return $user->can('categories.delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('categories.delete');
    }
}
