<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Data\PaymentStart;
use App\Payments\PaymentGatewayMode;

class CodGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'cod';
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
        return PaymentGatewayMode::Cod;
    }

    public function sort(): int
    {
        return 10;
    }

    public function isReady(): bool
    {
        return true;
    }

    public function start(Order $order, ?string $reference): PaymentStart
    {
        return new PaymentStart($order->order_number);
    }
}
