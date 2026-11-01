@props([
    'heading' => null,
    'subheading' => null,
])

@php
    use Filament\Support\Facades\FilamentView;
    use Filament\Tables\Contracts\HasTable;
    use Filament\View\PanelsRenderHook;

    $heading ??= $this->getHeading();
    $subheading ??= $this->getSubHeading();
    $hasLogo = $this->hasLogo();
@endphp

<div {{ $attributes->class(['fi-simple-page']) }}>
    {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_PAGE_START, scopes: $this->getRenderHookScopes()) }}

    <div class="fi-simple-page-content">
        @if (filled($heading) || $hasLogo || filled($subheading))
            <x-filament-panels::header.simple
                :heading="$heading"
                :logo="$hasLogo"
                :subheading="$subheading"
            />
        @endif

        {{ $slot }}
    </div>

    <div class="custom-login-footer-badge">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
        </svg>
        <span>{{ __('End-to-End Encrypted') }} &bull; {{ __('Enterprise Secure Admin Portal') }}</span>
    </div>

    @if (! $this instanceof HasTable)
        <x-filament-actions::modals />
    @endif

    {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_PAGE_END, scopes: $this->getRenderHookScopes()) }}
</div>

