<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, ?Payment $payment = null): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can record/create payments.
     */
    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, ?Payment $payment = null): bool
    {
        return $user->can('payments.update');
    }

    /**
     * Payments are immutable ledger records; deletion is strictly prohibited.
     */
    public function delete(User $user, ?Payment $payment = null): bool
    {
        return false;
    }

    /**
     * Bulk deletion of payment records is strictly prohibited.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can refund the payment.
     */
    public function refund(User $user, ?Payment $payment = null): bool
    {
        if (! $user->can('payments.refund')) {
            return false;
        }

        return $payment === null || $payment->canBeRefunded();
    }
}
