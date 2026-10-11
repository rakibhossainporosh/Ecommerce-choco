<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }} - Choco Ecommerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            padding: 30px 15px;
            font-size: 13px;
            line-height: 1.5;
        }
        .invoice-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 40px;
            border: 1px solid #e2e8f0;
        }
        .action-bar {
            max-width: 820px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background-color: #d97706;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #b45309;
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 700;
            color: #92400e;
            letter-spacing: -0.5px;
        }
        .brand p {
            color: #64748b;
            font-size: 12px;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-meta h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-meta .order-num {
            font-size: 14px;
            font-weight: 600;
            color: #d97706;
            margin-top: 2px;
        }
        .invoice-meta .meta-date {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .info-box h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .info-box .name {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .info-box .detail {
            color: #334155;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-confirmed { background-color: #e0f2fe; color: #0369a1; }
        .badge-processing { background-color: #fef3c7; color: #b45309; }
        .badge-shipped { background-color: #f3e8ff; color: #7e22ce; }
        .badge-delivered { background-color: #dcfce7; color: #15803d; }
        .badge-cancelled { background-color: #fee2e2; color: #b91c1c; }
        .badge-paid { background-color: #dcfce7; color: #15803d; }
        .badge-unpaid { background-color: #fee2e2; color: #b91c1c; }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 10px 12px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
            color: #334155;
        }
        table.items-table .text-right {
            text-align: right;
        }
        table.items-table .text-center {
            text-align: center;
        }
        table.items-table .item-title {
            font-weight: 600;
            color: #0f172a;
        }
        table.items-table .item-sub {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 12px;
            color: #475569;
        }
        .totals-table td.amount {
            text-align: right;
            font-weight: 500;
            color: #1e293b;
        }
        .totals-table tr.total-row td {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px;
        }
        .totals-table tr.total-row td.amount {
            color: #d97706;
        }
        .footer-note {
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
            color: #64748b;
            font-size: 11px;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .action-bar {
                display: none;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="javascript:window.close()" class="btn btn-secondary">
            &larr; Close Window
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Print Invoice / Packing Slip
        </button>
    </div>

    <div class="invoice-card">
        <!-- Header -->
        <div class="header">
            <div class="brand">
                <h1>Choco Ecommerce</h1>
                <p>Premium Artisan Chocolates</p>
                <p style="margin-top: 6px;">Dhaka, Bangladesh</p>
                <p>Support: support@chocoecommerce.com</p>
            </div>
            <div class="invoice-meta">
                <h2>Invoice / Slip</h2>
                <div class="order-num">#{{ $order->order_number }}</div>
                <div class="meta-date">Date: {{ $order->placed_at?->format('d M, Y h:i A') ?? $order->created_at->format('d M, Y h:i A') }}</div>
                <div style="margin-top: 8px;">
                    <span class="status-badge badge-{{ $order->status->value }}">{{ $order->status->label() }}</span>
                    <span class="status-badge badge-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span>
                </div>
            </div>
        </div>

        <!-- Addresses & Payment Info -->
        <div class="grid-2">
            <div class="info-box">
                <h3>Delivery Address</h3>
                <div class="name">{{ $order->customer_name }}</div>
                <div class="detail"><strong>Phone:</strong> {{ $order->customer_phone }}</div>
                @if($order->customer_email)
                    <div class="detail"><strong>Email:</strong> {{ $order->customer_email }}</div>
                @endif
                <div class="detail" style="margin-top: 6px;">
                    {{ $order->shipping_address_line }}<br>
                    @if($order->shipping_area) {{ $order->shipping_area }}, @endif
                    {{ $order->shipping_city }}
                    @if($order->shipping_postcode) - {{ $order->shipping_postcode }} @endif<br>
                    {{ $order->shipping_country ?? 'Bangladesh' }}
                </div>
            </div>

            <div class="info-box">
                <h3>Order & Payment Details</h3>
                <div class="detail"><strong>Payment Method:</strong> {{ $order->payment_method?->label() ?? 'Cash on Delivery' }}</div>
                <div class="detail"><strong>Currency:</strong> {{ $order->currency ?? 'BDT' }}</div>
                @if($order->customer_note)
                    <div class="detail" style="margin-top: 6px; padding: 6px 8px; background: #fff; border-radius: 4px; border: 1px dashed #cbd5e1;">
                        <strong>Customer Note:</strong> {{ $order->customer_note }}
                    </div>
                @endif
                @if($order->latestShipment?->tracking_code)
                    <div class="detail" style="margin-top: 6px;">
                        <strong>Carrier:</strong> {{ $order->latestShipment->provider?->label() }}<br>
                        <strong>Tracking Code:</strong> {{ $order->latestShipment->tracking_code }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Ordered Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Item Description</th>
                    <th class="text-center" style="width: 15%;">SKU</th>
                    <th class="text-right" style="width: 12%;">Qty</th>
                    <th class="text-right" style="width: 13%;">Price</th>
                    <th class="text-right" style="width: 15%;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div class="item-title">{{ $item->product_name }}</div>
                            @if($item->variant_name)
                                <div class="item-sub">{{ $item->variant_name }}</div>
                            @endif
                        </td>
                        <td class="text-center"><code>{{ $item->sku }}</code></td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ config('currency.symbol', '৳') }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right"><strong>{{ config('currency.symbol', '৳') }} {{ number_format((float) $item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Summary -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Items Subtotal:</td>
                    <td class="amount">{{ config('currency.symbol', '৳') }} {{ number_format((float) $order->subtotal, 2) }}</td>
                </tr>
                @if((float) $order->discount_amount > 0)
                    <tr>
                        <td>Discount:</td>
                        <td class="amount" style="color: #15803d;">- {{ config('currency.symbol', '৳') }} {{ number_format((float) $order->discount_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Shipping Fee:</td>
                    <td class="amount">{{ config('currency.symbol', '৳') }} {{ number_format((float) $order->shipping_amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Grand Total:</td>
                    <td class="amount">{{ config('currency.symbol', '৳') }} {{ number_format((float) $order->grand_total, 2) }}</td>
                </tr>
                <tr>
                    <td>Amount Paid:</td>
                    <td class="amount" style="color: #15803d; font-weight: 600;">{{ config('currency.symbol', '৳') }} {{ number_format((float) $order->total_paid, 2) }}</td>
                </tr>
                @if($order->due_amount > 0)
                    <tr>
                        <td style="color: #b91c1c; font-weight: 600;">Balance Due:</td>
                        <td class="amount" style="color: #b91c1c; font-weight: 700;">{{ config('currency.symbol', '৳') }} {{ number_format((float) $order->due_amount, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Footer Note -->
        <div class="footer-note">
            <p>Thank you for choosing Choco Ecommerce! For any inquiries, please contact our helpline.</p>
            <p style="margin-top: 4px;">This is a computer-generated invoice and requires no physical signature.</p>
        </div>
    </div>

</body>
</html>
