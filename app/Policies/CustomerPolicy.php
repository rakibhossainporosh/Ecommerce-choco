<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, ?Customer $customer = null): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, ?Customer $customer = null): bool
    {
        return $user->can('customers.update');
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, ?Customer $customer = null): bool
    {
        if (! $user->can('customers.delete')) {
            return false;
        }

        return $customer ? $customer->canBeDeleted() : true;
    }

    /**
     * Determine whether the user can bulk delete customers.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('customers.delete');
    }
}
