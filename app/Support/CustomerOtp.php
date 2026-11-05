<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerOtp
{
    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_RESET = 'reset';

    public const TTL_SECONDS = 600;

    public const MAX_ATTEMPTS = 5;

    public static function issue(string $purpose, Customer $customer): string
    {
        $code = (string) random_int(100000, 999999);

        Cache::put(self::codeKey($purpose, $customer->id), Hash::make($code), self::TTL_SECONDS);
        Cache::put(self::attemptsKey($purpose, $customer->id), 0, self::TTL_SECONDS);

        return $code;
    }

    public static function verify(string $purpose, Customer $customer, string $code): bool
    {
        $attemptsKey = self::attemptsKey($purpose, $customer->id);
        $attempts = (int) Cache::get($attemptsKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => __('storefront.auth.otp_too_many'),
            ]);
        }

        $hash = Cache::get(self::codeKey($purpose, $customer->id));

        if (! is_string($hash) || $hash === '' || ! Hash::check(trim($code), $hash)) {
            Cache::put($attemptsKey, $attempts + 1, self::TTL_SECONDS);

            throw ValidationException::withMessages([
                'code' => __('storefront.auth.otp_invalid'),
            ]);
        }

        self::forget($purpose, $customer->id);

        return true;
    }

    public static function forget(string $purpose, int $customerId): void
    {
        Cache::forget(self::codeKey($purpose, $customerId));
        Cache::forget(self::attemptsKey($purpose, $customerId));
    }

    public static function codeKey(string $purpose, int $customerId): string
    {
        return "customer_otp:{$purpose}:{$customerId}:code";
    }

    public static function attemptsKey(string $purpose, int $customerId): string
    {
        return "customer_otp:{$purpose}:{$customerId}:attempts";
    }
}
