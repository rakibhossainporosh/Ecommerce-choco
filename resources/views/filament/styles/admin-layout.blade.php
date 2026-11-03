{{-- Cached static admin CSS (was ~106KB inline on every request). --}}
@php
    $adminLayoutCss = public_path('css/filament/admin-layout.css');
    $adminLayoutCssVersion = is_file($adminLayoutCss) ? (string) filemtime($adminLayoutCss) : (string) time();
@endphp
<link
    rel="stylesheet"
    href="{{ asset('css/filament/admin-layout.css') }}?v={{ $adminLayoutCssVersion }}"
>
<style>
    :root {
        --fi-admin-actions-label: "{{ __('Actions') }}";
    }
</style>
