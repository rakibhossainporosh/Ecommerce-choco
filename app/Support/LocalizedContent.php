<?php

namespace App\Support;

class LocalizedContent
{
    /**
     * Show the current language when the saved value is still a known default.
     * A custom value written by the shop owner is kept as they wrote it.
     *
     * @param  array<string, string>  $defaults
     */
    /**
     * Phone numbers follow the active language: English uses 0-9, Bangla uses ০-৯.
     */
    public static function digits(?string $value): string
    {
        $value ??= '';

        if (app()->getLocale() === 'bn') {
            return strtr(self::asciiDigits($value), [
                '0' => '০',
                '1' => '১',
                '2' => '২',
                '3' => '৩',
                '4' => '৪',
                '5' => '৫',
                '6' => '৬',
                '7' => '৭',
                '8' => '৮',
                '9' => '৯',
            ]);
        }

        return self::asciiDigits($value);
    }

    public static function asciiDigits(?string $value): string
    {
        return strtr($value ?? '', [
            '০' => '0',
            '১' => '1',
            '২' => '2',
            '৩' => '3',
            '৪' => '4',
            '৫' => '5',
            '৬' => '6',
            '৭' => '7',
            '৮' => '8',
            '৯' => '9',
        ]);
    }

    public static function text(?string $stored, array $defaults): string
    {
        $stored ??= '';
        $locale = app()->getLocale();
        $known = array_values($defaults);

        if ($stored === '' || in_array($stored, $known, true)) {
            return $defaults[$locale] ?? $defaults['en'] ?? $stored;
        }

        return $stored;
    }

    /**
     * @param  array<int, array<string, mixed>>  $links
     * @param  array<string, array<string, string>>  $labelsByUrl
     * @return array<int, array<string, mixed>>
     */
    public static function links(array $links, array $labelsByUrl): array
    {
        return array_map(function (array $link) use ($labelsByUrl): array {
            $url = (string) ($link['url'] ?? '');
            $label = (string) ($link['label'] ?? '');

            if (isset($labelsByUrl[$url])) {
                $link['label'] = self::text($label, $labelsByUrl[$url]);
            }

            return $link;
        }, $links);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function settingDefaults(): array
    {
        return [
            'site_tagline' => [
                'en' => 'Everyday favourites, delivered',
                'bn' => 'আপনার প্রতিদিনের পছন্দ',
            ],
            'footer_about' => [
                'en' => 'Everyday favourites, delivered — a shop you can trust for your daily needs. We stand behind the quality of every product.',
                'bn' => 'আপনার প্রতিদিনের পছন্দ — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।',
            ],
            'footer_col2_title' => [
                'en' => 'Useful links',
                'bn' => 'প্রয়োজনীয় লিংক',
            ],
            'footer_col3_title' => [
                'en' => 'Customer care',
                'bn' => 'গ্রাহক সেবা',
            ],
            'footer_col4_title' => [
                'en' => 'Contact',
                'bn' => 'যোগাযোগ',
            ],
            'contact_address' => [
                'en' => '523, Nobo Shop Tower, Dhanmondi, Dhaka-1205, Bangladesh',
                'bn' => '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ',
            ],
            'footer_copyright' => [
                'en' => '© {year} Nobo Shop. All rights reserved.',
                'bn' => '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function linkDefaults(): array
    {
        return [
            '/about-us' => [
                'en' => 'About us',
                'bn' => 'আমাদের সম্পর্কে',
            ],
            '/contact' => [
                'en' => 'Contact',
                'bn' => 'যোগাযোগ',
            ],
            '/track-order' => [
                'en' => 'Track order',
                'bn' => 'অর্ডার ট্র্যাকিং',
            ],
            '/?offer=1#featured' => [
                'en' => 'Offers',
                'bn' => 'অফার সমূহ',
            ],
            '/return-policy' => [
                'en' => 'Return policy',
                'bn' => 'রিটার্ন পলিসি',
            ],
            '/privacy-policy' => [
                'en' => 'Privacy policy',
                'bn' => 'প্রাইভেসি পলিসি',
            ],
            '/terms-conditions' => [
                'en' => 'Terms and conditions',
                'bn' => 'শর্তাবলী',
            ],
            '/faq' => [
                'en' => 'FAQ',
                'bn' => 'সাধারণ জিজ্ঞাসা (FAQ)',
            ],
        ];
    }
}
