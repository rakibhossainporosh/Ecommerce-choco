<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class AdminNavigationBadge
{
    /**
     * Short-lived nav badge counts so every admin page does not re-query.
     */
    public static function count(string $key, callable $callback, int $ttlSeconds = 45): ?string
    {
        // Tests mutate data constantly — never serve a stale badge count there.
        $count = app()->runningUnitTests()
            ? (int) $callback()
            : (int) Cache::remember(
                "admin.nav.badge.{$key}",
                now()->addSeconds($ttlSeconds),
                static fn (): int => (int) $callback(),
            );

        return $count > 0 ? (string) $count : null;
    }

    public static function forget(string $key): void
    {
        Cache::forget("admin.nav.badge.{$key}");
    }

    public static function flush(): void
    {
        foreach ([
            'orders.pending',
            'payments.pending',
            'returns.pending',
            'reviews.pending',
            'abandoned_carts.open',
        ] as $key) {
            self::forget($key);
        }
    }
}
