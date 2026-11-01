@php
    $badge = match ($level) {
        'clear' => ['text' => __('Looks fine'), 'style' => 'background: var(--cov-green-bg); color: var(--cov-green); border: 1px solid var(--cov-green-border);'],
        'caution' => ['text' => __('Check before confirming'), 'style' => 'background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);'],
        'risk' => ['text' => __('Many cancellations'), 'style' => 'background: var(--cov-red-bg); color: var(--cov-red); border: 1px solid var(--cov-red-border);'],
        default => ['text' => __('No history yet'), 'style' => 'background: var(--cov-sku-bg); color: var(--cov-text-muted); border: 1px solid var(--cov-border);'],
    };
@endphp

<div class="cov-card" wire:key="order-fraud-{{ $orderId }}">
    <div class="cov-card-header">
        <strong style="font-size: 0.9375rem;">{{ __('Fraud check') }}</strong>
        <span class="cov-badge" style="{{ $badge['style'] }}">
            <span class="cov-badge-dot"></span>
            {{ $badge['text'] }}
        </span>
    </div>
    <div class="cov-card-body" style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.75rem;">
        @if($shop['phone'] === null)
            <div style="color: var(--cov-text-muted);">{{ __('This order has no mobile number to check.') }}</div>
        @else
            <div style="color: var(--cov-text-muted);">{{ __('Earlier orders from this number') }}</div>
            @if($shop['orders'] === 0)
                <div style="font-weight: 700; color: var(--cov-text-title);">{{ __('First order from this number') }}</div>
            @else
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem;">
                    <div class="cov-metric-card">
                        <div style="color: var(--cov-text-muted);">{{ __('Delivered') }}</div>
                        <div style="font-weight: 800; color: var(--cov-text-title);">{{ $shop['delivered'] }}</div>
                    </div>
                    <div class="cov-metric-card">
                        <div style="color: var(--cov-text-muted);">{{ __('Cancelled') }}</div>
                        <div style="font-weight: 800; color: var(--cov-text-title);">{{ $shop['cancelled'] }}</div>
                    </div>
                    <div class="cov-metric-card">
                        <div style="color: var(--cov-text-muted);">{{ __('Returned') }}</div>
                        <div style="font-weight: 800; color: var(--cov-text-title);">{{ $shop['returned'] }}</div>
                    </div>
                </div>
            @endif

            <div style="padding-top: 0.625rem; border-top: 1px solid var(--cov-border-subtle); display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="color: var(--cov-text-muted);">{{ $company }}</div>
                @if($courier === null)
                    <button type="button" wire:click="check" wire:loading.attr="disabled" wire:target="check" class="cov-copy-btn" style="align-self: flex-start;">
                        <span wire:loading.remove wire:target="check">{{ __('Check :name', ['name' => $company]) }}</span>
                        <span wire:loading wire:target="check">{{ __('Checking...') }}</span>
                    </button>
                @elseif($courier['state'] === 'missing')
                    <div style="color: var(--cov-text-muted);">{{ __('The outside check is off. Your own orders above still count.') }}</div>
                @elseif($courier['state'] === 'failed')
                    <div style="color: var(--cov-red);">{{ __('The courier did not answer. Try again.') }}</div>
                    <button type="button" wire:click="check" wire:target="check" class="cov-copy-btn" style="align-self: flex-start;">{{ __('Check :name', ['name' => $company]) }}</button>
                @else
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem;">
                        <div class="cov-metric-card">
                            <div style="color: var(--cov-text-muted);">{{ __('Parcels') }}</div>
                            <div style="font-weight: 800; color: var(--cov-text-title);">{{ $courier['total'] }}</div>
                        </div>
                        <div class="cov-metric-card">
                            <div style="color: var(--cov-text-muted);">{{ __('Delivered') }}</div>
                            <div style="font-weight: 800; color: var(--cov-text-title);">{{ $courier['delivered'] }}</div>
                        </div>
                        <div class="cov-metric-card">
                            <div style="color: var(--cov-text-muted);">{{ __('Cancelled') }}</div>
                            <div style="font-weight: 800; color: var(--cov-text-title);">{{ $courier['cancelled'] }}</div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <div style="color: var(--cov-text-muted);">{{ __('This does not cancel the order.') }}</div>
    </div>
</div>
