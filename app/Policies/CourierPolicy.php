<?php

namespace App\Policies;

use App\Models\Courier;
use App\Models\User;

class CourierPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('couriers.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Courier $courier): bool
    {
        return $user->can('couriers.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('couriers.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Courier $courier): bool
    {
        return $user->can('couriers.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Courier $courier): bool
    {
        if (! $user->can('couriers.delete')) {
            return false;
        }

        // Only allow deletion if there are no historical shipments tied to it
        return ! $courier->shipments()->exists();
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
