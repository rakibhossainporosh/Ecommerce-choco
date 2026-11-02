<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayMode;
use App\Payments\PaymentManager;
use Illuminate\Console\Command;
use Throwable;

class ReleaseAbandonedOnlineOrders extends Command
{
    protected $signature = 'payments:release-abandoned';

    protected $description = 'Cancel online orders that were left unpaid after the payment window';

    public function handle(PaymentManager $payments): int
    {
        $keys = [];

        foreach ($payments->all() as $gateway) {
            if ($gateway->mode() === PaymentGatewayMode::Redirect) {
                $keys[] = $gateway->key();
            }
        }

        if ($keys === []) {
            return self::SUCCESS;
        }

        $reason = __('storefront.checkout.payment_cancelled');

        Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->whereIn('payment_method', $keys)
            ->where('created_at', '<=', now()->subHours(2))
            ->whereDoesntHave('payments', function ($query): void {
                $query->where('status', PaymentTransactionStatus::Completed->value);
            })
            ->orderBy('id')
            ->each(function (Order $order) use ($reason, $payments): void {
                $pending = $order->payments()
                    ->where('status', PaymentTransactionStatus::Pending->value)
                    ->get();
                $method = $order->payment_method;
                $slug = $method instanceof PaymentMethod ? $method->value : (is_string($method) ? $method : '');
                $gateway = $slug !== '' ? $payments->find($slug) : null;

                foreach ($pending as $payment) {
                    try {
                        if ($gateway?->reconcile($payment)) {
                            return;
                        }
                    } catch (PaymentGatewayException) {
                        return;
                    }
                }

                $pending->each(function (Payment $payment): void {
                    try {
                        if ($payment->fresh()?->isPending()) {
                            $payment->markAsFailed('The online payment was not completed.');
                        }
                    } catch (Throwable) {
                        // A parallel return may have finished this payment.
                    }
                });

                $fresh = $order->fresh();

                if (! $fresh || ! $fresh->canBeCancelled()) {
                    return;
                }

                try {
                    $fresh->cancel($reason);
                } catch (Throwable) {
                    // The order left the pending state while this command was running.
                }
            });

        return self::SUCCESS;
    }
}
