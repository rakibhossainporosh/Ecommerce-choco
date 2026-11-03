@php
    $canUpdate = $this->canUpdateThemes();
    $selectedKey = $this->selectedThemeKey();
    $liveKey = $this->liveTheme;
    $isDirty = $this->hasUnsavedThemeChanges();
@endphp

<x-filament-panels::page>
    <div class="theme-settings-shell space-y-6">
        <div class="theme-settings-toolbar" wire:key="theme-settings-toolbar-{{ $selectedKey }}-{{ $liveKey }}">
            <div class="theme-settings-toolbar-copy">
                <p class="theme-settings-toolbar-title">
                    Live storefront theme:
                    <strong>{{ \App\Support\StorefrontTheme::label($liveKey) }}</strong>
                </p>
                @if ($isDirty)
                    <p class="theme-settings-toolbar-dirty" role="status">
                        Unsaved selection: {{ \App\Support\StorefrontTheme::label($selectedKey) }} — save to publish.
                    </p>
                @else
                    <p class="theme-settings-toolbar-hint">
                        {{ $canUpdate ? 'Select a theme preview below, then save to publish.' : 'View only — publishing requires settings update permission.' }}
                    </p>
                @endif
            </div>
            <a
                href="{{ url('/') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="theme-settings-storefront-link"
            >
                {{ __('View storefront') }}
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 0 0 1.06 0l7.22-7.22v5.69a.75.75 0 0 0 1.5 0v-7.5a.75.75 0 0 0-.75-.75h-7.5a.75.75 0 0 0 0 1.5h5.69l-7.22 7.22a.75.75 0 0 0 0 1.06Z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>

        @include('filament.components.storefront-theme-picker')

        {{ $this->form }}
    </div>
</x-filament-panels::page>
