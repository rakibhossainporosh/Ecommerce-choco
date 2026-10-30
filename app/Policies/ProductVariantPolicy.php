<?php

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
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
    public function view(User $user, ProductVariant $variant): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductVariant $variant): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductVariant $variant): bool
    {
        return $user->can('products.update') && $variant->canBeDeleted();
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('products.update');
    }
}
