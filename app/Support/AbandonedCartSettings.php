<?php

namespace App\Support;

use App\Enums\SettingType;
use App\Models\AbandonedCart;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AbandonedCartSettings
{
    public static function enabled(): bool
    {
        return (bool) Setting::get('abandoned_cart_enabled', false);
    }

    public static function emailEnabled(): bool
    {
        return (bool) Setting::get('abandoned_cart_email_enabled', true);
    }

    public static function smsEnabled(): bool
    {
        return (bool) Setting::get('abandoned_cart_sms_enabled', true);
    }

    public static function delayMinutes(): int
    {
        $minutes = (int) Setting::get('abandoned_cart_delay_minutes', 60);

        return max(15, min($minutes, 4320));
    }

    public static function emailSubject(): string
    {
        return self::text('abandoned_cart_email_subject', self::defaultEmailSubject());
    }

    public static function emailBody(): string
    {
        return self::text('abandoned_cart_email_body', self::defaultEmailBody());
    }

    public static function smsBody(): string
    {
        return self::text('abandoned_cart_sms_body', self::defaultSmsBody());
    }

    public static function render(string $template, AbandonedCart $cart): string
    {
        $name = trim((string) $cart->name);

        $text = strtr($template, [
            '{name}' => $name !== '' ? $name : __('there'),
            '{shop}' => OrderSmsSettings::shopName(),
            '{total}' => number_format((float) $cart->subtotal, 0),
            '{url}' => route('cart.recover', ['token' => $cart->token]),
        ]);

        $text = trim(preg_replace("/[ \t]+/", ' ', strip_tags($text)) ?? '');

        return mb_substr($text, 0, 600);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formState(): array
    {
        return [
            'abandoned_cart_enabled' => self::enabled(),
            'abandoned_cart_delay_minutes' => self::delayMinutes(),
            'abandoned_cart_email_enabled' => self::emailEnabled(),
            'abandoned_cart_sms_enabled' => self::smsEnabled(),
            'abandoned_cart_email_subject' => self::emailSubject(),
            'abandoned_cart_email_body' => self::emailBody(),
            'abandoned_cart_sms_body' => self::smsBody(),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        $delay = (int) ($state['abandoned_cart_delay_minutes'] ?? 60);

        self::store('abandoned_cart_enabled', ($state['abandoned_cart_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        self::store('abandoned_cart_delay_minutes', (string) max(15, min($delay, 4320)), SettingType::Integer);
        self::store('abandoned_cart_email_enabled', ($state['abandoned_cart_email_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        self::store('abandoned_cart_sms_enabled', ($state['abandoned_cart_sms_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        self::store('abandoned_cart_email_subject', self::clean($state['abandoned_cart_email_subject'] ?? null, 160, self::defaultEmailSubject()), SettingType::String);
        self::store('abandoned_cart_email_body', self::clean($state['abandoned_cart_email_body'] ?? null, 600, self::defaultEmailBody()), SettingType::String);
        self::store('abandoned_cart_sms_body', self::clean($state['abandoned_cart_sms_body'] ?? null, 600, self::defaultSmsBody()), SettingType::String);
    }

    private static function text(string $key, string $default): string
    {
        $stored = Setting::get($key);

        return is_string($stored) && trim($stored) !== '' ? trim($stored) : $default;
    }

    private static function clean(mixed $value, int $limit, string $default): string
    {
        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        return mb_substr(trim($value), 0, $limit);
    }

    private static function defaultEmailSubject(): string
    {
        return app()->getLocale() === 'bn'
            ? 'আপনার {shop} কার্টে জিনিস পড়ে আছে'
            : 'You left items in your {shop} cart';
    }

    private static function defaultEmailBody(): string
    {
        return app()->getLocale() === 'bn'
            ? 'হ্যালো {name}, আপনার {shop} কার্টে ৳{total} এর জিনিস পড়ে আছে। এখনি নিয়ে নিতে পারেন।'
            : 'Hi {name}, you left ৳{total} of items at {shop}. They are still waiting for you.';
    }

    private static function defaultSmsBody(): string
    {
        return app()->getLocale() === 'bn'
            ? '{name}, আপনার {shop} কার্টে ৳{total} এর জিনিস আছে। {url}'
            : '{name}, your {shop} cart of ৳{total} is still waiting. {url}';
    }

    private static function store(string $key, string $value, SettingType $type): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'abandoned_cart',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }
}
