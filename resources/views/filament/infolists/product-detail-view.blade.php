@php
    /** @var \App\Models\Product|null $product */
    $product = $record ?? (isset($getRecord) ? $getRecord() : null);
    if ($product) {
        $product->loadMissing(['brand', 'categories', 'variants.inventory', 'defaultVariant', 'media']);
    }

    $primaryMedia = $product
        ? ($product->getResolvedPrimaryMedia() ?? $product->defaultVariant?->getResolvedPrimaryMedia())
        : null;
    $imageUrl = $primaryMedia?->url();

    $variants = $product?->variants ?? collect();
    $defaultVariant = $product?->defaultVariant;
    $min = $variants->min('selling_price');
    $max = $variants->max('selling_price');
    if ($min === null) {
        $priceLabel = '—';
    } elseif ((float) $min === (float) $max) {
        $priceLabel = '৳ '.number_format((float) $min, 2);
    } else {
        $priceLabel = '৳ '.number_format((float) $min, 2).' - ৳ '.number_format((float) $max, 2);
    }

    $compareAt = $defaultVariant?->compare_at_price;
    $compareLabel = $compareAt !== null ? '৳ '.number_format((float) $compareAt, 2) : null;

    $stockTotal = (int) $variants->sum(fn ($variant) => $variant->inventory?->quantity ?? 0);
    $hasLowStock = $variants->contains(fn ($variant) => $variant->inventory && $variant->inventory->isLowStock());
    if ($variants->isEmpty()) {
        $stockStatus = __('No Variants');
        $stockTone = 'muted';
    } elseif ($stockTotal <= 0) {
        $stockStatus = __('Out of Stock');
        $stockTone = 'danger';
    } elseif ($hasLowStock) {
        $stockStatus = __('Low Stock');
        $stockTone = 'warning';
    } else {
        $stockStatus = __('In Stock');
        $stockTone = 'success';
    }

    $defaultSku = $defaultVariant?->sku;
    $hasSummary = filled($product?->short_description);
    $hasDescription = filled($product?->description);
    $hasMetaTitle = filled($product?->meta_title);
    $hasMetaDescription = filled($product?->meta_description);
    $seoMissing = ! $hasMetaTitle && ! $hasMetaDescription;
@endphp

@if ($product)
    <div class="product-detail-view">
        <section class="pdv-hero">
            <div class="pdv-photo">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="pdv-photo-img" />
                @else
                    <div class="pdv-photo-empty">{{ __('No photo') }}</div>
                @endif
            </div>

            <div class="pdv-identity">
                <p class="pdv-kicker">{{ __('Product Overview') }}</p>
                <h2 class="pdv-title">{{ $product->name }}</h2>

                <div class="pdv-badges">
                    @if ($defaultSku)
                        <span class="pdv-badge pdv-badge-mono" title="{{ __('Default SKU') }}">{{ $defaultSku }}</span>
                    @endif
                    @if ($product->slug)
                        <span class="pdv-badge pdv-badge-muted" title="{{ __('URL Slug') }}">{{ $product->slug }}</span>
                    @endif
                    <span class="pdv-badge {{ $product->is_active ? 'pdv-badge-success' : 'pdv-badge-muted' }}">
                        {{ $product->is_active ? __('Active') : __('Draft / Inactive') }}
                    </span>
                    @if ($product->is_featured)
                        <span class="pdv-badge pdv-badge-warning">{{ __('Featured') }}</span>
                    @endif
                    <span class="pdv-badge pdv-badge-{{ $stockTone }}">{{ $stockStatus }}</span>
                </div>

                <dl class="pdv-meta-list">
                    <div>
                        <dt>{{ __('Brand') }}</dt>
                        <dd>{{ $product->brand?->name ?: __('None') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Categories') }}</dt>
                        <dd>
                            @forelse ($product->categories as $category)
                                <span class="pdv-chip">{{ $category->name }}</span>
                            @empty
                                {{ __('None') }}
                            @endforelse
                        </dd>
                    </div>
                    <div class="pdv-meta-center">
                        <dt>{{ __('Variants') }}</dt>
                        <dd>{{ $variants->count() }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="pdv-kpis" aria-label="{{ __('Commerce Snapshot') }}">
            <div class="pdv-kpi">
                <span class="pdv-kpi-label">{{ __('Price') }}</span>
                <span class="pdv-kpi-value">{{ $priceLabel }}</span>
                @if ($compareLabel)
                    <span class="pdv-kpi-hint">{{ __('Compare at') }} {{ $compareLabel }}</span>
                @endif
            </div>
            <div class="pdv-kpi">
                <span class="pdv-kpi-label">{{ __('Total Stock') }}</span>
                <span class="pdv-kpi-value">{{ $stockTotal }}</span>
                <span class="pdv-kpi-hint pdv-tone-{{ $stockTone }}">{{ $stockStatus }}</span>
            </div>
            <div class="pdv-kpi pdv-kpi-center">
                <span class="pdv-kpi-label">{{ __('Variants') }}</span>
                <span class="pdv-kpi-value">{{ $variants->count() }}</span>
            </div>
            <div class="pdv-kpi">
                <span class="pdv-kpi-label">{{ __('Catalog status') }}</span>
                <span class="pdv-kpi-value">{{ $product->is_active ? __('Active') : __('Draft') }}</span>
                @if ($product->is_featured)
                    <span class="pdv-kpi-hint">{{ __('Featured') }}</span>
                @endif
            </div>
        </section>

        <section class="pdv-card">
            <div class="pdv-card-head">
                <h3 class="pdv-card-title">{{ __('Content & SEO') }}</h3>
            </div>

            <div class="pdv-content-grid">
                <div class="pdv-content-block">
                    <span class="pdv-field-label">{{ __('Short Summary') }}</span>
                    @if ($hasSummary)
                        <p class="pdv-field-value">{{ $product->short_description }}</p>
                    @else
                        <p class="pdv-empty">{{ __('No short summary yet. Add one in Edit.') }}</p>
                    @endif
                </div>

                <div class="pdv-content-block">
                    <span class="pdv-field-label">{{ __('Detailed Description') }}</span>
                    @if ($hasDescription)
                        <div class="pdv-field-value pdv-prose">{!! nl2br(e($product->description)) !!}</div>
                    @else
                        <p class="pdv-empty">{{ __('No detailed description yet. Add one in Edit.') }}</p>
                    @endif
                </div>
            </div>

            <div class="pdv-seo">
                <span class="pdv-field-label">{{ __('Search Engine Optimization (SEO)') }}</span>
                @if ($seoMissing)
                    <div class="pdv-empty-box" role="status">
                        <p class="pdv-empty-title">{{ __('SEO fields not set') }}</p>
                        <p class="pdv-empty">{{ __('Meta title and description are empty. Add them in Edit to improve search visibility.') }}</p>
                    </div>
                @else
                    <div class="pdv-seo-grid">
                        <div class="pdv-content-block">
                            <span class="pdv-field-label">{{ __('Meta Title (SEO)') }}</span>
                            <p class="pdv-field-value">{{ $hasMetaTitle ? $product->meta_title : __('Not set') }}</p>
                        </div>
                        <div class="pdv-content-block">
                            <span class="pdv-field-label">{{ __('Meta Description (SEO)') }}</span>
                            <p class="pdv-field-value">{{ $hasMetaDescription ? $product->meta_description : __('Not set') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="pdv-footer-meta" aria-label="{{ __('Record Meta') }}">
            <div class="pdv-stamp">
                <span class="pdv-stamp-label">{{ __('Created') }}</span>
                <p class="pdv-stamp-value">
                    {{ $product->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y') ?? '—' }}
                </p>
                <p class="pdv-stamp-time">
                    {{ $product->created_at?->timezone(config('app.timezone'))->translatedFormat('h:i A') ?? '' }}
                </p>
            </div>
            <div class="pdv-stamp">
                <span class="pdv-stamp-label">{{ __('Last updated') }}</span>
                <p class="pdv-stamp-value">
                    {{ $product->updated_at?->timezone(config('app.timezone'))->translatedFormat('d M Y') ?? '—' }}
                </p>
                <p class="pdv-stamp-time">
                    {{ $product->updated_at?->timezone(config('app.timezone'))->translatedFormat('h:i A') ?? '' }}
                </p>
            </div>
        </section>
    </div>
@endif
