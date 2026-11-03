<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Packing slip') }} #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 24px;
            line-height: 1.45;
        }
        .action-bar {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-primary { background: #d97706; color: #fff; }
        .slip-card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
            padding: 32px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #d97706;
        }
        .brand p { font-size: 12px; color: #64748b; margin-top: 4px; }
        .slip-meta { text-align: right; }
        .slip-meta h2 {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
        }
        .order-num { font-size: 16px; font-weight: 700; margin-top: 4px; }
        .meta-date { font-size: 12px; color: #64748b; margin-top: 4px; }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .info-box h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .info-box .name { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
        .info-box .detail { font-size: 12px; color: #334155; margin-bottom: 2px; }
        .gift-box {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px dashed #f59e0b;
            background: #fffbeb;
            font-size: 13px;
        }
        .gift-box strong { color: #b45309; display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
            padding: 10px 12px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #334155;
            vertical-align: top;
        }
        table.items-table .text-center { text-align: center; }
        table.items-table .item-title { font-weight: 700; color: #0f172a; }
        table.items-table .item-sub { color: #64748b; font-size: 11px; margin-top: 2px; }
        .pick-box {
            width: 18px;
            height: 18px;
            border: 2px solid #94a3b8;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
        }
        .footer-note {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .admin-note {
            margin-top: 16px;
            padding: 10px 12px;
            background: #f8fafc;
            border-left: 3px solid #64748b;
            font-size: 12px;
            color: #475569;
        }
        @media print {
            body { background: none; padding: 0; }
            .action-bar { display: none; }
            .slip-card { box-shadow: none; border: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="action-bar">
        <a href="javascript:window.close()" class="btn btn-secondary">&larr; {{ __('Close') }}</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ {{ __('Print packing slip') }}</button>
    </div>

    <div class="slip-card">
        <div class="header">
            <div class="brand">
                <h1>{{ \App\Models\Setting::get('site_name', config('app.name', 'Nobo Shop')) }}</h1>
                <p>{{ \App\Support\LocalizedContent::text(\App\Models\Setting::get('contact_address'), \App\Support\LocalizedContent::settingDefaults()['contact_address']) }}</p>
            </div>
            <div class="slip-meta">
                <h2>{{ __('Packing slip') }}</h2>
                <div class="order-num">#{{ $order->order_number }}</div>
                <div class="meta-date">{{ __('Date') }}: {{ $order->placed_at?->translatedFormat('d M, Y h:i A') ?? $order->created_at->translatedFormat('d M, Y h:i A') }}</div>
                <div class="meta-date">{{ __('Status') }}: {{ $order->status->label() }} · {{ $order->payment_method?->label() ?? __('COD') }}</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="info-box">
                <h3>{{ __('Ship to') }}</h3>
                <div class="name">{{ $order->customer_name }}</div>
                <div class="detail"><strong>{{ __('Phone') }}:</strong> {{ $order->customer_phone }}</div>
                <div class="detail" style="margin-top: 6px;">
                    {{ $order->shipping_address_line }}<br>
                    @if($order->shipping_area) {{ $order->shipping_area }}, @endif
                    {{ $order->shipping_city }}
                    @if($order->shipping_postcode) - {{ $order->shipping_postcode }} @endif<br>
                    {{ $order->shipping_country ?? __('Bangladesh') }}
                </div>
            </div>
            <div class="info-box">
                <h3>{{ __('Fulfillment') }}</h3>
                <div class="detail"><strong>{{ __('Items') }}:</strong> {{ $order->items->sum('quantity') }}</div>
                <div class="detail"><strong>{{ __('Lines') }}:</strong> {{ $order->items->count() }}</div>
                @if($order->latestShipment?->tracking_number)
                    <div class="detail"><strong>{{ __('Tracking') }}:</strong> {{ $order->latestShipment->tracking_number }}</div>
                @endif
                @if($order->tagList() !== [])
                    <div class="detail" style="margin-top: 6px;">
                        <strong>{{ __('Tags') }}:</strong>
                        {{ implode(', ', $order->tagList()) }}
                    </div>
                @endif
            </div>
        </div>

        @if(filled($order->gift_message))
            <div class="gift-box">
                <strong>{{ __('Gift message') }}</strong>
                {{ $order->gift_message }}
            </div>
        @endif

        @if(filled($order->customer_note))
            <div class="gift-box" style="border-color: #94a3b8; background: #f8fafc;">
                <strong style="color: #475569;">{{ __('Customer note') }}</strong>
                {{ $order->customer_note }}
            </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">{{ __('Pick') }}</th>
                    <th>{{ __('Item') }}</th>
                    <th class="text-center" style="width: 72px;">{{ __('Qty') }}</th>
                    <th style="width: 120px;">{{ __('SKU') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="text-center"><span class="pick-box"></span></td>
                        <td>
                            <div class="item-title">{{ $item->product_name }}</div>
                            @if(filled($item->variant_name))
                                <div class="item-sub">{{ $item->variant_name }}</div>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight: 800; font-size: 16px;">{{ $item->quantity }}</td>
                        <td>{{ $item->sku ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(filled($order->admin_note))
            <div class="admin-note">
                <strong>{{ __('Warehouse note') }}:</strong> {{ $order->admin_note }}
            </div>
        @endif

        <div class="footer-note">
            <span>{{ __('No prices on packing slip — warehouse use only.') }}</span>
            <span>{{ __('Packed by') }}: ____________ &nbsp; {{ __('Date') }}: ____________</span>
        </div>
    </div>
</body>
</html>
