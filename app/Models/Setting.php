<?php

namespace App\Models;

use App\Enums\SettingType;
use Database\Factories\SettingFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'key',
    'label',
    'value',
    'type',
    'group',
    'is_public',
    'is_system',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $casts = [
        'type' => SettingType::class,
        'is_public' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Setting $setting) {
            $setting->validateBusinessRules();
        });

        static::saved(function (Setting $setting) {
            Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function (Setting $setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }

    public function validateBusinessRules(): void
    {
        // Enforce valid key formats
        if (! preg_match('/^[a-z0-9_]+$/', $this->key)) {
            throw new DomainException('Setting key must contain only lowercase letters, numbers, and underscores.');
        }

        if ($this->is_system && $this->isDirty(['key', 'type']) && $this->exists) {
            throw new DomainException('Cannot modify key or type of a system setting.');
        }

        // Validate type conversions
        if ($this->value !== null) {
            match ($this->type) {
                SettingType::Boolean => $this->validateBoolean(),
                SettingType::Integer => $this->validateInteger(),
                SettingType::Float => $this->validateFloat(),
                SettingType::Json => $this->validateJson(),
                default => null,
            };
        }
    }

    protected function validateBoolean(): void
    {
        $val = filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($val === null) {
            throw new DomainException("Value must be a valid boolean for {$this->key}.");
        }
        $this->value = $val ? 'true' : 'false';
    }

    protected function validateInteger(): void
    {
        if (filter_var($this->value, FILTER_VALIDATE_INT) === false) {
            throw new DomainException("Value must be a valid integer for {$this->key}.");
        }
    }

    protected function validateFloat(): void
    {
        if (filter_var($this->value, FILTER_VALIDATE_FLOAT) === false) {
            throw new DomainException("Value must be a valid float for {$this->key}.");
        }
    }

    protected function validateJson(): void
    {
        json_decode($this->value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainException("Value must be a valid JSON string for {$this->key}.");
        }
    }

    /**
     * Retrieve a setting's typed value via Cache
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever("setting.{$key}", function () use ($key) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return ['exists' => false, 'value' => null, 'type' => null];
            }

            return [
                'exists' => true,
                'value' => $setting->value,
                'type' => $setting->type instanceof SettingType ? $setting->type->value : $setting->type,
            ];
        });

        if (! is_array($cached) || ! isset($cached['exists'])) {
            Cache::forget("setting.{$key}");
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            $cached = [
                'exists' => true,
                'value' => $setting->value,
                'type' => $setting->type instanceof SettingType ? $setting->type->value : $setting->type,
            ];

            Cache::forever("setting.{$key}", $cached);
        }

        if (! $cached['exists'] || $cached['value'] === null) {
            return $default;
        }

        $type = SettingType::tryFrom($cached['type'] ?? '');

        return match ($type) {
            SettingType::Boolean => filter_var($cached['value'], FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer => (int) $cached['value'],
            SettingType::Float => (float) $cached['value'],
            SettingType::Json => json_decode($cached['value'], true),
            default => $cached['value'],
        };
    }
}
