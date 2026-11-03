<?php

use App\Enums\SettingType;
use App\Filament\Pages\ThemeSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\StorefrontTheme;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('the shop stays on the warm theme until another one is saved', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('data-store-theme="warm"', false)
        ->assertInertia(fn (Assert $page) => $page->where('site_settings.theme', 'warm'));

    expect(StorefrontTheme::key())->toBe('warm');
});

test('an unknown theme falls back to warm', function () {
    Setting::query()->create([
        'key' => 'storefront_theme',
        'label' => 'storefront theme',
        'value' => 'neon',
        'type' => SettingType::String,
        'group' => 'theme',
    ]);

    expect(StorefrontTheme::key())->toBe('warm');

    $this->get('/')->assertSee('data-store-theme="warm"', false);
});

test('grove and harbor repaint the storefront', function () {
    Setting::query()->create([
        'key' => 'storefront_theme',
        'label' => 'storefront theme',
        'value' => 'grove',
        'type' => SettingType::String,
        'group' => 'theme',
    ]);

    $this->get('/')
        ->assertSee('data-store-theme="grove"', false)
        ->assertInertia(fn (Assert $page) => $page->where('site_settings.theme', 'grove'));

    Setting::query()->where('key', 'storefront_theme')->update(['value' => 'harbor']);
    Cache::flush();

    $this->get('/')->assertSee('data-store-theme="harbor"', false);
});

test('admin saves the shop theme', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(ThemeSettings::class)
        ->fillForm([
            'storefront_theme' => 'harbor',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(StorefrontTheme::key())->toBe('harbor');
});

test('site logo from settings is shared on every theme', function () {
    Setting::query()->create([
        'key' => 'site_logo',
        'label' => 'site logo',
        'value' => 'brand/logo.png',
        'type' => SettingType::String,
        'group' => 'general',
    ]);

    foreach (['warm', 'grove', 'harbor'] as $theme) {
        Setting::query()->updateOrCreate(
            ['key' => 'storefront_theme'],
            [
                'label' => 'storefront theme',
                'value' => $theme,
                'type' => SettingType::String,
                'group' => 'theme',
            ],
        );
        Cache::flush();

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('data-store-theme="'.$theme.'"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('site_settings.theme', $theme)
                ->where('site_settings.site_logo', asset('storage/brand/logo.png')));
    }
});
