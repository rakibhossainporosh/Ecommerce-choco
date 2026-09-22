<?php

namespace App\Policies;

use App\Models\User;

/**
 * Foundation policy for product authorization.
 *
 * NOTE: Model-specific methods accept `mixed $product = null` as a deliberate
 * temporary foundation decision because the `Product` Eloquent model has not been
 * created yet. Once the `App\Models\Product` model is introduced in a subsequent
 * phase, these parameters must be updated to strictly type-hint `Product $product`.
 */
class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  mixed  $product  Deliberate temporary foundation parameter; update to Product when model is created.
     */
    public function view(User $user, mixed $product = null): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  mixed  $product  Deliberate temporary foundation parameter; update to Product when model is created.
     */
    public function update(User $user, mixed $product = null): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  mixed  $product  Deliberate temporary foundation parameter; update to Product when model is created.
     */
    public function delete(User $user, mixed $product = null): bool
    {
        return $user->can('products.delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('products.delete');
    }
}
