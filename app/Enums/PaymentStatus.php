<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

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
     * Get the human-readable label for the payment status.
     */
    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }
}
