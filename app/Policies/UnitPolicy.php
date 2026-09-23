<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('units.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?Unit $unit = null): bool
    {
        return $user->can('units.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('units.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?Unit $unit = null): bool
    {
        return $user->can('units.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ?Unit $unit = null): bool
    {
        return $user->can('units.delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('units.delete');
    }
}
