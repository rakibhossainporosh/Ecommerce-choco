@php
    /** @var \App\Models\Order $order */
    $order = $record ?? $order ?? (isset($getRecord) ? $getRecord() : null);
    $currency = config('currency.symbol', '৳');

    // Stepper logic
    $statusOrder = [
        'pending' => 1,
        'confirmed' => 2,
        'processing' => 3,
        'shipped' => 4,
        'delivered' => 5,
    ];
    $currentStep = $statusOrder[$order?->status?->value ?? 'pending'] ?? 1;
    $isCancelled = $order?->isCancelled() ?? false;
@endphp

@if($order)
<style>
    .choco-order-view {
        width: 100%;
        max-width: 100%;
        font-family: inherit;

        /* Light Mode Palette */
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

    /* Dark Mode Palette - 100% High Contrast & High Legibility */
    .dark .choco-order-view {
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

    /* Cards */
    .cov-card {
        background: var(--cov-bg-card);
        border: 1px solid var(--cov-border);
        border-radius: 0.875rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .cov-card-header {
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--cov-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--cov-bg-header);
        color: var(--cov-text-title);
    }

    .cov-card-body {
        padding: 1.25rem;
    }

    /* Financial Footer - Matches card background seamlessly */
    .cov-financial-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--cov-border);
        background: var(--cov-bg-header);
    }

    /* Sub-cards (Logistics) - Matches card background seamlessly */
    .cov-metric-card {
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: var(--cov-bg-subtle);
        border: 1px solid var(--cov-border);
    }

    /* Responsive Side-by-Side 2-Column Layout */
    .cov-main-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        width: 100%;
        margin-top: 1.25rem;
    }
    @media (min-width: 1024px) {
        .cov-main-grid {
            grid-template-columns: minmax(0, 1.7fr) minmax(0, 1.2fr);
            gap: 1.25rem;
        }
    }

    .cov-col-left, .cov-col-right {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
        width: 100%;
    }

    /* Stepper */
    .cov-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        padding: 1rem 0.5rem 0.25rem;
        width: 100%;
    }
    .cov-step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .cov-step-circle {
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        transition: all 0.2s ease;
    }
    .cov-step-completed {
        background-color: #10b981;
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .cov-step-active {
        background-color: #f59e0b;
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
    }
    .cov-step-upcoming {
        background-color: var(--cov-bg-subtle);
        color: var(--cov-text-muted);
        border: 1px solid var(--cov-border);
    }
    .cov-step-label {
        font-size: 0.6875rem;
        font-weight: 600;
        margin-top: 0.375rem;
        text-align: center;
    }
    .cov-step-line {
        position: absolute;
        top: 2rem;
        left: 10%;
        right: 10%;
        height: 2px;
        background-color: var(--cov-border);
        z-index: 1;
    }
    .cov-step-line-fill {
        height: 100%;
        background-color: #10b981;
    }

    /* Products Table */
    .cov-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .cov-table {
        width: 100%;
        min-width: 580px;
        border-collapse: collapse;
        text-align: left;
    }
    .cov-table th {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--cov-text-muted);
        background: var(--cov-bg-subtle);
        padding: 0.625rem 0.875rem;
        border-bottom: 1px solid var(--cov-border);
    }
    .cov-table td {
        padding: 0.75rem 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--cov-border-subtle);
        font-size: 0.8125rem;
        color: var(--cov-text-body);
    }
    .cov-table tr:hover td {
        background-color: var(--cov-bg-subtle);
    }

    /* Badges */
    .cov-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }
    .cov-badge-dot {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 9999px;
        background-color: currentColor;
    }

    /* Financial Rows */
    .cov-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8125rem;
        color: var(--cov-text-muted);
        padding: 0.25rem 0;
    }
    .cov-summary-row strong {
        color: var(--cov-text-title);
        font-weight: 600;
    }

    .cov-total-divider {
        border-top: 2px solid var(--cov-border);
        margin-top: 0.375rem;
        padding-top: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9375rem;
        font-weight: 800;
        color: var(--cov-text-title);
    }

    .cov-paid-divider {
        border-top: 1px dashed var(--cov-border);
        margin-top: 0.25rem;
        padding-top: 0.35rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: var(--cov-text-muted);
    }

    /* Timeline */
    .cov-timeline {
        position: relative;
        padding-left: 1.25rem;
    }
    .cov-timeline::before {
        content: '';
        position: absolute;
        left: 0.375rem;
        top: 0.5rem;
        bottom: 0.5rem;
        width: 2px;
        background: var(--cov-border);
    }
    .cov-timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }
    .cov-timeline-item:last-child {
        padding-bottom: 0;
    }
    .cov-timeline-dot {
        position: absolute;
        left: -1.25rem;
        top: 0.25rem;
        width: 0.625rem;
        height: 0.625rem;
        border-radius: 9999px;
        background: #f59e0b;
        border: 2px solid var(--cov-bg-card);
    }

    /* Micro button */
    .cov-copy-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0.15rem 0.25rem;
        font-size: 0.75rem;
        color: var(--cov-text-muted);
        border-radius: 0.25rem;
        transition: color 0.15s, background 0.15s;
    }
    .cov-copy-btn:hover {
        color: var(--cov-amber);
        background: var(--cov-amber-bg);
    }
