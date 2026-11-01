<?php

namespace App\Support;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class OrderSmsSettings
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
            'placed' => ['customer' => true, 'admin' => false],
            'confirmed' => ['customer' => false, 'admin' => false],
            'processing' => ['customer' => false, 'admin' => false],
            'shipped' => ['customer' => false, 'admin' => false],
            'delivered' => ['customer' => false, 'admin' => false],
            'cancelled' => ['customer' => false, 'admin' => false],
            'returned' => ['customer' => false, 'admin' => false],
            'refunded' => ['customer' => false, 'admin' => false],
            'registered' => ['customer' => true, 'admin' => false],
        ];
    }

    public static function enabled(): bool
    {
        return (bool) Setting::get('sms_enabled', config('services.sms.enabled', true));
    }

    public static function driver(): string
    {
        $driver = Setting::get('sms_driver', config('services.sms.driver', 'log'));

        return in_array($driver, ['greenweb', 'bulksmsbd', 'custom', 'log'], true) ? $driver : 'log';
    }

    public static function gatewayReady(): bool
    {
        return match (self::driver()) {
            'greenweb' => filled(self::credential('greenweb_token')),
            'bulksmsbd' => filled(self::credential('bulksmsbd_api_key')) && filled(self::credential('bulksmsbd_sender_id')),
            'custom' => self::customRequest('8801700000000', 'test') !== null,
            default => false,
        };
    }

    public static function customUrl(): ?string
    {
        $stored = Setting::get('sms_custom_url');

        return self::publicHttpUrl(is_string($stored) ? $stored : null);
    }

    public static function customBalanceUrl(): ?string
    {
        $stored = Setting::get('sms_custom_balance_url');

        return self::publicHttpUrl(is_string($stored) ? $stored : null);
    }

    public static function customBalanceRequestUrl(): ?string
    {
        $url = self::customBalanceUrl();

        if ($url === null) {
            return null;
        }

        $key = self::credential('sms_custom_api_key') ?? '';
        $name = self::customKeyFieldName();
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($name !== '' && $key !== '' && (($query[$name] ?? '') === '')) {
            $query[$name] = $key;
        }

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        if ($query !== []) {
            $rebuilt .= '?'.http_build_query($query);
        }

        return self::publicHttpUrl($rebuilt);
    }

    public static function customKeyFieldName(): string
    {
        foreach (self::customFields() as $field) {
            if ($field['value'] === '{key}') {
                return $field['name'];
            }
        }

        return '';
    }

    public static function customMethod(): string
    {
        $method = Setting::get('sms_custom_method', 'post');

        return in_array($method, ['post', 'json', 'get'], true) ? $method : 'post';
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    public static function defaultCustomFields(): array
    {
        return [
            ['name' => 'api_key', 'value' => '{key}'],
            ['name' => 'to', 'value' => '{to}'],
            ['name' => 'message', 'value' => '{message}'],
            ['name' => 'senderid', 'value' => '{sender}'],
        ];
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    public static function customFields(): array
    {
        $stored = Setting::get('sms_custom_fields');

        if (! is_array($stored)) {
            return self::defaultCustomFields();
        }

        return self::normalizeGatewayFields($stored);
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    public static function customHeaders(): array
    {
        $stored = Setting::get('sms_custom_headers');

        return self::normalizeGatewayFields(is_array($stored) ? $stored : []);
    }

    /**
     * @return array{params: array<string, string>, headers: array<string, string>}|null
     */
    public static function customRequest(string $to, string $message): ?array
    {
        if (self::customUrl() === null) {
            return null;
        }

        $fields = self::customFields();
        $headers = self::customHeaders();

        if ($fields === [] || ! self::fieldsContain($fields, '{to}') || ! self::fieldsContain($fields, '{message}')) {
            return null;
        }

        $key = self::credential('sms_custom_api_key') ?? '';

        if ((self::fieldsContain($fields, '{key}') || self::fieldsContain($headers, '{key}')) && $key === '') {
            return null;
        }

        $sender = self::credential('sms_custom_sender_id') ?? '';
        $replace = [
            '{key}' => $key,
            '{to}' => $to,
            '{message}' => $message,
            '{sender}' => $sender,
        ];

        return [
            'params' => self::applyGatewayFields($fields, $replace, $sender),
            'headers' => self::applyGatewayFields($headers, $replace, $sender),
        ];
    }

    public static function publicHttpUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        return $url;
    }

    /**
     * @return array<string, array{customer: bool, admin: bool}>
     */
    public static function rules(): array
    {
        $stored = Setting::get('sms_rules');

        return self::normalizeRules(is_array($stored) ? $stored : null);
    }

    /**
     * @return array{customer: bool, admin: bool}
     */
    public static function rule(string $step): array
    {
        return self::rules()[$step] ?? ['customer' => false, 'admin' => false];
    }

    /**
     * @param  array<string, mixed>|null  $rules
     */
    public static function adminStepsEnabled(?array $rules): bool
    {
        $rules = self::normalizeRules($rules);

        foreach (array_keys(self::steps()) as $step) {
            if ($rules[$step]['admin']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function phones(): array
    {
        $stored = Setting::get('sms_admin_phones');

        return self::normalizePhones(is_array($stored) ? $stored : []);
    }

    public static function message(string $step, string $audience): string
    {
        $stored = Setting::get('sms_messages');
        $text = is_array($stored) ? ($stored[$step][$audience] ?? null) : null;

        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }

        return self::defaultMessages()[$step][$audience] ?? '';
    }

    public static function shopName(): string
    {
        $name = Setting::get('site_name', 'Nobo Shop');

        return is_string($name) && $name !== '' ? $name : 'Nobo Shop';
    }

    public static function credential(string $key): ?string
    {
        $stored = Setting::get($key);

        if (is_string($stored) && $stored !== '') {
            if (in_array($key, ['greenweb_token', 'bulksmsbd_api_key', 'sms_custom_api_key'], true)) {
                try {
                    return Crypt::decryptString($stored);
                } catch (DecryptException) {
                    return $stored;
                }
            }

            return $stored;
        }

        $fallback = match ($key) {
            'greenweb_token' => config('services.greenweb.token'),
            'bulksmsbd_api_key' => config('services.bulksmsbd.api_key'),
            'bulksmsbd_sender_id' => config('services.bulksmsbd.sender_id'),
            default => null,
        };

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    public static function hasCredential(string $key): bool
    {
        return filled(self::credential($key));
    }

    public static function canonicalPhone(string $phone): ?string
    {
        $formatted = app(SmsService::class)->formatBangladeshiPhone($phone);

        if (! is_string($formatted) || ! preg_match('/^8801[3-9]\d{8}$/', $formatted)) {
            return null;
        }

        return substr($formatted, 2);
    }

    /**
     * @param  array<string, string>  $values
     */
    public static function render(string $template, array $values): string
    {
        $replace = [];

        foreach (['name', 'phone', 'number', 'total', 'status', 'address', 'url', 'shop'] as $key) {
            $replace['{'.$key.'}'] = (string) ($values[$key] ?? '');
        }

        $text = strtr(strip_tags($template), $replace);
        $text = trim(preg_replace("/[ \t]+/", ' ', $text) ?? '');

        return $text;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        $driver = $state['sms_driver'] ?? 'greenweb';
        $driver = in_array($driver, ['greenweb', 'bulksmsbd', 'custom', 'log'], true) ? $driver : 'greenweb';

        $rules = self::normalizeRules($state['rules'] ?? null);
        $rules['registered']['customer'] = (bool) ($state['registered_enabled'] ?? $rules['registered']['customer']);
        $rules['registered']['admin'] = false;

        $messages = self::normalizeMessages($state['messages'] ?? null);
        $messages['registered']['customer'] = self::cleanMessage($state['registered_message'] ?? $messages['registered']['customer']);
        $messages['registered']['admin'] = '';

        self::store('sms_enabled', ($state['sms_enabled'] ?? false) ? 'true' : 'false', SettingType::Boolean);
        self::store('sms_driver', $driver, SettingType::String);
        self::store('bulksmsbd_sender_id', self::nullableString($state['bulksmsbd_sender_id'] ?? null), SettingType::String);
        self::store('sms_custom_url', self::publicHttpUrl($state['sms_custom_url'] ?? null), SettingType::String);
        self::store('sms_custom_balance_url', self::publicHttpUrl($state['sms_custom_balance_url'] ?? null), SettingType::String);
        self::store('sms_custom_method', self::customMethodFrom($state['sms_custom_method'] ?? null), SettingType::String);
        self::store('sms_custom_sender_id', self::nullableString($state['sms_custom_sender_id'] ?? null), SettingType::String);
        self::store('sms_custom_fields', json_encode(self::fieldsForSave($state), JSON_UNESCAPED_UNICODE), SettingType::Json);
        self::store('sms_custom_headers', json_encode(self::headersForSave($state), JSON_UNESCAPED_UNICODE), SettingType::Json);
        self::store('sms_admin_phones', json_encode(self::normalizePhones($state['phones'] ?? []), JSON_UNESCAPED_UNICODE), SettingType::Json);
        self::store('sms_rules', json_encode($rules, JSON_UNESCAPED_UNICODE), SettingType::Json);
        self::store('sms_messages', json_encode($messages, JSON_UNESCAPED_UNICODE), SettingType::Json);

        self::storeSecret('greenweb_token', $state['greenweb_token'] ?? null);
        self::storeSecret('bulksmsbd_api_key', $state['bulksmsbd_api_key'] ?? null);
        self::storeSecret('sms_custom_api_key', $state['sms_custom_api_key'] ?? null);
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

        $normalized['registered']['admin'] = false;

        return $normalized;
    }

    /**
     * @return array<string, array{customer: string, admin: string}>
     */
    public static function defaultMessages(): array
    {
        if (app()->getLocale() === 'bn') {
            return [
                'placed' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} নেওয়া হয়েছে। মোট: ৳{total}। ট্র্যাক করুন: {url}',
                    'admin' => 'নতুন অর্ডার #{number}। গ্রাহক: {name}, {phone}। মোট: ৳{total}। ঠিকানা: {address}',
                ],
                'confirmed' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} নিশ্চিত হয়েছে। ট্র্যাক করুন: {url}',
                    'admin' => 'অর্ডার #{number} নিশ্চিত। গ্রাহক: {name}, {phone}।',
                ],
                'processing' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} তৈরি হচ্ছে। ট্র্যাক করুন: {url}',
                    'admin' => 'অর্ডার #{number} তৈরি হচ্ছে। গ্রাহক: {name}, {phone}।',
                ],
                'shipped' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} পাঠানো হয়েছে। ট্র্যাক করুন: {url}',
                    'admin' => 'অর্ডার #{number} পাঠানো হয়েছে। গ্রাহক: {name}, {phone}।',
                ],
                'delivered' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} পৌঁছে গেছে। ধন্যবাদ।',
                    'admin' => 'অর্ডার #{number} পৌঁছেছে। গ্রাহক: {name}, {phone}।',
                ],
                'cancelled' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} বাতিল হয়েছে।',
                    'admin' => 'অর্ডার #{number} বাতিল। গ্রাহক: {name}, {phone}।',
                ],
                'returned' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number} ফেরত এসেছে।',
                    'admin' => 'অর্ডার #{number} ফেরত। গ্রাহক: {name}, {phone}।',
                ],
                'refunded' => [
                    'customer' => 'প্রিয় {name}, {shop}-এ আপনার অর্ডার #{number}-এর টাকা ফেরত দেওয়া হয়েছে।',
                    'admin' => 'অর্ডার #{number}-এর টাকা ফেরত। গ্রাহক: {name}, {phone}।',
                ],
                'registered' => [
                    'customer' => 'স্বাগতম {name}। {shop}-এ আপনার অ্যাকাউন্ট তৈরি হয়েছে। কেনাকাটা শুরু করুন: {url}',
                    'admin' => '',
                ],
            ];
        }

        return [
            'placed' => [
                'customer' => 'Hi {name}, {shop} has received your order #{number}. Total: ৳{total}. Track it here: {url}',
                'admin' => 'New order #{number} from {name}, {phone}. Total: ৳{total}. Address: {address}',
            ],
            'confirmed' => [
                'customer' => 'Hi {name}, your {shop} order #{number} is confirmed. Track it here: {url}',
                'admin' => 'Order #{number} is confirmed. Customer: {name}, {phone}.',
            ],
            'processing' => [
                'customer' => 'Hi {name}, your {shop} order #{number} is being prepared. Track it here: {url}',
                'admin' => 'Order #{number} is being prepared. Customer: {name}, {phone}.',
            ],
            'shipped' => [
                'customer' => 'Hi {name}, your {shop} order #{number} is on the way. Track it here: {url}',
                'admin' => 'Order #{number} has shipped. Customer: {name}, {phone}.',
            ],
            'delivered' => [
                'customer' => 'Hi {name}, your {shop} order #{number} has been delivered. Thank you.',
                'admin' => 'Order #{number} was delivered. Customer: {name}, {phone}.',
            ],
            'cancelled' => [
                'customer' => 'Hi {name}, your {shop} order #{number} has been cancelled.',
                'admin' => 'Order #{number} was cancelled. Customer: {name}, {phone}.',
            ],
            'returned' => [
                'customer' => 'Hi {name}, your {shop} order #{number} has been returned.',
                'admin' => 'Order #{number} was returned. Customer: {name}, {phone}.',
            ],
            'refunded' => [
                'customer' => 'Hi {name}, the refund for your {shop} order #{number} has been sent.',
                'admin' => 'Order #{number} was refunded. Customer: {name}, {phone}.',
            ],
            'registered' => [
                'customer' => 'Welcome {name}. Your {shop} account is ready. Start shopping: {url}',
                'admin' => '',
            ],
        ];
    }

    /**
     * @return array<string, array{customer: string, admin: string}>
     */
    public static function normalizeMessages(mixed $messages): array
    {
        $normalized = self::defaultMessages();

        foreach ($normalized as $step => $audiences) {
            $normalized[$step] = [
                'customer' => '',
                'admin' => '',
            ];

            if (! is_array($messages) || ! is_array($messages[$step] ?? null)) {
                $normalized[$step]['customer'] = $audiences['customer'];
                $normalized[$step]['admin'] = $audiences['admin'];

                continue;
            }

            $normalized[$step]['customer'] = self::cleanMessage($messages[$step]['customer'] ?? $audiences['customer']);
            $normalized[$step]['admin'] = self::cleanMessage($messages[$step]['admin'] ?? $audiences['admin']);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function normalizePhones(mixed $phones): array
    {
        if (! is_array($phones)) {
            return [];
        }

        $unique = [];

        foreach ($phones as $phone) {
            if (is_array($phone)) {
                $phone = $phone['number'] ?? null;
            }

            if (! is_string($phone)) {
                continue;
            }

            $canonical = self::canonicalPhone($phone);

            if ($canonical === null) {
                continue;
            }

            $unique[$canonical] = $canonical;
        }

        return array_slice(array_values($unique), 0, 10);
    }

    /**
     * @return array{
     *     sms_field_key: string,
     *     sms_field_phone: string,
     *     sms_field_message: string,
     *     sms_field_sender: string,
     *     sms_custom_extra: list<array{name: string, value: string}>,
     *     sms_custom_header_rows: list<array{name: string, words: string, use_key: bool}>
     * }
     */
    public static function formFields(): array
    {
        $storedFields = Setting::get('sms_custom_fields');
        $fields = is_array($storedFields) ? self::normalizeGatewayFields($storedFields) : self::defaultCustomFields();
        $names = [
            'sms_field_key' => '',
            'sms_field_phone' => '',
            'sms_field_message' => '',
            'sms_field_sender' => '',
        ];
        $extra = [];

        foreach ($fields as $field) {
            $input = match ($field['value']) {
                '{key}' => 'sms_field_key',
                '{to}' => 'sms_field_phone',
                '{message}' => 'sms_field_message',
                '{sender}' => 'sms_field_sender',
                default => null,
            };

            if ($input === null) {
                $extra[] = $field;

                continue;
            }

            if ($names[$input] === '') {
                $names[$input] = $field['name'];
            }
        }

        $headerRows = [];

        foreach (self::customHeaders() as $header) {
            $usesKey = str_contains($header['value'], '{key}');
            $words = trim(str_replace('{key}', '', $header['value']));
            $headerRows[] = [
                'name' => $header['name'],
                'words' => $words,
                'use_key' => $usesKey,
            ];
        }

        return [
            ...$names,
            'sms_custom_extra' => $extra,
            'sms_custom_header_rows' => $headerRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<array{name: string, value: string}>
     */
    private static function fieldsForSave(array $state): array
    {
        $usesPlainNames = array_key_exists('sms_field_phone', $state)
            || array_key_exists('sms_field_message', $state)
            || array_key_exists('sms_field_key', $state);

        if (! $usesPlainNames) {
            return self::normalizeGatewayFields($state['sms_custom_fields'] ?? null);
        }

        $rows = [];

        foreach ([
            'sms_field_key' => '{key}',
            'sms_field_phone' => '{to}',
            'sms_field_message' => '{message}',
            'sms_field_sender' => '{sender}',
        ] as $input => $token) {
            $name = self::fieldName($state[$input] ?? null, '');

            if ($name === '') {
                continue;
            }

            $rows[] = ['name' => $name, 'value' => $token];
        }

        foreach (self::normalizeGatewayFields($state['sms_custom_extra'] ?? []) as $extra) {
            $rows[] = $extra;
        }

        return self::normalizeGatewayFields($rows);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<array{name: string, value: string}>
     */
    private static function headersForSave(array $state): array
    {
        if (! array_key_exists('sms_custom_header_rows', $state)) {
            return self::normalizeGatewayFields($state['sms_custom_headers'] ?? null);
        }

        $rows = [];
        $headers = $state['sms_custom_header_rows'];

        if (! is_array($headers)) {
            return [];
        }

        foreach ($headers as $header) {
            if (! is_array($header)) {
                continue;
            }

            $name = self::fieldName($header['name'] ?? null, '');
            $words = is_string($header['words'] ?? null) ? trim($header['words']) : '';
            $useKey = (bool) ($header['use_key'] ?? false);

            if ($name === '') {
                continue;
            }

            if ($useKey) {
                $value = $words === '' ? '{key}' : $words.' {key}';
            } else {
                $value = $words;
            }

            if ($value === '') {
                continue;
            }

            $rows[] = ['name' => $name, 'value' => $value];
        }

        return self::normalizeGatewayFields($rows);
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    private static function normalizeGatewayFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $unique = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = self::fieldName($field['name'] ?? null, '');
            $value = $field['value'] ?? null;

            if ($name === '' || ! is_string($value) || trim($value) === '') {
                continue;
            }

            $unique[$name] = [
                'name' => $name,
                'value' => mb_substr(trim($value), 0, 500),
            ];
        }

        return array_values(array_slice($unique, 0, 20));
    }

    /**
     * @param  list<array{name: string, value: string}>  $fields
     */
    private static function fieldsContain(array $fields, string $token): bool
    {
        foreach ($fields as $field) {
            if (str_contains($field['value'], $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{name: string, value: string}>  $fields
     * @param  array<string, string>  $replace
     * @return array<string, string>
     */
    private static function applyGatewayFields(array $fields, array $replace, string $sender): array
    {
        $applied = [];

        foreach ($fields as $field) {
            if (str_contains($field['value'], '{sender}') && $sender === '') {
                continue;
            }

            $applied[$field['name']] = strtr($field['value'], $replace);
        }

        return $applied;
    }

    private static function customMethodFrom(mixed $method): string
    {
        return in_array($method, ['post', 'json', 'get'], true) ? $method : 'post';
    }

    private static function fieldName(mixed $value, string $default): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/', $value) !== 1) {
            return $default;
        }

        return $value;
    }

    private static function cleanMessage(mixed $message): string
    {
        if (! is_string($message)) {
            return '';
        }

        $message = trim($message);

        if ($message === '') {
            return '';
        }

        return mb_substr($message, 0, 600);
    }

    private static function store(string $key, ?string $value, SettingType $type): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'sms',
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
            'group' => 'sms',
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
}
