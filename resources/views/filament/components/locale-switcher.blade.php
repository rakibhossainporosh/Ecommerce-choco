@auth
    @php
        $localeOptions = \App\Support\LocaleManager::options();
        $currentLocale = collect($localeOptions)->firstWhere('code', app()->getLocale()) ?? $localeOptions[0];
    @endphp

    <div
        class="admin-locale"
        x-data="{
            open: false,
            top: 0,
            right: 8,
            width: 208,
            place() {
                const rect = this.$refs.trigger.getBoundingClientRect()
                const menuWidth = Math.min(208, window.innerWidth - 16)
                let right = Math.round(window.innerWidth - rect.right)

                if (right < 8) {
                    right = 8
                }

                if (right + menuWidth > window.innerWidth - 8) {
                    right = 8
                }

                this.top = Math.round(rect.bottom + 8)
                this.right = right
                this.width = menuWidth
            },
            toggle() {
                this.place()
                this.open = ! this.open
            },
        }"
        @resize.window="open && place()"
        @keydown.escape.window="open = false"
    >
        <button
            type="button"
            class="admin-locale-trigger"
            x-ref="trigger"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox"
            aria-label="{{ __('Store language') }}"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <span class="admin-locale-name">{{ $currentLocale['label'] }}</span>
            <svg class="admin-locale-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <template x-teleport="body">
            <div class="admin-locale-layer" x-show="open" x-cloak @click="open = false">
                <div
                    class="admin-locale-menu"
                    role="listbox"
                    aria-label="{{ __('Store language') }}"
                    @click.stop
                    :style="`top:${top}px; right:${right}px; width:${width}px`"
                >
                    <p class="admin-locale-menu-label">{{ __('Store language') }}</p>
                    @foreach ($localeOptions as $option)
                        <form method="POST" action="{{ route('admin.site-locale.update') }}">
                            @csrf
                            <button
                                type="submit"
                                name="locale"
                                value="{{ $option['code'] }}"
                                class="admin-locale-option {{ app()->getLocale() === $option['code'] ? 'is-active' : '' }}"
                                role="option"
                                aria-selected="{{ app()->getLocale() === $option['code'] ? 'true' : 'false' }}"
                            >
                                <span>{{ $option['label'] }}</span>
                                @if (app()->getLocale() === $option['code'])
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </template>
    </div>
@endauth
