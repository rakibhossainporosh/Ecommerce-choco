<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasLabel
{
    use HasTranslatedLabel;

    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return __('enums.coupon_type.'.$this->value);
    }
}
