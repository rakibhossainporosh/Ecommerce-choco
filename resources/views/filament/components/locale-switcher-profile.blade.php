@auth
    <div class="admin-locale-profile">
        <x-filament::dropdown.header icon="heroicon-o-language">
            {{ __('Store language') }}
        </x-filament::dropdown.header>

        <x-filament::dropdown.list>
            @foreach (\App\Support\LocaleManager::options() as $option)
                <x-filament::dropdown.list.item
                    tag="form"
                    method="post"
                    :action="route('admin.site-locale.update')"
                    :icon="app()->getLocale() === $option['code'] ? 'heroicon-o-check' : 'heroicon-o-language'"
                    :color="app()->getLocale() === $option['code'] ? 'primary' : 'gray'"
                >
                    {{ $option['label'] }}
                    <input type="hidden" name="locale" value="{{ $option['code'] }}">
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </div>
@endauth
