<?php

use App\Enums\SettingType;
use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('can list settings', function () {
    Setting::factory(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(ListSettings::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Setting::all());
});

test('can create setting', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateSetting::class)
        ->fillForm([
            'key' => 'site_maintenance_mode',
            'label' => 'Maintenance Mode',
            'type' => SettingType::Boolean->value,
            'group' => 'general',
            'value_boolean' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('settings', [
        'key' => 'site_maintenance_mode',
        'type' => SettingType::Boolean->value,
        'value' => 'true',
    ]);
});

test('can edit setting', function () {
    $setting = Setting::factory()->create([
        'key' => 'max_items',
        'type' => SettingType::Integer,
        'value' => '10',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditSetting::class, ['record' => $setting->id])
        ->fillForm([
            'value' => '20',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($setting->fresh()->value)->toBe('20');
});
