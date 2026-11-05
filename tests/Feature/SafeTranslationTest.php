<?php

use App\Filament\Widgets\StatsOverview;
use App\Models\User;
use App\Support\UiText;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('bare language-file names stay strings instead of returning file arrays', function () {
    foreach (['en', 'bn'] as $locale) {
        app()->setLocale($locale);

        foreach (['Payments', 'payments', 'Catalog', 'catalog', 'Mail', 'Sms', 'Enums', 'Storefront'] as $key) {
            expect(__($key))->toBeString("{$locale}:{$key}");
        }
    }
});

test('dotted payment language keys still resolve to nested strings', function () {
    app()->setLocale('bn');

    expect(__('payments.cod.label'))->toBeString()->not->toBe('payments.cod.label');
});

test('payments-only dashboard renders in english and bangla without type errors', function () {
    $role = Role::create([
        'name' => 'Pay desk',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo(['dashboard.view', 'payments.view']);

    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);
    $user->assignRole($role);

    $this->actingAs($user);

    foreach (['en', 'bn'] as $locale) {
        app()->setLocale($locale);

        Livewire::test(StatsOverview::class)
            ->assertSuccessful()
            ->assertSee(UiText::line('Payments'));
    }
});
