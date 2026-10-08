<?php

namespace App\Policies;

use App\Models\ShippingMethod;
use App\Models\User;

class ShippingMethodPolicy
{
    /**
     * Determine whether the user can view any shipping methods.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shipping.view');
    }

    /**
     * Determine whether the user can view the shipping method.
     */
    public function view(User $user, ?ShippingMethod $method = null): bool
    {
        return $user->can('shipping.view');
    }

    /**
     * Determine whether the user can create shipping methods.
     */
    public function create(User $user): bool
    {
        return $user->can('shipping.create');
    }

    /**
     * Determine whether the user can update the shipping method.
     */
    public function update(User $user, ?ShippingMethod $method = null): bool
    {
        return $user->can('shipping.update');
    }

    /**
     * Determine whether the user can delete the shipping method.
     */
    public function delete(User $user, ?ShippingMethod $method = null): bool
    {
        if (! $user->can('shipping.update')) {
            return false;
        }

        if ($method === null) {
            return true;
        }

        return $method->shipments()->count() === 0;
    }

    /**
     * Bulk deletion of shipping methods is prohibited.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
