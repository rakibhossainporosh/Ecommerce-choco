<?php

namespace App\Policies;

use App\Models\User;

/**
 * Foundation policy for order authorization.
 *
 * NOTE: Model-specific methods accept `mixed $order = null` as a deliberate
 * temporary foundation decision because the `Order` Eloquent model has not been
 * created yet. Once the `App\Models\Order` model is introduced in a subsequent
 * phase, these parameters must be updated to strictly type-hint `Order $order`.
 */
class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  mixed  $order  Deliberate temporary foundation parameter; update to Order when model is created.
     */
    public function view(User $user, mixed $order = null): bool
    {
        return $user->can('orders.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  mixed  $order  Deliberate temporary foundation parameter; update to Order when model is created.
     */
    public function update(User $user, mixed $order = null): bool
    {
        return $user->can('orders.update');
    }

    /**
     * Determine whether the user can cancel the order.
     *
     * @param  mixed  $order  Deliberate temporary foundation parameter; update to Order when model is created.
     */
    public function cancel(User $user, mixed $order = null): bool
    {
        return $user->can('orders.cancel');
    }

    /**
     * Determine whether the user can refund the order.
     *
     * @param  mixed  $order  Deliberate temporary foundation parameter; update to Order when model is created.
     */
    public function refund(User $user, mixed $order = null): bool
    {
        return $user->can('orders.refund');
    }
}
