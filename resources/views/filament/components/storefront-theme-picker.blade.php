@php
    use App\Models\Setting;
    use App\Support\StorefrontTheme;

    $siteName = (string) Setting::get('site_name', config('app.name', 'Nobo Shop'));
    if ($siteName === '') {
        $siteName = (string) config('app.name', 'Nobo Shop');
    }

    $themes = StorefrontTheme::previews();
    $selected = $this->selectedThemeKey();
    $liveTheme = $this->liveTheme;
    $canUpdate = $this->canUpdateThemes();
@endphp

<div
    class="theme-preview-grid {{ $canUpdate ? '' : 'is-readonly' }}"
    role="radiogroup"
    aria-label="Shop theme"
    aria-disabled="{{ $canUpdate ? 'false' : 'true' }}"
>
    @foreach ($themes as $key => $theme)
        <label
            class="theme-preview-card {{ $selected === $key ? 'is-selected' : '' }} {{ $liveTheme === $key ? 'is-live' : '' }}"
            style="--tp-brand: {{ $theme['brand'] }}; --tp-shell: {{ $theme['shell'] }}; --tp-ink: {{ $theme['ink'] }}; --tp-muted: {{ $theme['muted'] }}; --tp-panel: {{ $theme['panel'] }}; --tp-border: {{ $theme['border'] }}; --tp-header: {{ $theme['header'] }}; --tp-header-ink: {{ $theme['headerInk'] }}; --tp-radius: {{ $theme['radius'] }};"
            wire:key="theme-preview-{{ $key }}"
        >
            <input
                type="radio"
                class="theme-preview-input"
                wire:model.live="data.storefront_theme"
                value="{{ $key }}"
                @disabled(! $canUpdate)
            />

            <div class="theme-preview-canvas" aria-hidden="true" data-layout="{{ $theme['layout'] }}">
                <div class="theme-preview-window">
                    <div class="theme-preview-window-bar">
                        <span></span><span></span><span></span>
                        <em class="theme-preview-url">{{ \Illuminate\Support\Str::limit($siteName, 22) }}</em>
                    </div>
                    <div class="theme-preview-store">
                        <div class="theme-preview-topbar">
                            <strong class="theme-preview-brand">{{ \Illuminate\Support\Str::limit($siteName, 18) }}</strong>
                            <nav class="theme-preview-nav">
                                <span>Shop</span>
                                <span>Cart</span>
                            </nav>
                        </div>

                        @if ($theme['layout'] === 'marketplace')
                            <div class="theme-preview-hero theme-preview-hero--compact">
                                <div class="theme-preview-hero-copy">
                                    <b>{{ $theme['hero'] }}</b>
                                    <i>{{ $siteName }} marketplace</i>
                                </div>
                                <span class="theme-preview-cta">{{ $theme['cta'] }}</span>
                            </div>
                            <div class="theme-preview-market">
                                <aside class="theme-preview-filters">
                                    <span></span><span></span><span></span>
                                </aside>
                                <div class="theme-preview-market-grid">
                                    @foreach (range(1, 6) as $i)
                                        <article class="theme-preview-product theme-preview-product--dense">
                                            <div class="theme-preview-product-media"></div>
                                            <div class="theme-preview-product-meta">
                                                <span></span>
                                                <em></em>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($theme['layout'] === 'editorial')
                            <div class="theme-preview-editorial">
                                <div class="theme-preview-editorial-hero">
                                    <div class="theme-preview-hero-copy theme-preview-hero-copy--on-dark">
                                        <b>{{ $theme['hero'] }}</b>
                                        <i>{{ $siteName }}</i>
                                    </div>
                                    <span class="theme-preview-cta theme-preview-cta--light">{{ $theme['cta'] }}</span>
                                </div>
                                <div class="theme-preview-editorial-row">
                                    @foreach (range(1, 3) as $i)
                                        <article class="theme-preview-product">
                                            <div class="theme-preview-product-media"></div>
                                            <div class="theme-preview-product-meta">
                                                <span></span>
                                                <em></em>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="theme-preview-hero">
                                <div class="theme-preview-hero-copy">
                                    <b>{{ $theme['hero'] }}</b>
                                    <i>Welcome to {{ \Illuminate\Support\Str::limit($siteName, 16) }}</i>
                                </div>
                                <span class="theme-preview-cta">{{ $theme['cta'] }}</span>
                            </div>
                            <div class="theme-preview-boutique">
                                <div class="theme-preview-boutique-row">
                                    @foreach (range(1, 2) as $i)
                                        <article class="theme-preview-product">
                                            <div class="theme-preview-product-media"></div>
                                            <div class="theme-preview-product-meta">
                                                <span></span>
                                                <em></em>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <div class="theme-preview-boutique-row">
                                    @foreach (range(1, 2) as $i)
                                        <article class="theme-preview-product">
                                            <div class="theme-preview-product-media"></div>
                                            <div class="theme-preview-product-meta">
                                                <span></span>
                                                <em></em>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="theme-preview-meta">
                <div class="theme-preview-title-row">
                    <div class="theme-preview-name-wrap">
                        <span class="theme-preview-name">{{ $theme['label'] }}</span>
                        @if ($liveTheme === $key)
                            <span class="theme-preview-live-badge">Live</span>
                        @endif
                    </div>
                    <span class="theme-preview-check" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="theme-preview-check-icon">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
                <p class="theme-preview-blurb">{{ $theme['blurb'] }}</p>
            </div>
        </label>
    @endforeach
</div>
