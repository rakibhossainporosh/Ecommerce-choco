<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LocaleManager
{
    public const COOKIE = 'storefront_locale';

    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return ['en', 'bn'];
    }

    public static function isSupported(?string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    public static function siteLocale(): string
    {
        if (! Schema::hasTable('settings')) {
            return 'en';
        }

        $locale = Setting::get('site_locale', 'en');

        return self::isSupported(is_string($locale) ? $locale : null) ? $locale : 'en';
    }

    public static function forRequest(Request $request): string
    {
        $siteLocale = self::siteLocale();

        if ($request->is('admin') || $request->is('admin/*')) {
            return $siteLocale;
        }

        $preferred = $request->cookie(self::COOKIE);

        if (self::isSupported(is_string($preferred) ? $preferred : null)) {
            return $preferred;
        }

        return $siteLocale;
    }

    /**
     * @return array<int, array{code: string, label: string, short: string}>
     */
    public static function options(): array
    {
        return [
            ['code' => 'en', 'label' => 'English', 'short' => 'EN'],
            ['code' => 'bn', 'label' => 'বাংলা', 'short' => 'বাং'],
        ];
    }
}
