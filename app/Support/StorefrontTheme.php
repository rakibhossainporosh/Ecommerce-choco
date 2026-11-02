<?php

namespace App\Support;

use App\Models\Setting;

class StorefrontTheme
{
    /**
     * The current cream and clay shop stays the default.
     *
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        return [
            'warm' => 'Warm',
            'grove' => 'Grove',
            'harbor' => 'Harbor',
        ];
    }

    public static function key(): string
    {
        $key = Setting::get('storefront_theme', 'warm');

        return is_string($key) && isset(self::catalog()[$key]) ? $key : 'warm';
    }
}
