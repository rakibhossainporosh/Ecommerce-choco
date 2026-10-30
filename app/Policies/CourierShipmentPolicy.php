<?php

namespace App\Policies;

use App\Models\CourierShipment;
use App\Models\User;

class CourierShipmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('courier-shipments.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourierShipment $courierShipment): bool
    {
        return $user->can('courier-shipments.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('courier-shipments.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourierShipment $courierShipment): bool
    {
        return $user->can('courier-shipments.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourierShipment $courierShipment): bool
    {
        return $user->can('courier-shipments.delete');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
