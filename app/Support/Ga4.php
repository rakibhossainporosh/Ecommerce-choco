<?php

namespace App\Support;

use App\Models\Setting;

class Ga4
{
    public static function measurementId(): ?string
    {
        $value = Setting::get('ga4_measurement_id');

        return self::normalizeId(is_string($value) ? $value : null);
    }

    public static function normalizeId(?string $value): ?string
    {
        $id = strtoupper(trim((string) $value));

        return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) === 1 ? $id : null;
    }

    /**
     * @return array{measurement_id: ?string}
     */
    public static function share(): array
    {
        return [
            'measurement_id' => self::measurementId(),
        ];
    }
}
