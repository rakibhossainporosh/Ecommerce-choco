<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $isReset ? __('storefront.auth.reset_mail_subject', ['shop' => $shopName]) : __('storefront.auth.otp_mail_subject', ['shop' => $shopName]) }}</title>
</head>
<body style="font-family: system-ui, sans-serif; color: #1e293b; line-height: 1.5;">
    <p>{{ __('storefront.auth.reset_mail_greeting', ['name' => $customer->name]) }}</p>
    <p>{{ $isReset ? __('storefront.auth.reset_mail_body') : __('storefront.auth.otp_mail_body') }}</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 0.2em;">{{ $code }}</p>
    <p>{{ __('storefront.auth.reset_mail_expiry') }}</p>
    <p style="color: #64748b; font-size: 13px;">{{ $shopName }}</p>
</body>
</html>
