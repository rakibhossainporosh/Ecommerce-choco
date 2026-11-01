<?php

namespace App\Support;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class FraudCheckSettings
{
    public static function enabled(): bool
    {
        return (bool) Setting::get('fraud_check_enabled', false);
    }

    public static function provider(): string
    {
        $provider = Setting::get('fraud_provider', 'steadfast');

        return $provider === 'custom' ? 'custom' : 'steadfast';
    }

    public static function companyName(): string
    {
        if (self::provider() !== 'custom') {
            return 'Steadfast';
        }

        $name = Setting::get('fraud_company_name');

        return is_string($name) && trim($name) !== '' ? trim($name) : __('Courier');
    }

    public static function ready(): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if (self::provider() === 'custom') {
            return self::customUrl() !== null
                && filled(self::credential('fraud_custom_api_key'))
                && self::fieldName(Setting::get('fraud_key_name'), '') !== '';
        }

        return filled(self::credential('fraud_steadfast_api_key'))
            && filled(self::credential('fraud_steadfast_secret_key'));
    }

    public static function customUrl(): ?string
    {
        return OrderSmsSettings::publicHttpUrl(Setting::get('fraud_custom_url'));
    }

    public static function customMethod(): string
    {
        return Setting::get('fraud_custom_method') === 'post' ? 'post' : 'get';
    }

    public static function phoneInField(): bool
    {
        return Setting::get('fraud_phone_place') === 'field';
    }

    public static function phoneField(): string
    {
        return self::fieldName(Setting::get('fraud_phone_field'), 'phone');
    }

    public static function keyName(): string
    {
        return self::fieldName(Setting::get('fraud_key_name'), 'Api-Key');
    }

    public static function extraKeyName(): string
    {
        return self::fieldName(Setting::get('fraud_extra_key_name'), '');
    }

    public static function keyOnLink(): bool
    {
        return (bool) Setting::get('fraud_key_on_link', false);
    }

    public static function word(string $key, string $default): string
    {
        return self::fieldName(Setting::get($key), $default);
    }

    public static function credential(string $key): ?string
    {
        $stored = Setting::get($key);

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return $stored;
        }
    }

    public static function hasSecret(string $key): bool
    {
        return filled(self::credential($key));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        self::store('fraud_check_enabled', ($state['fraud_check_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);

        if (array_key_exists('fraud_provider', $state)) {
            self::store('fraud_provider', ($state['fraud_provider'] ?? '') === 'custom' ? 'custom' : 'steadfast', SettingType::String);
        }

        self::storeSecret('fraud_steadfast_api_key', $state['fraud_steadfast_api_key'] ?? null);
        self::storeSecret('fraud_steadfast_secret_key', $state['fraud_steadfast_secret_key'] ?? null);

        if (array_key_exists('fraud_company_name', $state)) {
            $name = is_string($state['fraud_company_name'] ?? null) ? trim(strip_tags($state['fraud_company_name'])) : '';
            self::store('fraud_company_name', mb_substr($name, 0, 40), SettingType::String);
        }

        if (array_key_exists('fraud_custom_url', $state)) {
            self::store('fraud_custom_url', (string) (OrderSmsSettings::publicHttpUrl($state['fraud_custom_url']) ?? ''), SettingType::String);
        }

        if (array_key_exists('fraud_custom_method', $state)) {
            self::store('fraud_custom_method', ($state['fraud_custom_method'] ?? '') === 'post' ? 'post' : 'get', SettingType::String);
        }

        if (array_key_exists('fraud_phone_place', $state)) {
            self::store('fraud_phone_place', ($state['fraud_phone_place'] ?? '') === 'field' ? 'field' : 'link', SettingType::String);
        }

        if (array_key_exists('fraud_phone_field', $state)) {
            self::store('fraud_phone_field', self::fieldName($state['fraud_phone_field'], 'phone'), SettingType::String);
        }

        if (array_key_exists('fraud_key_name', $state)) {
            self::store('fraud_key_name', self::fieldName($state['fraud_key_name'], 'Api-Key'), SettingType::String);
        }

        if (array_key_exists('fraud_extra_key_name', $state)) {
            self::store('fraud_extra_key_name', self::fieldName($state['fraud_extra_key_name'], ''), SettingType::String);
        }

        if (array_key_exists('fraud_key_on_link', $state)) {
            self::store('fraud_key_on_link', ($state['fraud_key_on_link'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        }

        foreach ([
            'fraud_word_total' => 'total_parcels',
            'fraud_word_delivered' => 'total_delivered',
            'fraud_word_cancelled' => 'total_cancelled',
        ] as $key => $default) {
            if (array_key_exists($key, $state)) {
                self::store($key, self::fieldName($state[$key], $default), SettingType::String);
            }
        }

        self::storeSecret('fraud_custom_api_key', $state['fraud_custom_api_key'] ?? null);
        self::storeSecret('fraud_custom_secret', $state['fraud_custom_secret'] ?? null);
    }

    public static function fieldName(mixed $value, string $default): string
    {
        $name = is_string($value) ? trim($value) : '';

        if ($name === '' || preg_match('/^[A-Za-z0-9_-]{1,40}$/', $name) !== 1) {
            return $default;
        }

        return $name;
    }

    private static function store(string $key, string $value, SettingType $type): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'fraud_check',
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

        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->disableLogging();
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => Crypt::encryptString(trim($plain)),
            'type' => SettingType::String,
            'group' => 'fraud_check',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }
}
