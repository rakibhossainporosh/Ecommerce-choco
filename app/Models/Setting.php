<?php

namespace App\Models;

use App\Enums\SettingType;
use Database\Factories\SettingFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        $setting = Cache::rememberForever("setting.{$key}", function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        if ($setting->value === null) {
            return $default;
        }

        return match ($setting->type) {
            SettingType::Boolean => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer => (int) $setting->value,
            SettingType::Float => (float) $setting->value,
            SettingType::Json => json_decode($setting->value, true),
            default => $setting->value,
        };
    }
}
