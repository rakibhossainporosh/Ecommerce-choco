<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentTransactionStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

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
     * Get the human-readable label for the transaction status.
     */
    public function label(): string
    {
        return __('enums.payment_transaction.'.$this->value);
    }
}
