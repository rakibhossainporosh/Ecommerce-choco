<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Rocket = 'rocket';
    case BankTransfer = 'bank_transfer';

    /**
     * Get all values of the enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
            self::Bkash => 'bKash',
            self::Nagad => 'Nagad',
            self::Rocket => 'Rocket',
            self::BankTransfer => 'Bank Transfer',
        };
    }
}
