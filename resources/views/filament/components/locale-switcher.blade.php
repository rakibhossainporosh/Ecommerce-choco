@auth
    <form method="POST" action="{{ route('admin.site-locale.update') }}" class="admin-locale-switch">
        @csrf
        <span class="admin-locale-switch-label">{{ __('Language') }}</span>
        <button type="submit" name="locale" value="en" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}" title="{{ __('Show the admin panel and website in English') }}">
            EN
        </button>
        <button type="submit" name="locale" value="bn" class="{{ app()->getLocale() === 'bn' ? 'is-active' : '' }}" title="{{ __('Show the admin panel and website in Bangla') }}">
            বাং
        </button>
    </form>
@endauth
