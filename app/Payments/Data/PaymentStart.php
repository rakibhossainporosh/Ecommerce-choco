<?php

namespace App\Payments\Data;

readonly class PaymentStart
{
    public function __construct(
        public string $orderNumber,
        public ?string $redirectUrl = null,
    ) {}
}
