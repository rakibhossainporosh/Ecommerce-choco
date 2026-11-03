<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Livewire\Topbar;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('admin topbar always offers a view store link to the storefront', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(Topbar::class)
        ->assertSuccessful()
        ->assertSee(__('View store'), false)
        ->assertSee('href="'.e(url('/')).'"', false)
        ->assertSee('target="_blank"', false);
});
