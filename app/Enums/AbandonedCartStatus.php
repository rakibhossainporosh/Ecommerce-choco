<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum AbandonedCartStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Open = 'open';
    case Reminded = 'reminded';
    case Recovered = 'recovered';
    case Closed = 'closed';

    public function label(): string
    {
        return __('enums.abandoned_cart_status.'.$this->value);
    }
}
