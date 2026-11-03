<?php

use App\Livewire\ClearCachesButton;
use App\Models\User;
use App\Support\ClearApplicationCaches;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('clear application caches runs optimize clear once', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('optimize:clear')
        ->andReturn(0);

    $result = app(ClearApplicationCaches::class)->handle();

    expect($result['ok'])->toBeTrue()
        ->and($result['commands'])->toBe(['optimize:clear'])
        ->and($result['failed'])->toBeNull();
});

test('admin topbar clear cache button clears caches', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Artisan::shouldReceive('call')
        ->once()
        ->with('optimize:clear')
        ->andReturn(0);

    Livewire::test(ClearCachesButton::class)
        ->assertSee(__('Clear cache'), false)
        ->call('clear')
        ->assertNotified(__('Cache cleared.'));
});

test('view-only staff do not see the clear cache button', function () {
    $this->seed(RolePermissionSeeder::class);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('settings.view');
    $this->actingAs($viewer);

    Artisan::shouldReceive('call')->never();

    Livewire::test(ClearCachesButton::class)
        ->assertDontSee(__('Clear cache'), false)
        ->call('clear')
        ->assertNotified(__('You do not have permission to update settings.'));
});
