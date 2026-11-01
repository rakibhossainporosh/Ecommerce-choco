<?php

namespace App\Support;

use App\Enums\SettingType;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class OrderMailSettings
{
    /**
     * @return array<string, string>
     */
    public static function steps(): array
    {
        return [
            'placed' => 'Order placed',
            'confirmed' => 'Order confirmed',
            'processing' => 'Order processing',
            'shipped' => 'Order shipped',
            'delivered' => 'Order delivered',
            'cancelled' => 'Order cancelled',
            'returned' => 'Order returned',
            'refunded' => 'Order refunded',
        ];
    }

    /**
     * @return array<string, array{customer: bool, admin: bool}>
     */
    public static function defaultRules(): array
    {
        return [
            'placed' => ['customer' => true, 'admin' => true],
            'confirmed' => ['customer' => true, 'admin' => false],
            'processing' => ['customer' => false, 'admin' => false],
            'shipped' => ['customer' => true, 'admin' => false],
            'delivered' => ['customer' => true, 'admin' => true],
            'cancelled' => ['customer' => true, 'admin' => true],
            'returned' => ['customer' => true, 'admin' => false],
            'refunded' => ['customer' => true, 'admin' => true],
        ];
    }

    public static function enabled(): bool
    {
        return (bool) Setting::get('mail_enabled', false);
    }

    public static function provider(): string
    {
        $provider = Setting::get('mail_provider', 'gmail');

        return in_array($provider, ['gmail', 'webmail'], true) ? $provider : 'gmail';
    }

    /**
     * @return array<string, array{customer: bool, admin: bool}>
     */
    public static function rules(): array
    {
        $stored = Setting::get('order_mail_rules');
        $rules = self::defaultRules();

        if (! is_array($stored)) {
            return $rules;
        }

        foreach (array_keys($rules) as $step) {
            $rule = $stored[$step] ?? null;

            if (! is_array($rule)) {
                continue;
            }

            $rules[$step] = [
                'customer' => (bool) ($rule['customer'] ?? false),
                'admin' => (bool) ($rule['admin'] ?? false),
            ];
        }

        return $rules;
    }

    /**
     * @return array{customer: bool, admin: bool}
     */
    public static function rule(string $step): array
    {
        return self::rules()[$step] ?? ['customer' => false, 'admin' => false];
    }

    public static function adminEmail(): ?string
    {
        return self::validEmail(Setting::get('mail_admin_address'));
    }

    public static function customerEmail(Order $order): ?string
    {
        return self::validEmail($order->customer_email)
            ?? self::validEmail($order->customer?->email);
    }

    public static function hasSecret(string $key): bool
    {
        return filled(self::secret($key));
    }

    public static function secret(string $key): ?string
    {
        $stored = Setting::get($key);

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function fromAddress(): ?string
    {
        $key = self::provider() === 'webmail' ? 'mail_webmail_address' : 'mail_gmail_address';

        return self::validEmail(Setting::get($key));
    }

    public static function fromName(): string
    {
        $name = Setting::get('site_name', 'Nobo Shop');

        return is_string($name) && $name !== '' ? $name : 'Nobo Shop';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function mailerConfig(): ?array
    {
        $from = self::fromAddress();

        if ($from === null) {
            return null;
        }

        if (self::provider() === 'webmail') {
            $host = Setting::get('mail_webmail_host');
            $password = self::secret('mail_webmail_password');
            $port = (int) Setting::get('mail_webmail_port', 587);
            $encryption = Setting::get('mail_webmail_encryption', 'tls') === 'ssl' ? 'ssl' : 'tls';

            if (! is_string($host) || $host === '' || $password === null || $port < 1) {
                return null;
            }

            return self::smtpConfig($host, $port, $encryption, $from, $password);
        }

        $password = self::secret('mail_gmail_password');

        if ($password === null) {
            return null;
        }

        return self::smtpConfig('smtp.gmail.com', 587, 'tls', $from, $password);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        self::store('mail_enabled', ($state['mail_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        self::store(
            'mail_provider',
            ($state['mail_provider'] ?? 'gmail') === 'webmail' ? 'webmail' : 'gmail',
            SettingType::String,
        );
        self::store('mail_gmail_address', self::nullableString($state['mail_gmail_address'] ?? null), SettingType::String);
        self::store('mail_webmail_address', self::nullableString($state['mail_webmail_address'] ?? null), SettingType::String);
        self::store('mail_webmail_host', self::nullableString($state['mail_webmail_host'] ?? null), SettingType::String);
        self::store('mail_webmail_port', (string) self::port($state['mail_webmail_port'] ?? 587), SettingType::Integer);
        self::store(
            'mail_webmail_encryption',
            ($state['mail_webmail_encryption'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls',
            SettingType::String,
        );
        self::store('mail_admin_address', self::nullableString($state['mail_admin_address'] ?? null), SettingType::String);
        self::store('order_mail_rules', json_encode(self::normalizeRules($state['rules'] ?? []), JSON_UNESCAPED_UNICODE), SettingType::Json);

        self::storeSecret('mail_gmail_password', $state['mail_gmail_password'] ?? null);
        self::storeSecret('mail_webmail_password', $state['mail_webmail_password'] ?? null);
    }

    /**
     * @return array<string, array{customer: bool, admin: bool}>
     */
    public static function normalizeRules(mixed $rules): array
    {
        $normalized = self::defaultRules();

        if (! is_array($rules)) {
            return $normalized;
        }

        foreach (array_keys($normalized) as $step) {
            $rule = $rules[$step] ?? null;

            if (! is_array($rule)) {
                continue;
            }

            $normalized[$step] = [
                'customer' => (bool) ($rule['customer'] ?? false),
                'admin' => (bool) ($rule['admin'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private static function smtpConfig(string $host, int $port, string $encryption, string $username, string $password): array
    {
        return [
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'timeout' => 15,
        ];
    }

    private static function store(string $key, ?string $value, SettingType $type): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'mail',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }

    private static function storeSecret(string $key, mixed $plain): void
    {
        if (! is_string($plain) || trim($plain) === '') {
            return;
        }

        $plain = trim($plain);

        if ($key === 'mail_gmail_password') {
            $plain = preg_replace('/\s+/', '', $plain) ?? $plain;
        }

        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->disableLogging();
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => Crypt::encryptString(trim($plain)),
            'type' => SettingType::String,
            'group' => 'mail',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function validEmail(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private static function port(mixed $value): int
    {
        $port = (int) $value;

        if ($port < 1 || $port > 65535) {
            return 587;
        }

        return $port;
    }
}
