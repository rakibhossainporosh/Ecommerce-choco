{{-- Custom Sidebar Header Toggle and Brand Logo --}}
<div class="custom-sidebar-brand-wrapper">
    {{-- Expanded State: Logo on the left, collapse toggle on the right --}}
    <div x-show="$store.sidebar.isOpen" class="custom-sidebar-header-expanded">
        <a href="{{ filament()->getHomeUrl() ?? url('/admin') }}" class="custom-brand-logo-link">
            @php
                $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Nobo Shop'));
                $siteLogo = \App\Models\Setting::get('site_logo');
            @endphp
            @if ($siteLogo)
                <img src="{{ asset('storage/' . ltrim($siteLogo, '/')) }}" alt="{{ $siteName }}" class="custom-brand-logo-img" />
            @else
                <span class="custom-brand-name">{{ $siteName }}</span>
            @endif
        </a>

        {{-- Collapse Button in Expanded State (Sidebar icon with chevron pointing left) --}}
        <button
            type="button"
            x-on:click="$store.sidebar.close()"
            title="Collapse sidebar"
            class="custom-sidebar-toggle-btn"
        >
            <svg class="custom-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2" />
                <path d="M9 3v18" />
                <path d="m16 15-3-3 3-3" />
            </svg>
        </button>
    </div>

    {{-- Collapsed State: Logo hidden, ONLY centered toggle button with chevron pointing right --}}
    <div x-show="! $store.sidebar.isOpen" class="custom-sidebar-header-collapsed">
        <button
            type="button"
            x-on:click="$store.sidebar.open()"
            title="Expand sidebar"
            class="custom-sidebar-toggle-btn"
        >
            <svg class="custom-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2" />
                <path d="M9 3v18" />
                <path d="m14 9 3 3-3 3" />
            </svg>
        </button>
    </div>
</div>
