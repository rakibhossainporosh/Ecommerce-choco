<?php

namespace App\Policies;

use App\Models\User;

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
     */
    public function update(User $user, mixed $product = null): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can delete the model.
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
