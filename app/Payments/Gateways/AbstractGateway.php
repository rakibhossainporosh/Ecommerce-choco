<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\PaymentOutcome;
use App\Support\PaymentGatewaySettings;
use Illuminate\Http\Request;

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
}
