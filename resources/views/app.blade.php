<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-store-theme="{{ \App\Support\StorefrontTheme::key() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $storeTheme = \App\Support\StorefrontTheme::key();
            $storeName = (string) \App\Models\Setting::get('site_name', config('app.name', 'Nobo Shop'));
            if ($storeName === '') {
                $storeName = (string) config('app.name', 'Nobo Shop');
            }
        @endphp

        <title inertia>{{ $storefrontSeo['document_title'] ?? $storeName }}</title>
        <meta name="store-name" content="{{ $storeName }}">
        @if (! empty($storefrontSeo))
            <meta data-inertia="description" name="description" content="{{ $storefrontSeo['description'] }}">
            <meta data-inertia="robots" name="robots" content="{{ $storefrontSeo['robots'] }}">
            <link data-inertia="canonical" rel="canonical" href="{{ $storefrontSeo['canonical'] }}">
            <meta data-inertia="og:type" property="og:type" content="{{ $storefrontSeo['type'] }}">
            <meta data-inertia="og:site_name" property="og:site_name" content="{{ $storefrontSeo['site_name'] }}">
            <meta data-inertia="og:locale" property="og:locale" content="{{ $storefrontSeo['locale'] }}">
            <meta data-inertia="og:url" property="og:url" content="{{ $storefrontSeo['canonical'] }}">
            <meta data-inertia="og:title" property="og:title" content="{{ $storefrontSeo['document_title'] }}">
            <meta data-inertia="og:description" property="og:description" content="{{ $storefrontSeo['description'] }}">
            @if (! empty($storefrontSeo['image']))
                <meta data-inertia="og:image" property="og:image" content="{{ $storefrontSeo['image'] }}">
            @endif
            @if (! empty($storefrontSeo['price']) && ! empty($storefrontSeo['currency']))
                <meta data-inertia="product:price:amount" property="product:price:amount" content="{{ $storefrontSeo['price'] }}">
                <meta data-inertia="product:price:currency" property="product:price:currency" content="{{ $storefrontSeo['currency'] }}">
            @endif
            <meta data-inertia="twitter:card" name="twitter:card" content="{{ ! empty($storefrontSeo['image']) ? 'summary_large_image' : 'summary' }}">
            <meta data-inertia="twitter:title" name="twitter:title" content="{{ $storefrontSeo['document_title'] }}">
            <meta data-inertia="twitter:description" name="twitter:description" content="{{ $storefrontSeo['description'] }}">
            @if (! empty($storefrontSeo['image']))
                <meta data-inertia="twitter:image" name="twitter:image" content="{{ $storefrontSeo['image'] }}">
            @endif
            @if (! empty($storefrontSeo['json_ld']))
                <script data-inertia="jsonld" type="application/ld+json">{!! json_encode($storefrontSeo['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            @endif
        @endif
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        @if ($storeTheme === 'grove')
            <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;600;700&family=Hind+Siliguri:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @elseif ($storeTheme === 'harbor')
            <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        @else
            <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        @endif

        <!-- Favicon -->
        @php
            $siteFavicon = \App\Models\Setting::get('site_favicon');
        @endphp
        @if($siteFavicon)
            <link rel="icon" href="{{ asset('storage/' . ltrim($siteFavicon, '/')) }}">
        @endif

        @php
            $metaPixelId = \App\Support\MetaPixel::id();
            $ga4MeasurementId = \App\Support\Ga4::measurementId();
        @endphp
        @if ($metaPixelId)
            <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $metaPixelId }}');
                fbq('track', 'PageView');
            </script>
            <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" alt=""></noscript>
        @endif
        @if ($ga4MeasurementId)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $ga4MeasurementId }}');
            </script>
        @endif

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="{{ app()->getLocale() === 'bn' ? 'font-bengali' : 'font-sans' }} antialiased bg-[#FAF7F2] text-[#1E293B] selection:bg-[#E86A28] selection:text-white">
        @inertia
    </body>
</html>
