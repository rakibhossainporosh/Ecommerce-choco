<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\PaymentOutcome;
use App\Payments\PaymentGatewayException;
use App\Support\PaymentGatewaySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractGateway implements PaymentGateway
{
    public function settingsFields(): array
    {
        return [];
    }

    public function present(): array
    {
        $account = PaymentGatewaySettings::text($this->key(), 'account');

        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'description' => $this->customerText(),
            'mode' => $this->mode()->value,
            'account' => $account !== '' ? $account : null,
        ];
    }

    public function successNotice(Order $order): array
    {
        return [
            'mode' => $this->mode()->value,
            'title' => $this->label(),
            'body' => $this->customerText(),
            'account' => null,
            'reference' => null,
            'paid' => false,
        ];
    }

    public function completeReturn(Request $request, string $outcome): PaymentOutcome
    {
        return PaymentOutcome::failed(null, __('storefront.checkout.payment_unavailable'));
    }

    public function completeWebhook(Request $request): PaymentOutcome
    {
        return $this->completeReturn($request, 'success');
    }

    public function refundOnline(Payment $payment, string $reason): void {}

    public function reconcile(Payment $payment): bool
    {
        return $payment->isCompleted();
    }

    protected function customerText(): string
    {
        $custom = PaymentGatewaySettings::text($this->key(), 'instructions');

        return $custom !== '' ? $custom : $this->description();
    }

    protected function line(string $field): string
    {
        $key = 'payments.'.$this->key().'.'.$field;
        $line = __($key);

        return is_string($line) ? $line : $key;
    }

    protected function latestPayment(Order $order): ?Payment
    {
        return $order->payments()->latest('id')->first();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function completeOnlinePayment(Payment $payment, array $metadata): void
    {
        DB::transaction(function () use ($payment, $metadata): void {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted()) {
                return;
            }

            $locked->metadata = array_merge($locked->metadata ?? [], $metadata);
            $locked->save();

            try {
                $locked->markAsCompleted();
            } catch (PaymentException) {
                if (! $locked->fresh()->isCompleted()) {
                    throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
                }
            }
        });

        $order = $payment->fresh()?->order;

        if (! $order || ! $order->isPending()) {
            return;
        }

        try {
            $order->confirm();
        } catch (Throwable $exception) {
            Log::warning('A paid online order could not be confirmed.', [
                'order' => $order->order_number,
                'gateway' => $this->key(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function abandonOnlinePayment(Payment $payment, string $reason): void
    {
        if (! $payment->isCompleted() && ! $payment->isFailed()) {
            try {
                $payment->markAsFailed($reason);
            } catch (PaymentException) {
                // A parallel confirmation may have completed this payment.
            }
        }

        $order = $payment->fresh()?->order;

        if (! $order || ! $order->canBeCancelled()) {
            return;
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        try {
            $order->cancel($reason);
        } catch (Throwable $exception) {
            Log::warning('An unpaid online order could not be cancelled.', [
                'order' => $order->order_number,
                'gateway' => $this->key(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function sameMoney(mixed $left, mixed $right): bool
    {
        if (! is_numeric($left) || ! is_numeric($right)) {
            return false;
        }

        return abs(round((float) $left, 2) - round((float) $right, 2)) < 0.009;
    }
}
