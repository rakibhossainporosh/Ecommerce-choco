<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Payments\PaymentManager;

class PaymentMethodPresenter
{
    public static function label(mixed $state): string
    {
        if ($state instanceof PaymentMethod) {
            return $state->label();
        }

        if (! is_string($state) || $state === '') {
            return '';
        }

        $known = PaymentMethod::tryFrom($state);

        if ($known) {
            return $known->label();
        }

        return app(PaymentManager::class)->find($state)?->label()
            ?? str($state)->headline()->toString();
    }
}
