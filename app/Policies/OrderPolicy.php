<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    /**
     * Determine whether the user can confirm the order.
     */
    public function confirm(User $user, Order $order): bool
    {
        return $user->can('orders.confirm');
    }

    /**
     * Determine whether the user can process the order.
     */
    public function process(User $user, Order $order): bool
    {
        return $user->can('orders.process');
    }

    /**
     * Determine whether the user can ship the order.
     */
    public function ship(User $user, Order $order): bool
    {
        return $user->can('orders.ship');
    }

    /**
     * Determine whether the user can deliver the order.
     */
    public function deliver(User $user, Order $order): bool
    {
        return $user->can('orders.deliver');
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.cancel');
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param  Order|mixed|null  $order
     */
    public function update(User $user, mixed $order = null): bool
    {
        return $user->can('orders.update');
    }

    /**
     * Determine whether the user can refund the order.
     *
     * @param  Order|mixed|null  $order
     */
    public function refund(User $user, mixed $order = null): bool
    {
        return $user->can('orders.refund');
    }
}
