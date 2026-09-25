<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine whether the user can view inventory lists.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Determine whether the user can view a specific inventory record.
     */
    public function view(User $user, ?Inventory $inventory = null): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Determine whether the user can adjust stock quantities.
     */
    public function adjust(User $user, ?Inventory $inventory = null): bool
    {
        return $user->can('inventory.adjust');
    }

    /**
     * Determine whether the user can view inventory movement history.
     */
    public function viewHistory(User $user, ?Inventory $inventory = null): bool
    {
        return $user->can('inventory.history');
    }
}
