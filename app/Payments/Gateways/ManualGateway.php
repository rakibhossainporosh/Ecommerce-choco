<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Payments\Data\PaymentStart;
use App\Payments\PaymentGatewayMode;
use App\Support\PaymentGatewaySettings;

class ManualGateway extends AbstractGateway
{
    public function __construct(
        private readonly string $key,
        private readonly int $sort,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->line('label');
    }

    public function description(): string
    {
        return $this->line('description');
    }

    public function mode(): PaymentGatewayMode
    {
        return PaymentGatewayMode::Manual;
    }

    public function sort(): int
    {
        return $this->sort;
    }

    public function settingsFields(): array
    {
        return [
            [
                'key' => 'account',
                'type' => 'text',
                'label' => __('Account number'),
                'helper' => __('Shown at checkout. This method stays hidden until a number is saved.'),
                'placeholder' => '01XXXXXXXXX',
                'max' => 50,
            ],
            [
                'key' => 'instructions',
                'type' => 'textarea',
                'label' => __('Instructions'),
                'helper' => __('Optional. Leave blank to use the default sentence.'),
                'max' => 500,
            ],
        ];
    }

    public function isReady(): bool
    {
        return PaymentGatewaySettings::text($this->key(), 'account') !== '';
    }

    public function successNotice(Order $order): array
    {
        $payment = $this->latestPayment($order);
        $reference = $payment?->transaction_id;

        return [
            'mode' => $this->mode()->value,
            'title' => $this->label(),
            'body' => $this->customerText(),
            'account' => PaymentGatewaySettings::text($this->key(), 'account') ?: null,
            'reference' => filled($reference) ? $reference : null,
            'paid' => (bool) $payment?->isCompleted(),
        ];
    }

    public function start(Order $order, ?string $reference): PaymentStart
    {
        $reference = filled($reference) ? mb_substr(trim($reference), 0, 100) : null;

        $order->recordPayment(
            amount: (float) $order->grand_total,
            method: $this->key(),
            status: PaymentTransactionStatus::Pending,
            transactionId: $reference,
            accountNumber: PaymentGatewaySettings::text($this->key(), 'account') ?: null,
            notes: $this->customerText(),
        );

        return new PaymentStart($order->order_number);
    }
}
