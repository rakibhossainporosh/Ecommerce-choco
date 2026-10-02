<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';

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
        };
    }
}
