@php
    use App\Models\Setting;
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Arr;

    $brandName = Setting::get('site_name', filament()->getBrandName() ?? config('app.name', 'Nobo Shop'));
    $dbLogo = Setting::get('site_logo');
    $brandLogo = $dbLogo ? asset('storage/' . ltrim($dbLogo, '/')) : filament()->getBrandLogo();
    $brandLogoHeight = filament()->getBrandLogoHeight() ?? '2.5rem';
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);

    $getLogoClasses = fn (bool $isDarkMode): string => Arr::toCssClasses([
        'fi-logo',
        'fi-logo-light' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'fi-logo-dark' => $isDarkMode,
    ]);

    $logoStyles = 'height: ' . e($brandLogoHeight) . '; max-height: ' . e($brandLogoHeight) . '; width: auto; object-fit: contain;';
@endphp

@capture($content, $logo, $isDarkMode = false)
    @if ($logo instanceof Htmlable)
        <div
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        >
            {{ $logo }}
        </div>
    @elseif (filled($logo))
        <img
            alt="{{ __('filament-panels::layout.logo.alt', ['name' => $brandName]) }}"
            src="{{ $logo }}"
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        />
    @else
        <div
            {{
                $attributes->class([
                    $getLogoClasses($isDarkMode),
                    'custom-login-brand-text',
                ])
            }}
        >
            {{ $brandName }}
        </div>
    @endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif
