@php
    /** @var \App\Models\AbandonedCart|null $cart */
    $cart = $record ?? (isset($getRecord) ? $getRecord() : null);
    $currency = config('currency.symbol', '৳');
    $decimals = (int) config('currency.decimals', 2);
    $decimalSeparator = (string) config('currency.decimal_separator', '.');
    $thousandsSeparator = (string) config('currency.thousands_separator', ',');
    $money = fn (float $amount): string => $currency.' '.number_format($amount, $decimals, $decimalSeparator, $thousandsSeparator);

    $lines = is_array($cart?->items) ? $cart->items : [];
    $pieceCount = 0;

    foreach ($lines as $line) {
        if (is_array($line)) {
            $pieceCount += (int) ($line['quantity'] ?? 0);
        }
    }

    $splitName = function (string $name): array {
        $product = $name;
        $variant = null;

        if (str_contains($name, ' - ')) {
            [$product, $variant] = explode(' - ', $name, 2);
            $prefix = $product.' - ';

            if (str_starts_with((string) $variant, $prefix)) {
                $variant = substr((string) $variant, strlen($prefix));
            }

            if ($variant === '' || $variant === $product) {
                $variant = null;
            }
        }

        return [$product, $variant];
    };

    $status = $cart?->status;
    $step = match ($status) {
        \App\Enums\AbandonedCartStatus::Reminded => 2,
        \App\Enums\AbandonedCartStatus::Recovered => 3,
        default => 1,
    };
    $isClosed = $status === \App\Enums\AbandonedCartStatus::Closed;
    $canRecover = in_array($status, [\App\Enums\AbandonedCartStatus::Open, \App\Enums\AbandonedCartStatus::Reminded], true);
    $recoverUrl = ($cart && $canRecover) ? route('cart.recover', ['token' => $cart->token]) : null;
    $orderUrl = $cart?->recovered_order_id
        ? \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $cart->recovered_order_id])
        : null;
    $displayName = $cart?->name ?: ($cart?->phone ?: __('Abandoned cart'));
    $initials = mb_strtoupper(mb_substr($displayName, 0, 2));
    $statusStyle = match ($status) {
        \App\Enums\AbandonedCartStatus::Open => 'background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);',
        \App\Enums\AbandonedCartStatus::Reminded => 'background: rgba(59, 130, 246, 0.14); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);',
        \App\Enums\AbandonedCartStatus::Recovered => 'background: var(--cov-green-bg); color: var(--cov-green); border: 1px solid var(--cov-green-border);',
        default => 'background: var(--cov-sku-bg); color: var(--cov-text-muted); border: 1px solid var(--cov-border);',
    };
@endphp

