@auth
    <a
        href="{{ url('/') }}"
        target="_blank"
        rel="noopener noreferrer"
        class="admin-view-store"
        title="{{ __('View store') }}"
        aria-label="{{ __('View store') }}"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
        <span class="admin-view-store-label">{{ __('View store') }}</span>
    </a>
@endauth
