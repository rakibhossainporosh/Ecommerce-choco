<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-store-theme="{{ \App\Support\StorefrontTheme::key() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Nobo Shop') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $storeTheme = \App\Support\StorefrontTheme::key();
        @endphp
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

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="{{ app()->getLocale() === 'bn' ? 'font-bengali' : 'font-sans' }} antialiased bg-[#FAF7F2] text-[#1E293B] selection:bg-[#E86A28] selection:text-white">
        @inertia
    </body>
</html>
