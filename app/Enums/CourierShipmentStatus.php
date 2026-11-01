<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum CourierShipmentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.courier_shipment_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Dispatched => 'primary',
            self::Delivered => 'success',
            self::Failed => 'danger',
            self::Returned => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
