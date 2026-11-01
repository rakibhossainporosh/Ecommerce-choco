@php
    $intro = __('mail.intro.'.$audience.'.'.$step, ['name' => $order->customer_name]);
    $status = $step === 'placed'
        ? __('Order placed')
        : __('enums.order_status.'.$order->status->value);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:0;background:#FAF7F2;color:#1E293B;font-family:'Nirmala UI','Hind Siliguri',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF7F2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #EAE3D6;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#E86A28;padding:20px 24px;color:#ffffff;font-size:18px;font-weight:700;">
                            {{ $fromName }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:16px;line-height:1.6;">{{ $intro }}</p>
                            <p style="margin:0 0 16px;font-size:14px;color:#64748B;">{{ __('Order number') }}: <strong style="color:#1E293B;">{{ $order->order_number }}</strong></p>
                            <p style="margin:0 0 20px;font-size:14px;color:#64748B;">{{ __('Status') }}: <strong style="color:#1E293B;">{{ $status }}</strong></p>

                            @if ($audience === 'admin')
                                <p style="margin:0 0 4px;font-size:14px;">{{ $order->customer_name }}</p>
                                <p style="margin:0 0 4px;font-size:14px;color:#64748B;">{{ $order->customer_phone }}</p>
                                <p style="margin:0 0 20px;font-size:14px;color:#64748B;">{{ $order->shipping_address_line }}{{ filled($order->shipping_city) ? ', '.$order->shipping_city : '' }}</p>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #F0EAE1;">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td style="padding:12px 0;font-size:14px;border-bottom:1px solid #F0EAE1;">
                                            {{ $item->product_name }}
                                            @if (filled($item->variant_name) && $item->variant_name !== $item->product_name)
                                                <span style="color:#64748B;"> — {{ $item->variant_name }}</span>
                                            @endif
                                            <div style="color:#64748B;font-size:13px;">× {{ $item->quantity }}</div>
                                        </td>
                                        <td align="right" style="padding:12px 0;font-size:14px;font-weight:700;border-bottom:1px solid #F0EAE1;white-space:nowrap;">
                                            ৳{{ number_format((float) $item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style="padding:16px 0 0;font-size:15px;font-weight:700;">{{ __('Grand total') }}</td>
                                    <td align="right" style="padding:16px 0 0;font-size:15px;font-weight:700;color:#E86A28;white-space:nowrap;">৳{{ number_format((float) $order->grand_total, 2) }}</td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#E86A28;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:10px;">{{ $actionLabel }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
