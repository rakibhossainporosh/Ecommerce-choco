<?php

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

test('setting validation logic works for booleans', function () {
    $setting = Setting::factory()->create([
        'type' => SettingType::Boolean,
        'value' => 'on',
    ]);
    expect($setting->value)->toBe('true');
    expect(Setting::get($setting->key))->toBeTrue();
});

test('setting validation rejects invalid integers', function () {
    expect(fn () => Setting::factory()->create([
        'type' => SettingType::Integer,
        'value' => 'not-an-int',
    ]))->toThrow(DomainException::class, 'Value must be a valid integer');
});

test('setting key is constrained', function () {
    expect(fn () => Setting::factory()->create([
        'key' => 'Invalid Key Format!',
    ]))->toThrow(DomainException::class, 'Setting key must contain only lowercase letters, numbers, and underscores.');
});

test('system settings prevent key and type updates', function () {
    $setting = Setting::factory()->system()->create([
        'key' => 'core_timezone',
        'type' => SettingType::String,
    ]);

    expect(fn () => $setting->update(['key' => 'new_key']))
        ->toThrow(DomainException::class, 'Cannot modify key or type of a system setting.');
});

test('cache is cleared when setting updates', function () {
    $setting = Setting::factory()->create([
        'key' => 'test_cache',
        'type' => SettingType::String,
        'value' => 'old_value',
    ]);

    // warm cache
    expect(Setting::get('test_cache'))->toBe('old_value');

    // update
    $setting->update(['value' => 'new_value']);

    // fetch again
    expect(Setting::get('test_cache'))->toBe('new_value');
});
