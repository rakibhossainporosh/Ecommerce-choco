<?php

use App\Enums\SettingType;
use App\Filament\Pages\OccasionSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\StorefrontOccasion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('a festival stays off until it is turned on and then dresses every shop page', function () {
    $this->get('/')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('site_settings.occasion', null)
    );

    Setting::query()->create([
        'key' => 'occasion_enabled',
        'label' => 'occasion enabled',
        'value' => 'true',
        'type' => SettingType::Boolean,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_key',
        'label' => 'occasion key',
        'value' => 'eid_fitr',
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);

    expect(StorefrontOccasion::present())->toMatchArray([
        'key' => 'eid_fitr',
        'label' => 'Eid Mubarak',
    ]);

    foreach (['/', '/about-us', '/contact'] as $path) {
        $this->get($path)->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->where('site_settings.occasion.key', 'eid_fitr')
            ->where('site_settings.occasion.label', 'Eid Mubarak')
        );
    }
});

test('an unknown festival key does not decorate the shop', function () {
    Setting::query()->create([
        'key' => 'occasion_enabled',
        'label' => 'occasion enabled',
        'value' => 'true',
        'type' => SettingType::Boolean,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_key',
        'label' => 'occasion key',
        'value' => 'not-a-festival',
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);

    expect(StorefrontOccasion::present())->toBeNull();
});

test('a festival stays hidden before it starts and after it ends', function () {
    Setting::query()->create([
        'key' => 'occasion_enabled',
        'label' => 'occasion enabled',
        'value' => 'true',
        'type' => SettingType::Boolean,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_key',
        'label' => 'occasion key',
        'value' => 'eid_fitr',
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_ends_at',
        'label' => 'occasion ends at',
        'value' => now()->subMinute()->format('Y-m-d H:i:s'),
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);

    expect(StorefrontOccasion::present())->toBeNull();

    Setting::query()->where('key', 'occasion_ends_at')->update([
        'value' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);
    Setting::query()->create([
        'key' => 'occasion_starts_at',
        'label' => 'occasion starts at',
        'value' => now()->addHour()->format('Y-m-d H:i:s'),
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);
    Cache::flush();

    expect(StorefrontOccasion::present())->toBeNull();

    Setting::query()->where('key', 'occasion_starts_at')->update([
        'value' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);
    Cache::flush();

    expect(StorefrontOccasion::present())
        ->key->toBe('eid_fitr')
        ->ends_at->not->toBeNull();
});

test('admin saves one festival and the shop reads that choice', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(OccasionSettings::class)
        ->fillForm([
            'occasion_enabled' => true,
            'occasion_key' => 'durga_puja',
            'occasion_general' => true,
            'occasion_hero' => true,
            'occasion_categories' => false,
            'occasion_products' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(StorefrontOccasion::present())->toMatchArray([
        'key' => 'durga_puja',
        'label' => 'Shubho Durga Puja',
        'lines' => [
            'hero' => 'Puja shopping is open',
            'categories' => 'Browse the puja edit',
            'products' => 'Puja offers inside',
        ],
        'sale_ids' => [],
        'hero_image' => null,
        'sections' => [
            'general' => true,
            'hero' => true,
            'categories' => false,
            'products' => true,
        ],
    ]);
});

test('the shop reads the chosen sale products and ignores an unsafe hero image', function () {
    Setting::query()->create([
        'key' => 'occasion_enabled',
        'label' => 'occasion enabled',
        'value' => 'true',
        'type' => SettingType::Boolean,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_key',
        'label' => 'occasion key',
        'value' => 'eid_adha',
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_sale_ids',
        'label' => 'occasion sale ids',
        'value' => json_encode([4, 4, 9]),
        'type' => SettingType::Json,
        'group' => 'occasion',
    ]);
    Setting::query()->create([
        'key' => 'occasion_hero_image',
        'label' => 'occasion hero image',
        'value' => 'https://example.com/banner.jpg',
        'type' => SettingType::String,
        'group' => 'occasion',
    ]);

    expect(StorefrontOccasion::present())->toMatchArray([
        'key' => 'eid_adha',
        'label' => 'Qurbani Eid Mubarak',
        'lines' => [
            'hero' => 'Qurbani deals are live',
            'categories' => 'Shop by category',
            'products' => 'Qurbani prices, cut',
        ],
        'sale_ids' => [4, 9],
        'hero_image' => null,
    ]);
});
