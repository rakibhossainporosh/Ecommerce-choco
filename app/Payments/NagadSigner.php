<?php

namespace App\Payments;

class NagadSigner
{
    public static function encrypt(string $plain, string $publicKey): string
    {
        $key = openssl_pkey_get_public(self::pem($publicKey));

        if ($key === false) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $key, OPENSSL_PKCS1_PADDING);

        if (! $ok) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return base64_encode($encrypted);
    }

    public static function decrypt(string $cipher, string $privateKey): string
    {
        $key = openssl_pkey_get_private(self::pem($privateKey));
        $decoded = base64_decode($cipher, true);

        if ($key === false || $decoded === false) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        $plain = '';
        $ok = openssl_private_decrypt($decoded, $plain, $key, OPENSSL_PKCS1_PADDING);

        if (! $ok) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        return $plain;
    }

    public static function sign(string $plain, string $privateKey): string
    {
        $key = openssl_pkey_get_private(self::pem($privateKey));

        if ($key === false) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        $signature = '';
        $ok = openssl_sign($plain, $signature, $key, OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return base64_encode($signature);
    }

    public static function verify(string $plain, string $signature, string $publicKey): bool
    {
        $key = openssl_pkey_get_public(self::pem($publicKey));
        $decoded = base64_decode($signature, true);

        if ($key === false || $decoded === false) {
            return false;
        }

        return openssl_verify($plain, $decoded, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private static function pem(string $key): string
    {
        $trimmed = trim($key);

        if (str_contains($trimmed, '-----BEGIN')) {
            return $trimmed;
        }

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split($trimmed, 64, "\n").'-----END PUBLIC KEY-----';
    }
}
