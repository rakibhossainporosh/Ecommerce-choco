<?php

namespace App\Exceptions;

use DomainException;

class PaymentException extends DomainException
{
    public static function cannotRecordForCancelledOrder(string $orderNumber): self
    {
        return new self("Cannot record payment for cancelled order [{$orderNumber}].");
    }

    public static function invalidAmount(float $amount): self
    {
        return new self("Payment amount must be greater than zero. Given: [{$amount}].");
    }

    public static function paymentAlreadyCompleted(string $paymentNumber): self
    {
        return new self("Payment [{$paymentNumber}] has already been completed.");
    }

    public static function paymentAlreadyRefunded(string $paymentNumber): self
    {
        return new self("Payment [{$paymentNumber}] has already been refunded.");
    }

    public static function cannotRefundUncompletedPayment(string $paymentNumber, string $currentStatus): self
    {
        return new self("Cannot refund payment [{$paymentNumber}] because its status is [{$currentStatus}].");
    }
}
