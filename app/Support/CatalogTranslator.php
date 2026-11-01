<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class CatalogTranslator
{
    public static function category(string $slug, string $fallback): string
    {
        return self::line('categories.'.$slug, $fallback);
    }

    public static function product(string $slug, string $field, ?string $fallback): string
    {
        return self::line('products.'.$slug.'.'.$field, (string) $fallback);
    }

    /**
     * Extra database search terms so an English query can find Bangla catalog rows.
     *
     * @return array<int, string>
     */
    public static function searchNeedles(string $search): array
    {
        $needles = [trim($search)];

        foreach (['en', 'bn'] as $locale) {
            $products = Lang::get('catalog.products', [], $locale);
            $categories = Lang::get('catalog.categories', [], $locale);

            if (is_array($products)) {
                foreach ($products as $slug => $fields) {
                    if (! is_array($fields) || ! self::contains($fields, $search)) {
                        continue;
                    }

                    $storedName = Lang::get('catalog.products.'.$slug.'.name', [], 'bn');

                    if (is_string($storedName) && $storedName !== '') {
                        $needles[] = $storedName;
                    }

                    $needles[] = str_replace('-', ' ', (string) $slug);
                }
            }

            if (is_array($categories)) {
                foreach ($categories as $slug => $label) {
                    if (! is_string($label) || mb_stripos($label, $search) === false) {
                        continue;
                    }

                    $storedName = Lang::get('catalog.categories.'.$slug, [], 'bn');

                    if (is_string($storedName) && $storedName !== '') {
                        $needles[] = $storedName;
                    }

                    $needles[] = (string) $slug;
                }
            }
        }

        return array_values(array_unique(array_filter($needles, fn (string $needle): bool => $needle !== '')));
    }

    private static function line(string $path, string $fallback): string
    {
        $key = 'catalog.'.$path;

        if (! Lang::has($key)) {
            return $fallback;
        }

        $line = __($key);

        return is_string($line) && $line !== '' ? $line : $fallback;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function contains(array $fields, string $search): bool
    {
        foreach ($fields as $value) {
            if (is_string($value) && mb_stripos($value, $search) !== false) {
                return true;
            }
        }

        return false;
    }
}
