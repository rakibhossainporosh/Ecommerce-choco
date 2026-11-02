<?php

namespace App\Payments\Data;

readonly class PaymentOutcome
{
    private function __construct(
        public string $state,
        public ?string $orderNumber,
        public string $message,
    ) {}

    public static function paid(?string $orderNumber, string $message): self
    {
        return new self('paid', $orderNumber, $message);
    }

    public static function failed(?string $orderNumber, string $message): self
    {
        return new self('failed', $orderNumber, $message);
    }

    public function wasPaid(): bool
    {
        return $this->state === 'paid';
    }
}
