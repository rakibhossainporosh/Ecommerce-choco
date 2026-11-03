<?php

namespace App\Support;

use App\Models\Setting;

class StorefrontTheme
{
    /**
     * Preview metadata for the admin theme picker and public theme keys.
     *
     * @return array<string, array{
     *     label: string,
     *     blurb: string,
     *     shell: string,
     *     ink: string,
     *     brand: string,
     *     muted: string,
     *     panel: string,
     *     border: string,
     *     header: string,
     *     headerInk: string,
     *     radius: string,
     *     layout: string,
     *     hero: string,
     *     cta: string
     * }>
     */
    public static function previews(): array
    {
        return [
            'warm' => [
                'label' => 'Warm',
                'blurb' => 'Cream boutique storefront with soft cards and orange accents.',
                'shell' => '#FDFBF7',
                'ink' => '#1E293B',
                'brand' => '#E86A28',
                'muted' => '#64748B',
                'panel' => '#ffffff',
                'border' => '#EAE3D6',
                'header' => '#ffffff',
                'headerInk' => '#1E293B',
                'radius' => '10px',
                'layout' => 'boutique',
                'hero' => 'New arrivals',
                'cta' => 'Shop now',
            ],
            'grove' => [
                'label' => 'Grove',
                'blurb' => 'Editorial lookbook layout with deep green chrome.',
                'shell' => '#f3efe8',
                'ink' => '#1a2e28',
                'brand' => '#0e6b58',
                'muted' => '#5c6f67',
                'panel' => '#ffffff',
                'border' => '#d4ccc0',
                'header' => '#1a2e28',
                'headerInk' => '#f3faf6',
                'radius' => '0px',
                'layout' => 'editorial',
                'hero' => 'Lookbook',
                'cta' => 'Explore',
            ],
            'harbor' => [
                'label' => 'Harbor',
                'blurb' => 'Dense marketplace catalog with sharp navy UI.',
                'shell' => '#f4f7fb',
                'ink' => '#0f172a',
                'brand' => '#1d4ed8',
                'muted' => '#3e516b',
                'panel' => '#ffffff',
                'border' => '#d5e0ee',
                'header' => '#ffffff',
                'headerInk' => '#0f172a',
                'radius' => '2px',
                'layout' => 'marketplace',
                'hero' => 'Best deals',
                'cta' => 'Browse',
            ],
        ];
    }

    /**
     * The current cream and clay shop stays the default.
     *
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        return array_map(
            static fn (array $theme): string => $theme['label'],
            self::previews(),
        );
    }

    public static function key(): string
    {
        $key = Setting::get('storefront_theme', 'warm');

        return is_string($key) && isset(self::previews()[$key]) ? $key : 'warm';
    }

    public static function label(?string $key = null): string
    {
        $resolved = $key ?? self::key();

        return self::catalog()[$resolved] ?? self::catalog()['warm'];
    }
}
