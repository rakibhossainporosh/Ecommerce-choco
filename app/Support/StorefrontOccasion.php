<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

class StorefrontOccasion
{
    /**
     * One active occasion at a time. The storefront reads this on every page.
     *
     * @return array<string, array{en: string, bn: string}>
     */
    public static function catalog(): array
    {
        return [
            'eid_fitr' => self::entry('Eid Mubarak', 'ঈদ মোবারক', 'Eid sale is live', 'ঈদ সেল চলছে', 'Shop the Eid edit', 'ঈদের ক্যাটাগরি', 'Eid prices, lowered', 'ঈদে দাম কমেছে'),
            'eid_adha' => self::entry('Qurbani Eid Mubarak', 'কোরবানির ঈদ মোবারক', 'Qurbani deals are live', 'কোরবানির ডিল চলছে', 'Shop by category', 'ক্যাটাগরি ধরে কিনুন', 'Qurbani prices, cut', 'কোরবানিতে দাম কম'),
            'durga_puja' => self::entry('Shubho Durga Puja', 'শুভ দুর্গাপূজা', 'Puja shopping is open', 'পূজার কেনাকাটা চলছে', 'Browse the puja edit', 'পূজার ক্যাটাগরি', 'Puja offers inside', 'পূজার অফার'),
            'kali_puja' => self::entry('Shubho Kali Puja', 'শুভ কালীপূজা', 'Kali Puja night market', 'কালীপূজার বাজার', 'Pick a category', 'ক্যাটাগরি বেছে নিন', 'Night-market prices', 'রাতের বাজারের দাম'),
            'noboborsho' => self::entry('Shubho Noboborsho', 'শুভ নববর্ষ', 'New year, new arrivals', 'নতুন বছর, নতুন জিনিস', 'Start with a category', 'ক্যাটাগরি থেকে শুরু', 'Noboborsho price cuts', 'নববর্ষের ছাড়'),
            'christmas' => self::entry('Merry Christmas', 'মেরি ক্রিসমাস', 'Christmas offers are here', 'ক্রিসমাস অফার এসেছে', 'Gifts by category', 'ক্যাটাগরিতে উপহার', 'Christmas prices dropped', 'ক্রিসমাসে দাম কম'),
            'halloween' => self::entry('Happy Halloween', 'হ্যালোউইন', 'Halloween deals are on', 'হ্যালোউইন ডিল চলছে', 'Shop by category', 'ক্যাটাগরি দেখুন', 'Marked down for tonight', 'আজকের ছাড়'),
            'new_year' => self::entry('Happy New Year', 'হ্যাপি নিউ ইয়ার', 'New year sale is open', 'নিউ ইয়ার সেল চলছে', 'Browse by category', 'ক্যাটাগরি ঘুরে দেখুন', 'Fresh prices for the year', 'নতুন বছরের দাম'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::catalog() as $key => $labels) {
            $options[$key] = $labels['en'];
        }

        return $options;
    }

    /**
     * @return array{key: string, label: string}|null
     */
    public static function present(): ?array
    {
        $enabled = filter_var(Setting::get('occasion_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $key = Setting::get('occasion_key', '');

        if (! $enabled || ! is_string($key) || ! isset(self::catalog()[$key])) {
            return null;
        }

        $start = self::moment('occasion_starts_at');
        $end = self::moment('occasion_ends_at');
        $now = now();

        if (($start && $now->lt($start)) || ($end && $now->gte($end))) {
            return null;
        }

        $labels = self::catalog()[$key];

        return [
            'key' => $key,
            'label' => app()->getLocale() === 'bn' ? $labels['bn'] : $labels['en'],
            'lines' => [
                'hero' => self::line($key, 'hero'),
                'categories' => self::line($key, 'categories'),
                'products' => self::line($key, 'products'),
            ],
            'ends_at' => $end?->toIso8601String(),
            'hero_image' => self::heroImage(),
            'sale_ids' => self::saleIds(),
            'sections' => self::sections(),
        ];
    }

    /**
     * @return list<int>
     */
    public static function saleIds(): array
    {
        $saved = Setting::get('occasion_sale_ids', []);

        if (! is_array($saved)) {
            return [];
        }

        $ids = [];

        foreach ($saved as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 4);
    }

    private static function heroImage(): ?string
    {
        $path = Setting::get('occasion_hero_image', '');

        if (! is_string($path) || ! str_starts_with($path, 'occasions/') || str_contains($path, '..')) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private static function line(string $key, string $part): string
    {
        $entry = self::catalog()[$key][$part] ?? null;
        $locale = app()->getLocale() === 'bn' ? 'bn' : 'en';

        if (! is_array($entry)) {
            return self::catalog()[$key][$locale];
        }

        return $entry[$locale];
    }

    /**
     * @return array{en: string, bn: string, hero: array{en: string, bn: string}, categories: array{en: string, bn: string}, products: array{en: string, bn: string}}
     */
    private static function entry(
        string $en,
        string $bn,
        string $heroEn,
        string $heroBn,
        string $categoriesEn,
        string $categoriesBn,
        string $productsEn,
        string $productsBn,
    ): array {
        return [
            'en' => $en,
            'bn' => $bn,
            'hero' => ['en' => $heroEn, 'bn' => $heroBn],
            'categories' => ['en' => $categoriesEn, 'bn' => $categoriesBn],
            'products' => ['en' => $productsEn, 'bn' => $productsBn],
        ];
    }

    private static function moment(string $key): ?Carbon
    {
        $value = Setting::get($key, '');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The top bar is the general effect. The other keys dress one homepage section.
     *
     * @return array{general: bool, hero: bool, categories: bool, products: bool}
     */
    public static function sections(): array
    {
        $saved = Setting::get('occasion_sections', []);
        $sections = [
            'general' => true,
            'hero' => false,
            'categories' => false,
            'products' => false,
        ];

        if (! is_array($saved)) {
            return $sections;
        }

        foreach (array_keys($sections) as $key) {
            if (array_key_exists($key, $saved)) {
                $sections[$key] = filter_var($saved[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $sections;
    }
}
