<?php

use App\Filament\Pages\FooterSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\Ga4;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('ga4 measurement id is shared on the storefront when configured', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'ga4_measurement_id'],
        [
            'label' => 'GA4 Measurement ID',
            'value' => 'G-TEST12345',
            'type' => 'string',
            'group' => 'marketing',
            'is_public' => true,
        ],
    );

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ga4.measurement_id', 'G-TEST12345')
        );
});

test('admin can save a ga4 measurement id from website settings', function () {
    $this->actingAs($this->admin);

    Livewire::test(FooterSettings::class)
        ->fillForm([
            'ga4_measurement_id' => 'g-abcd1234ef',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('ga4_measurement_id'))->toBe('G-ABCD1234EF')
        ->and(Ga4::measurementId())->toBe('G-ABCD1234EF');
});

test('invalid ga4 ids are normalized away', function () {
    expect(Ga4::normalizeId('UA-123456'))->toBeNull()
        ->and(Ga4::normalizeId('G-OK99'))->toBe('G-OK99');
});
