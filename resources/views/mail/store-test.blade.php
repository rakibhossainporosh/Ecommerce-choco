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
                            {{ __('This is a test email. Your mailbox is ready to send order updates.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
