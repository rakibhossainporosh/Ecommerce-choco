<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $shopName }}</title>
</head>
<body style="margin:0;padding:0;background:#FAF7F2;color:#1E293B;font-family:'Nirmala UI','Hind Siliguri',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF7F2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #EAE3D6;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#E86A28;padding:20px 24px;color:#ffffff;font-size:18px;font-weight:700;">{{ $shopName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:24px;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">{{ $intro }}</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="padding:8px 0;border-top:1px solid #EAE3D6;">
                                            {{ $item['quantity'] ?? 1 }} × {{ $item['name'] ?? '' }}
                                        </td>
                                        <td style="padding:8px 0;border-top:1px solid #EAE3D6;text-align:right;white-space:nowrap;">
                                            ৳{{ number_format((float) ($item['line_total'] ?? 0), 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            <p style="margin:16px 0;font-weight:700;">{{ __('Total') }}: ৳{{ $total }}</p>
                            <a href="{{ $recoverUrl }}" style="display:inline-block;background:#E86A28;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">{{ __('Return to your cart') }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