</style>

<div class="choco-order-view">

    <!-- ======================================================== -->
    <!-- 1. TOP HEADER & LIFECYCLE PROGRESS BANNER (FULL WIDTH)   -->
    <!-- ======================================================== -->
    <div class="cov-card">
        <div class="cov-card-body">
            <!-- Header Row -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid var(--cov-border-subtle);">
                
                <!-- Order ID + Timestamps -->
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.625rem; background: var(--cov-amber-bg); border: 1px solid var(--cov-amber-border); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        📦
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; color: var(--cov-text-title);">
                                #{{ $order->order_number }}
                            </span>
                            <button type="button" 
                                    onclick="navigator.clipboard.writeText('{{ $order->order_number }}'); window.$wire?.dispatch('notify', { status: 'success', title: 'Order number copied!' })"
                                    class="cov-copy-btn" 
                                    title="Copy Order Number">
                                📋
                            </button>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--cov-text-muted); margin-top: 0.15rem;">
                            Placed: <strong style="color: var(--cov-text-title);">{{ $order->placed_at?->format('d M Y, h:i A') ?? $order->created_at->format('d M Y, h:i A') }}</strong>
                            &bull; {{ $order->items->count() }} item(s)
                            &bull; Currency: <strong style="color: var(--cov-text-title);">{{ $order->currency ?? 'BDT' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Status Pills -->
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    @php
                        $stBg = match($order->status->value) {
                            'pending' => 'background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);',
                            'confirmed' => 'background: rgba(59, 130, 246, 0.14); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);',
                            'processing' => 'background: rgba(99, 102, 241, 0.14); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);',
                            'shipped' => 'background: rgba(168, 85, 247, 0.14); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3);',
                            'delivered' => 'background: var(--cov-green-bg); color: var(--cov-green); border: 1px solid var(--cov-green-border);',
                            'cancelled' => 'background: var(--cov-red-bg); color: var(--cov-red); border: 1px solid var(--cov-red-border);',
                            default => 'background: var(--cov-sku-bg); color: var(--cov-text-muted); border: 1px solid var(--cov-border);',
                        };

                        $payBg = match($order->payment_status->value) {
                            'paid' => 'background: var(--cov-green-bg); color: var(--cov-green); border: 1px solid var(--cov-green-border);',
                            'pending' => 'background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);',
                            default => 'background: var(--cov-red-bg); color: var(--cov-red); border: 1px solid var(--cov-red-border);',
                        };
                    @endphp

                    <span class="cov-badge" style="{{ $stBg }}">
                        <span class="cov-badge-dot"></span>
                        Order: {{ $order->status->label() }}
                    </span>

                    <span class="cov-badge" style="{{ $payBg }}">
                        <span class="cov-badge-dot"></span>
                        Payment: {{ $order->payment_status->label() }}
                    </span>

                    <span class="cov-badge" style="background: var(--cov-sku-bg); color: var(--cov-text-body); border: 1px solid var(--cov-border);">
                        💳 {{ $order->payment_method?->label() ?? 'Cash on Delivery' }}
                    </span>
                </div>
            </div>

            <!-- Stepper Progress Bar -->
            @if($isCancelled)
                <div style="margin-top: 0.875rem; padding: 0.75rem 1rem; border-radius: 0.5rem; background: var(--cov-red-bg); border: 1px solid var(--cov-red-border); color: var(--cov-red); font-size: 0.75rem;">
                    <strong>Order Cancelled</strong>
                    @if(filled($order->cancellation_reason))
                        <span style="margin-left: 0.5rem;">Reason: {{ $order->cancellation_reason }}</span>
                    @endif
                    @if($order->cancelled_at)
                        <span style="margin-left: 0.5rem; opacity: 0.8;">({{ $order->cancelled_at->format('d M Y, h:i A') }})</span>
                    @endif
                </div>
            @else
                <div style="padding-top: 0.75rem;">
                    <div style="font-size: 0.6875rem; font-weight: 700; color: var(--cov-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                        Order Lifecycle
                    </div>
                    <div class="cov-stepper">
                        @php
                            $progressPct = match($currentStep) {
                                1 => 0,
                                2 => 25,
                                3 => 50,
                                4 => 75,
                                5 => 100,
                                default => 0,
                            };
                        @endphp
                        <div class="cov-step-line">
                            <div class="cov-step-line-fill" style="width: {{ $progressPct }}%;"></div>
                        </div>

                        <!-- Step 1: Placed -->
                        <div class="cov-step-item">
                            <div class="cov-step-circle {{ $currentStep > 1 ? 'cov-step-completed' : 'cov-step-active' }}">
                                @if($currentStep > 1) ✓ @else 1 @endif
                            </div>
                            <span class="cov-step-label" style="color: var(--cov-text-title);">Placed</span>
                        </div>

                        <!-- Step 2: Confirmed -->
                        <div class="cov-step-item">
                            <div class="cov-step-circle {{ $currentStep > 2 ? 'cov-step-completed' : ($currentStep === 2 ? 'cov-step-active' : 'cov-step-upcoming') }}">
                                @if($currentStep > 2) ✓ @else 2 @endif
                            </div>
                            <span class="cov-step-label" style="color: {{ $currentStep >= 2 ? 'var(--cov-text-title)' : 'var(--cov-text-muted)' }};">Confirmed</span>
                        </div>

                        <!-- Step 3: Processing -->
                        <div class="cov-step-item">
                            <div class="cov-step-circle {{ $currentStep > 3 ? 'cov-step-completed' : ($currentStep === 3 ? 'cov-step-active' : 'cov-step-upcoming') }}">
                                @if($currentStep > 3) ✓ @else 3 @endif
                            </div>
                            <span class="cov-step-label" style="color: {{ $currentStep >= 3 ? 'var(--cov-text-title)' : 'var(--cov-text-muted)' }};">Processing</span>
                        </div>

                        <!-- Step 4: Shipped -->
                        <div class="cov-step-item">
                            <div class="cov-step-circle {{ $currentStep > 4 ? 'cov-step-completed' : ($currentStep === 4 ? 'cov-step-active' : 'cov-step-upcoming') }}">
                                @if($currentStep > 4) ✓ @else 4 @endif
                            </div>
                            <span class="cov-step-label" style="color: {{ $currentStep >= 4 ? 'var(--cov-text-title)' : 'var(--cov-text-muted)' }};">Shipped</span>
                        </div>

                        <!-- Step 5: Delivered -->
                        <div class="cov-step-item">
                            <div class="cov-step-circle {{ $currentStep === 5 ? 'cov-step-completed' : 'cov-step-upcoming' }}">
                                @if($currentStep === 5) ✓ @else 5 @endif
                            </div>
                            <span class="cov-step-label" style="color: {{ $currentStep === 5 ? 'var(--cov-text-title)' : 'var(--cov-text-muted)' }};">Delivered</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 2. SIDE-BY-SIDE MAIN 2-COLUMN GRID (NO EMPTY GAPS)       -->
    <!-- ======================================================== -->
    <div class="cov-main-grid">

        <!-- ==================================================== -->
        <!-- LEFT COLUMN: PRODUCTS TABLE, TOTALS, LOGISTICS, AUDIT-->
        <!-- ==================================================== -->
        <div class="cov-col-left">

            <!-- CARD: Ordered Products -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">🛍️</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Ordered Products</strong>
                    </div>
                    <span class="cov-badge" style="background: var(--cov-amber-bg); color: var(--cov-amber);">
                        {{ $order->items->count() }} Line Item(s)
                    </span>
                </div>

                <!-- Product Table with Explicit Min-Widths to avoid column squishing -->
                <div class="cov-table-wrap">
                    <table class="cov-table">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;">Product Details</th>
                                <th style="min-width: 110px; text-align: center;">SKU</th>
                                <th style="min-width: 95px; text-align: right;">Unit Price</th>
                                <th style="min-width: 55px; text-align: center;">Qty</th>
                                <th style="min-width: 90px; text-align: right;">Discount</th>
                                <th style="min-width: 105px; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <!-- Product Name & Variant -->
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.625rem;">
                                            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; background: var(--cov-amber-bg); border: 1px solid var(--cov-amber-border); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                                🍫
                                            </div>
                                            <div style="min-width: 0;">
                                                <div style="font-weight: 700; color: var(--cov-text-title); line-height: 1.35;">
                                                    {{ $item->product_name }}
                                                </div>
                                                @if($item->variant_name)
                                                    <div style="margin-top: 0.2rem;">
                                                        <span style="display: inline-block; font-size: 0.6875rem; font-weight: 600; padding: 0.1rem 0.4rem; border-radius: 0.25rem; background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border);">
                                                            {{ $item->variant_name }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- SKU Chip -->
                                    <td style="text-align: center; white-space: nowrap;">
                                        <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.6875rem; font-weight: 600; padding: 0.2rem 0.45rem; border-radius: 0.3rem; background: var(--cov-sku-bg); color: var(--cov-sku-text); border: 1px solid var(--cov-border);">
                                            {{ $item->sku }}
                                        </span>
                                    </td>

                                    <!-- Unit Price -->
                                    <td style="text-align: right; white-space: nowrap; color: var(--cov-text-subtle);">
                                        {{ $currency }} {{ number_format((float) $item->unit_price, 2) }}
                                    </td>

                                    <!-- Quantity -->
                                    <td style="text-align: center; white-space: nowrap;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.625rem; height: 1.625rem; border-radius: 9999px; background: var(--cov-sku-bg); font-size: 0.75rem; font-weight: 700; color: var(--cov-text-title);">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>

                                    <!-- Discount -->
                                    <td style="text-align: right; white-space: nowrap;">
                                        @if((float) $item->discount_amount > 0)
                                            <span style="color: var(--cov-green); font-weight: 600;">
                                                - {{ $currency }} {{ number_format((float) $item->discount_amount, 2) }}
                                            </span>
                                        @else
                                            <span style="color: var(--cov-text-muted);">—</span>
                                        @endif
                                    </td>

                                    <!-- Line Total -->
                                    <td style="text-align: right; white-space: nowrap; font-weight: 700; color: var(--cov-text-title);">
                                        {{ $currency }} {{ number_format((float) $item->line_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Financial Breakdown Section - Consistent Background -->
                <div class="cov-financial-footer">
                    <div style="display: flex; flex-direction: column; align-items: flex-end; width: 100%;">
                        <div style="width: 100%; max-width: 22rem; display: flex; flex-direction: column; gap: 0.35rem;">
                            
                            <!-- Subtotal -->
                            <div class="cov-summary-row">
                                <span>Items Subtotal:</span>
                                <strong>{{ $currency }} {{ number_format((float) $order->subtotal, 2) }}</strong>
                            </div>

                            <!-- Discount -->
                            @if((float) $order->discount_amount > 0)
                                <div class="cov-summary-row" style="color: var(--cov-green);">
                                    <span>Discount Applied:</span>
                                    <strong style="color: var(--cov-green);">- {{ $currency }} {{ number_format((float) $order->discount_amount, 2) }}</strong>
                                </div>
                            @endif

                            <!-- Shipping -->
                            <div class="cov-summary-row">
                                <span>Delivery / Shipping Fee:</span>
                                <strong>{{ $currency }} {{ number_format((float) $order->shipping_amount, 2) }}</strong>
                            </div>

                            <!-- Grand Total -->
                            <div class="cov-total-divider">
                                <span>Grand Total:</span>
                                <span style="font-size: 1.125rem; color: var(--cov-amber);">
                                    {{ $currency }} {{ number_format((float) $order->grand_total, 2) }}
                                </span>
                            </div>

                            <!-- Amount Paid -->
                            <div class="cov-paid-divider">
                                <span>Amount Paid:</span>
                                <span style="font-weight: 700; color: var(--cov-green);">
                                    {{ $currency }} {{ number_format((float) $order->total_paid, 2) }}
                                </span>
                            </div>

                            <!-- Due Amount / Balance -->
                            @if($order->due_amount > 0)
                                <div style="margin-top: 0.25rem; padding: 0.4rem 0.625rem; border-radius: 0.375rem; background: var(--cov-red-bg); border: 1px solid var(--cov-red-border); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; font-weight: 700; color: var(--cov-red);">
                                    <span>Due on Delivery:</span>
                                    <span>{{ $currency }} {{ number_format((float) $order->due_amount, 2) }}</span>
                                </div>
                            @else
                                <div style="margin-top: 0.25rem; padding: 0.4rem 0.625rem; border-radius: 0.375rem; background: var(--cov-green-bg); border: 1px solid var(--cov-green-border); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; font-weight: 700; color: var(--cov-green);">
                                    <span>Payment Status:</span>
                                    <span>Paid in Full ✓</span>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD: Shipment & Logistics Tracking -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">🚚</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Shipment & Logistics Tracking</strong>
                    </div>
                    @if($order->latestShipment?->status)
                        <span class="cov-badge" style="background: rgba(168, 85, 247, 0.14); color: #c084fc;">
                            {{ $order->latestShipment->status->label() }}
                        </span>
                    @else
                        <span class="cov-badge" style="background: var(--cov-sku-bg); color: var(--cov-text-muted);">
                            Not Dispatched
                        </span>
                    @endif
                </div>

                <div class="cov-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
                        <!-- Carrier -->
                        <div class="cov-metric-card">
                            <span style="font-size: 0.6875rem; font-weight: 700; color: var(--cov-text-muted); text-transform: uppercase;">Carrier Partner</span>
                            <div style="font-weight: 700; font-size: 0.8125rem; margin-top: 0.25rem; color: var(--cov-text-title);">
                                {{ $order->latestShipment?->provider?->label() ?? 'Pending Assignment' }}
                            </div>
                        </div>

                        <!-- Consignment Tracking -->
                        <div class="cov-metric-card">
                            <span style="font-size: 0.6875rem; font-weight: 700; color: var(--cov-text-muted); text-transform: uppercase;">Consignment Tracking</span>
                            <div style="font-size: 0.8125rem; margin-top: 0.25rem;">
                                @if($order->latestShipment?->tracking_code)
                                    <span style="font-family: monospace; font-size: 0.75rem; background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border); padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-weight: 600;">
                                        {{ $order->latestShipment->tracking_code }}
                                    </span>
                                    <button type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $order->latestShipment->tracking_code }}')"
                                            class="cov-copy-btn" 
                                            title="Copy Tracking">
                                        📋
                                    </button>
                                @else
                                    <span style="color: var(--cov-text-muted); font-style: italic; font-size: 0.75rem;">No tracking code yet</span>
                                @endif
                            </div>
                        </div>

                        <!-- Online Tracking Link -->
                        <div class="cov-metric-card">
                            <span style="font-size: 0.6875rem; font-weight: 700; color: var(--cov-text-muted); text-transform: uppercase;">Online Tracking</span>
                            <div style="font-size: 0.8125rem; margin-top: 0.25rem;">
                                @if($order->latestShipment?->getTrackingUrl())
                                    <a href="{{ $order->latestShipment->getTrackingUrl() }}" target="_blank"
                                       style="font-size: 0.75rem; font-weight: 700; color: #60a5fa; text-decoration: underline;">
                                        Track Parcel ↗
                                    </a>
                                @else
                                    <span style="color: var(--cov-text-muted); font-style: italic; font-size: 0.75rem;">Unavailable</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD: Status History & Audit Trail -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">⏱️</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Status History & Audit Trail</strong>
                    </div>
                    <span style="font-size: 0.6875rem; color: var(--cov-text-muted);">
                        {{ $order->statusHistories->count() }} Record(s)
                    </span>
                </div>

                <div class="cov-card-body">
                    @if($order->statusHistories->isNotEmpty())
                        <div class="cov-timeline">
                            @foreach($order->statusHistories as $history)
                                <div class="cov-timeline-item">
                                    <div class="cov-timeline-dot"></div>
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.35rem; font-size: 0.75rem;">
                                        <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                            <span style="padding: 0.15rem 0.4rem; border-radius: 0.25rem; background: var(--cov-sku-bg); color: var(--cov-text-body); font-weight: 600; border: 1px solid var(--cov-border);">
                                                {{ $history->from_status?->label() ?? (is_string($history->from_status) ? $history->from_status : '—') }}
                                            </span>
                                            <span style="color: var(--cov-text-muted);">&rarr;</span>
                                            <span style="padding: 0.15rem 0.4rem; border-radius: 0.25rem; background: var(--cov-amber-bg); color: var(--cov-amber); border: 1px solid var(--cov-amber-border); font-weight: 700;">
                                                {{ $history->to_status?->label() ?? (is_string($history->to_status) ? $history->to_status : '—') }}
                                            </span>
                                            <span style="color: var(--cov-text-muted);">&bull;</span>
                                            <span style="color: var(--cov-text-muted);">
                                                By <strong style="color: var(--cov-text-title);">{{ $history->changedBy?->name ?? 'System' }}</strong>
                                            </span>
                                        </div>
                                        <div style="color: var(--cov-text-muted); font-size: 0.6875rem; white-space: nowrap;">
                                            {{ $history->created_at->format('d M Y, h:i A') }}
                                        </div>
                                    </div>
                                    @if(filled($history->reason))
                                        <div style="font-size: 0.75rem; color: var(--cov-text-subtle); margin-top: 0.35rem; padding-left: 0.5rem; border-left: 2px solid var(--cov-amber); font-style: italic;">
                                            "{{ $history->reason }}"
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 1.5rem 0; font-size: 0.75rem; color: var(--cov-text-muted); font-style: italic;">
                            No status transitions recorded for this order yet.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- ==================================================== -->
        <!-- RIGHT COLUMN: CUSTOMER, ADDRESSES & METADATA         -->
        <!-- ==================================================== -->
        <div class="cov-col-right">

            <!-- CARD: Customer Information -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">👤</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Customer Profile</strong>
                    </div>
                    <span class="cov-badge" style="background: rgba(59, 130, 246, 0.14); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                        Verified Customer
                    </span>
                </div>

                <div class="cov-card-body" style="display: flex; flex-direction: column; gap: 0.875rem;">
                    <!-- Avatar + Name -->
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #ffffff; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; flex-shrink: 0;">
                            {{ strtoupper(substr($order->customer_name, 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight: 800; font-size: 0.9375rem; color: var(--cov-text-title);">
                                {{ $order->customer_name }}
                            </div>
                            <div style="font-size: 0.6875rem; color: var(--cov-text-muted);">
                                ID: #{{ $order->customer_id ?? $order->user_id ?? 'Guest' }}
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div style="padding-top: 0.625rem; border-top: 1px solid var(--cov-border-subtle); display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.75rem;">
                        <!-- Phone -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--cov-text-muted);">Phone:</span>
                            <div style="font-weight: 700; color: var(--cov-text-title); display: flex; align-items: center; gap: 0.25rem;">
                                <a href="tel:{{ $order->customer_phone }}" style="color: inherit; text-decoration: none;">
                                    {{ $order->customer_phone }}
                                </a>
                                <button type="button" 
                                        onclick="navigator.clipboard.writeText('{{ $order->customer_phone }}'); window.$wire?.dispatch('notify', { status: 'success', title: 'Phone copied!' })"
                                        class="cov-copy-btn" 
                                        title="Copy Phone">
                                    📋
                                </button>
                            </div>
                        </div>

                        <!-- Email -->
                        @if($order->customer_email)
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--cov-text-muted);">Email:</span>
                                <div style="font-weight: 700; color: var(--cov-text-title); display: flex; align-items: center; gap: 0.25rem; max-width: 180px;">
                                    <a href="mailto:{{ $order->customer_email }}" style="color: inherit; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $order->customer_email }}
                                    </a>
                                    <button type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $order->customer_email }}'); window.$wire?.dispatch('notify', { status: 'success', title: 'Email copied!' })"
                                            class="cov-copy-btn" 
                                            title="Copy Email">
                                        📋
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Customer Note -->
                    @if(filled($order->customer_note))
                        <div style="padding: 0.625rem 0.75rem; border-radius: 0.5rem; background: var(--cov-amber-bg); border: 1px solid var(--cov-amber-border); font-size: 0.75rem;">
                            <div style="font-weight: 700; color: var(--cov-amber); display: flex; align-items: center; gap: 0.25rem;">
                                💬 Customer Note:
                            </div>
                            <div style="color: var(--cov-text-subtle); font-style: italic; margin-top: 0.25rem; line-height: 1.4;">
                                "{{ $order->customer_note }}"
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CARD: Shipping Address -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">📍</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Shipping Address</strong>
                    </div>
                </div>

                <div class="cov-card-body" style="font-size: 0.75rem; display: flex; flex-direction: column; gap: 0.35rem;">
                    <div style="font-weight: 800; font-size: 0.875rem; color: var(--cov-text-title);">
                        {{ $order->customer_name }}
                    </div>
                    <div style="color: var(--cov-text-muted);">
                        📞 {{ $order->customer_phone }}
                    </div>
                    <div style="margin-top: 0.35rem; padding-top: 0.5rem; border-top: 1px solid var(--cov-border-subtle); color: var(--cov-text-subtle); line-height: 1.5;">
                        <div>{{ $order->shipping_address_line }}</div>
                        @if($order->shipping_area)
                            <div>Area: {{ $order->shipping_area }}</div>
                        @endif
                        <div>{{ $order->shipping_city }} @if($order->shipping_postcode) - {{ $order->shipping_postcode }} @endif</div>
                        <div style="font-weight: 600; color: var(--cov-text-title); margin-top: 0.15rem;">{{ $order->shipping_country ?? 'Bangladesh' }}</div>
                    </div>
                </div>
            </div>

            <!-- CARD: Billing Address -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">💳</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Billing Address</strong>
                    </div>
                </div>

                <div class="cov-card-body">
                    @if($order->billing_same_as_shipping)
                        <div style="padding: 0.625rem 0.875rem; border-radius: 0.5rem; background: var(--cov-green-bg); border: 1px solid var(--cov-green-border); display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cov-green);">
                            <span style="width: 1.125rem; height: 1.125rem; border-radius: 9999px; background: var(--cov-green); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.6875rem;">✓</span>
                            <span>Same as shipping address</span>
                        </div>
                    @else
                        <div style="font-size: 0.75rem; color: var(--cov-text-subtle); line-height: 1.5;">
                            <div style="font-weight: 800; font-size: 0.8125rem; color: var(--cov-text-title);">{{ $order->billing_address_line }}</div>
                            @if($order->billing_area)
                                <div>{{ $order->billing_area }}</div>
                            @endif
                            <div>{{ $order->billing_city }} @if($order->billing_postcode) - {{ $order->billing_postcode }} @endif</div>
                            <div style="font-weight: 600; color: var(--cov-text-title); margin-top: 0.15rem;">{{ $order->billing_country ?? 'Bangladesh' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CARD: Order Details & Metadata -->
            <div class="cov-card">
                <div class="cov-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">ℹ️</span>
                        <strong style="font-size: 0.9375rem; color: var(--cov-text-title);">Order Details</strong>
                    </div>
                </div>

                <div class="cov-card-body" style="font-size: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--cov-text-muted);">Payment Method:</span>
                        <strong style="color: var(--cov-text-title);">
                            {{ $order->payment_method?->label() ?? 'Cash on Delivery' }}
                        </strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--cov-text-muted);">Currency:</span>
                        <strong style="color: var(--cov-text-title);">
                            {{ $order->currency ?? 'BDT' }}
                        </strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--cov-text-muted);">Created At:</span>
                        <span style="color: var(--cov-text-title);">
                            {{ $order->created_at->format('d M Y, h:i A') }}
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--cov-text-muted);">Placed At:</span>
                        <span style="color: var(--cov-text-title);">
                            {{ $order->placed_at?->format('d M Y, h:i A') ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endif
