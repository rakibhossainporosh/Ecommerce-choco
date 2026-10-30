@props([
    'heading' => null,
    'logo' => true,
    'subheading' => null,
])

<header class="fi-simple-header">
    @if ($logo)
        <div class="fi-simple-header-logo-wrapper">
            <x-filament-panels::logo />
        </div>
    @endif

    @if (filled($heading))
        <h1 class="fi-simple-header-heading">
            {{ $heading }}
        </h1>
    @else
        <h1 class="fi-simple-header-heading">
            Sign in
        </h1>
    @endif


    @if (filled($subheading))
        <p class="fi-simple-header-subheading">
            {{ $subheading }}
        </p>
    @else
        <p class="fi-simple-header-subheading">
            Enter your credentials to access your administrative dashboard
        </p>
    @endif
</header>

