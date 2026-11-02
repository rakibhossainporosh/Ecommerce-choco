<?php

namespace App\Support;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentManager;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewaySettings
{
    public static function enabled(PaymentGateway $gateway): bool
    {
        $default = $gateway->key() === 'cod';
        $value = Setting::get(self::settingKey($gateway->key(), 'enabled'), $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function text(string $gateway, string $field): string
    {
        $value = Setting::get(self::settingKey($gateway, $field), '');

        return is_string($value) ? trim($value) : '';
    }

    public static function bool(string $gateway, string $field, bool $default = false): bool
    {
        $value = Setting::get(self::settingKey($gateway, $field), $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function secret(string $gateway, string $field): ?string
    {
        $stored = Setting::get(self::settingKey($gateway, $field));

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return $stored;
        }
    }

    public static function hasSecret(string $gateway, string $field): bool
    {
        return filled(self::secret($gateway, $field));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        $input = is_array($state['gateways'] ?? null) ? $state['gateways'] : [];

        foreach (app(PaymentManager::class)->all() as $gateway) {
            $row = $input[$gateway->key()] ?? null;

            if (! is_array($row)) {
                continue;
            }

            self::storeBool($gateway->key(), 'enabled', (bool) ($row['enabled'] ?? false));

            foreach ($gateway->settingsFields() as $field) {
                $name = $field['key'];

                if (! array_key_exists($name, $row)) {
                    continue;
                }

                if ($field['type'] === 'secret' || ($field['secret'] ?? false)) {
                    self::storeSecret($gateway->key(), $name, $row[$name]);

                    continue;
                }

                if ($field['type'] === 'toggle') {
                    self::storeBool($gateway->key(), $name, (bool) $row[$name]);

                    continue;
                }

                $value = is_string($row[$name]) ? trim(strip_tags($row[$name])) : '';
                $max = $field['max'] ?? 500;
                self::storeText($gateway->key(), $name, mb_substr($value, 0, $max));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function formState(): array
    {
        $gateways = [];

        foreach (app(PaymentManager::class)->all() as $gateway) {
            $row = [
                'enabled' => self::enabled($gateway),
            ];

            foreach ($gateway->settingsFields() as $field) {
                $row[$field['key']] = match (true) {
                    $field['type'] === 'secret', (bool) ($field['secret'] ?? false) => null,
                    $field['type'] === 'toggle' => self::bool($gateway->key(), $field['key'], $field['default'] ?? false),
                    default => self::text($gateway->key(), $field['key']),
                };
            }

            $gateways[$gateway->key()] = $row;
        }

        return ['gateways' => $gateways];
    }

    private static function settingKey(string $gateway, string $field): string
    {
        return 'payment_gw_'.$gateway.'_'.$field;
    }

    private static function storeBool(string $gateway, string $field, bool $value): void
    {
        self::store($gateway, $field, $value ? 'true' : 'false', SettingType::Boolean);
    }

    private static function storeText(string $gateway, string $field, string $value): void
    {
        self::store($gateway, $field, $value, SettingType::String);
    }

    private static function storeSecret(string $gateway, string $field, mixed $plain): void
    {
        if (! is_string($plain) || trim($plain) === '') {
            return;
        }

        $setting = Setting::query()->firstOrNew(['key' => self::settingKey($gateway, $field)]);
        $setting->disableLogging();
        $setting->fill([
            'label' => str_replace('_', ' ', self::settingKey($gateway, $field)),
            'value' => Crypt::encryptString(trim($plain)),
            'type' => SettingType::String,
            'group' => 'payments',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget('setting.'.self::settingKey($gateway, $field));
    }

    private static function store(string $gateway, string $field, string $value, SettingType $type): void
    {
        $key = self::settingKey($gateway, $field);
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'payments',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }
}