@if($cart)
<style>
    .choco-cart-view {
        width: 100%;
        --cov-bg-card: #ffffff;
        --cov-bg-header: #f8fafc;
        --cov-bg-subtle: #f8fafc;
        --cov-border: #e2e8f0;
        --cov-border-subtle: #f1f5f9;
        --cov-text-title: #0f172a;
        --cov-text-body: #1e293b;
        --cov-text-muted: #64748b;
        --cov-text-subtle: #475569;
        --cov-sku-bg: rgba(148, 163, 184, 0.15);
        --cov-sku-text: #334155;
        --cov-amber: #d97706;
        --cov-amber-bg: rgba(245, 158, 11, 0.12);
        --cov-amber-border: rgba(245, 158, 11, 0.25);
        --cov-green: #059669;
        --cov-green-bg: rgba(16, 185, 129, 0.1);
        --cov-green-border: rgba(16, 185, 129, 0.25);
        --cov-red: #e11d48;
        --cov-red-bg: rgba(244, 63, 94, 0.1);
        --cov-red-border: rgba(244, 63, 94, 0.25);
        color: var(--cov-text-body);
    }
    .dark .choco-cart-view {
        --cov-bg-card: #111827;
        --cov-bg-header: rgba(255, 255, 255, 0.02);
        --cov-bg-subtle: rgba(255, 255, 255, 0.035);
        --cov-border: rgba(255, 255, 255, 0.08);
        --cov-border-subtle: rgba(255, 255, 255, 0.05);
        --cov-text-title: #f8fafc;
        --cov-text-body: #e2e8f0;
        --cov-text-muted: #94a3b8;
        --cov-text-subtle: #cbd5e1;
        --cov-sku-bg: rgba(255, 255, 255, 0.08);
        --cov-sku-text: #e2e8f0;
        --cov-amber: #fbbf24;
        --cov-amber-bg: rgba(245, 158, 11, 0.16);
        --cov-amber-border: rgba(245, 158, 11, 0.35);
        --cov-green: #34d399;
        --cov-green-bg: rgba(16, 185, 129, 0.14);
        --cov-green-border: rgba(16, 185, 129, 0.3);
        --cov-red: #fb7185;
        --cov-red-bg: rgba(244, 63, 94, 0.14);
        --cov-red-border: rgba(244, 63, 94, 0.3);
        color: var(--cov-text-body);
    }
    .choco-cart-view .cov-card { background: var(--cov-bg-card); border: 1px solid var(--cov-border); border-radius: 0.875rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08); overflow: hidden; }
    .choco-cart-view .cov-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--cov-border); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; background: var(--cov-bg-header); color: var(--cov-text-title); }
    .choco-cart-view .cov-card-body { padding: 1.25rem; }
    .choco-cart-view .cov-financial-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--cov-border); background: var(--cov-bg-header); }
    .choco-cart-view .cov-main-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; width: 100%; margin-top: 1.25rem; }
    @media (min-width: 1024px) {
        .choco-cart-view .cov-main-grid { grid-template-columns: minmax(0, 1.7fr) minmax(0, 1.2fr); }
    }
    .choco-cart-view .cov-col-left, .choco-cart-view .cov-col-right { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
    .choco-cart-view .cov-stepper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 1rem 0.5rem 0.25rem; width: 100%; }
    .choco-cart-view .cov-step-item { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; flex: 1; }
    .choco-cart-view .cov-step-circle { width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; }
    .choco-cart-view .cov-step-completed { background: #10b981; color: #fff; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); }
    .choco-cart-view .cov-step-active { background: #f59e0b; color: #fff; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25); }
    .choco-cart-view .cov-step-upcoming { background: var(--cov-bg-subtle); color: var(--cov-text-muted); border: 1px solid var(--cov-border); }
    .choco-cart-view .cov-step-label { font-size: 0.6875rem; font-weight: 600; margin-top: 0.375rem; text-align: center; }
    .choco-cart-view .cov-step-line { position: absolute; top: 2rem; left: 16%; right: 16%; height: 2px; background: var(--cov-border); z-index: 1; }
    .choco-cart-view .cov-step-line-fill { height: 100%; background: #10b981; }
    .choco-cart-view .cov-table-wrap { width: 100%; overflow-x: auto; }
    .choco-cart-view .cov-table { width: 100%; min-width: 32rem; border-collapse: collapse; text-align: left; }
    .choco-cart-view .cov-table th { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cov-text-muted); background: var(--cov-bg-subtle); padding: 0.625rem 0.875rem; border-bottom: 1px solid var(--cov-border); }
    .choco-cart-view .cov-table td { padding: 0.75rem 0.875rem; vertical-align: middle; border-bottom: 1px solid var(--cov-border-subtle); font-size: 0.8125rem; color: var(--cov-text-body); }
    .choco-cart-view .cov-table tr:hover td { background: var(--cov-bg-subtle); }
    .choco-cart-view .cov-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; line-height: 1.2; white-space: nowrap; }
    .choco-cart-view .cov-badge-dot { width: 0.375rem; height: 0.375rem; border-radius: 9999px; background: currentColor; }
    .choco-cart-view .cov-total-divider { border-top: 2px solid var(--cov-border); margin-top: 0.375rem; padding-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.9375rem; font-weight: 800; color: var(--cov-text-title); }
    .choco-cart-view .cov-copy-btn { align-self: flex-start; width: auto; background: transparent; border: 1px solid var(--cov-border); cursor: pointer; padding: 0.45rem 0.85rem; font-size: 0.75rem; font-weight: 700; color: var(--cov-text-title); border-radius: 0.5rem; }
    .choco-cart-view .cov-copy-btn:hover { color: var(--cov-amber); background: var(--cov-amber-bg); }
</style>

<div class="choco-cart-view">
    <div class="cov-card">
        <div class="cov-card-body">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid var(--cov-border-subtle);">
                <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0;">
                    <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.625rem; background: var(--cov-amber-bg); border: 1px solid var(--cov-amber-border); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">🛒</div>
                    <div style="min-width: 0;">
                        <div style="font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; color: var(--cov-text-title);">{{ $displayName }}</div>
                        <div style="font-size: 0.75rem; color: var(--cov-text-muted); margin-top: 0.15rem;">
                            {{ __('Last seen') }}: <strong style="color: var(--cov-text-title);">{{ $cart->last_activity_at?->translatedFormat('d M Y, h:i A') ?? '—' }}</strong>
                            &bull; {{ $pieceCount }} {{ __('Items') }}
                        </div>
                    </div>
                </div>
                <span class="cov-badge" style="{{ $statusStyle }}">
                    <span class="cov-badge-dot"></span>
                    {{ $status?->label() }}
                </span>
            </div>

            @if($isClosed)
                <div style="margin-top: 0.875rem; padding: 0.75rem 1rem; border-radius: 0.5rem; background: var(--cov-sku-bg); border: 1px solid var(--cov-border); color: var(--cov-text-muted); font-size: 0.75rem;">
                    <strong>{{ __('This cart is closed.') }}</strong>
                </div>
            @else
                <div style="padding-top: 0.75rem;">
                    <div class="cov-stepper">
                        @php $progress = match ($step) { 2 => 50, 3 => 100, default => 0 }; @endphp
                        <div class="cov-step-line"><div class="cov-step-line-fill" style="width: {{ $progress }}%;"></div></div>
                        @foreach([1 => \App\Enums\AbandonedCartStatus::Open, 2 => \App\Enums\AbandonedCartStatus::Reminded, 3 => \App\Enums\AbandonedCartStatus::Recovered] as $number => $stage)
                            <div class="cov-step-item">
                                <div class="cov-step-circle {{ $step > $number ? 'cov-step-completed' : ($step === $number ? 'cov-step-active' : 'cov-step-upcoming') }}">
                                    {{ $step > $number ? '✓' : $number }}
                                </div>
                                <span class="cov-step-label" style="color: {{ $step >= $number ? 'var(--cov-text-title)' : 'var(--cov-text-muted)' }};">{{ $stage->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="cov-main-grid">
        <div class="cov-col-left">
            <div class="cov-card">
                <div class="cov-card-header">
                    <strong style="font-size: 0.9375rem;">{{ __('Items') }}</strong>
                    <span class="cov-badge" style="background: var(--cov-amber-bg); color: var(--cov-amber);">{{ count($lines) }}</span>
                </div>
                <div class="cov-table-wrap">
                    <table class="cov-table">
                        <thead>
                            <tr>
                                <th>{{ __('Product Details') }}</th>
                                <th style="text-align: right;">{{ __('Unit Price') }}</th>
                                <th style="text-align: center;">{{ __('Qty') }}</th>
                                <th style="text-align: right;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                @continue(! is_array($line))
                                @php [$productName, $variantName] = $splitName((string) ($line['name'] ?? '')); @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--cov-text-title); line-height: 1.35;">{{ $productName }}</div>
                                        @if($variantName)
                                            <div style="margin-top: 0.2rem;">
                                                <span style="display: inline-block; font-size: 0.6875rem; font-weight: 600; padding: 0.1rem 0.4rem; border-radius: 0.25rem; background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);">{{ $variantName }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">{{ $money((float) ($line['unit_price'] ?? 0)) }}</td>
                                    <td style="text-align: center;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 1.625rem; height: 1.625rem; padding: 0 0.35rem; border-radius: 9999px; background: var(--cov-sku-bg); font-size: 0.75rem; font-weight: 700; color: var(--cov-text-title);">{{ (int) ($line['quantity'] ?? 0) }}</span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap; font-weight: 700; color: var(--cov-text-title);">{{ $money((float) ($line['line_total'] ?? 0)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--cov-text-muted);">—</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="cov-financial-footer">
                    <div style="display: flex; justify-content: flex-end;">
                        <div style="width: 100%; max-width: 18rem;">
                            <div class="cov-total-divider">
                                <span>{{ __('Total') }}</span>
                                <span style="font-size: 1.125rem; color: var(--cov-amber);">{{ $money((float) $cart->subtotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cov-col-right">
            <div class="cov-card">
                <div class="cov-card-header">
                    <strong style="font-size: 0.9375rem;">{{ __('Customer Profile') }}</strong>
                </div>
                <div class="cov-card-body" style="display: flex; flex-direction: column; gap: 0.875rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #ffffff; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; flex-shrink: 0;">{{ $initials }}</div>
                        <div style="font-weight: 800; font-size: 0.9375rem; color: var(--cov-text-title);">{{ $displayName }}</div>
                    </div>
                    <div style="padding-top: 0.625rem; border-top: 1px solid var(--cov-border-subtle); display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                            <span style="color: var(--cov-text-muted);">{{ __('Phone Number') }}</span>
                            <strong style="color: var(--cov-text-title);">{{ $cart->phone ?: '—' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                            <span style="color: var(--cov-text-muted);">{{ __('Email Address') }}</span>
                            <strong style="color: var(--cov-text-title); overflow-wrap: anywhere; text-align: right;">{{ $cart->email ?: '—' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                            <span style="color: var(--cov-text-muted);">{{ __('Reminded') }}</span>
                            <strong style="color: var(--cov-text-title); text-align: right;">{{ $cart->reminded_at?->translatedFormat('d M Y, h:i A') ?? __('Not reminded yet') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cov-card">
                <div class="cov-card-header">
                    <strong style="font-size: 0.9375rem;">{{ __('Cart link') }}</strong>
                </div>
                <div class="cov-card-body" style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.75rem; color: var(--cov-text-muted);">
                    @if($recoverUrl)
                        <button type="button" class="cov-copy-btn" onclick="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($recoverUrl) }}).then(function () { new FilamentNotification().title({{ \Illuminate\Support\Js::from(__('Cart link copied.')) }}).success().send() })">{{ __('Copy cart link') }}</button>
                    @elseif($orderUrl)
                        <div>{{ __('This cart became an order.') }}</div>
                        <a href="{{ $orderUrl }}" style="font-weight: 700; color: var(--cov-amber); text-decoration: none;">{{ __('Open the order') }}</a>
                    @else
                        <div>{{ __('This cart is closed.') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
