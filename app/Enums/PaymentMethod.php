<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use HasTranslatedLabel;

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
        return __('enums.payment_method.'.$this->value);
    }
}
