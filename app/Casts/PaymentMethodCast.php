<?php

namespace App\Casts;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Known methods stay as the PaymentMethod enum. Any other registered
 * gateway slug is stored and read as a string, so a new gateway does
 * not require an enum change.
 *
 * @implements CastsAttributes<PaymentMethod|string|null, PaymentMethod|string|null>
 */
class PaymentMethodCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PaymentMethod|string|null
    {
        if ($value instanceof PaymentMethod) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return PaymentMethod::tryFrom((string) $value) ?? (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof PaymentMethod) {
            return $value->value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return strtolower(trim((string) $value));
    }
}
