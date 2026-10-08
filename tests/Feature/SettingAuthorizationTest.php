<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->manager = User::factory()->create();
    $this->manager->assignRole('Manager');

    $this->staff = User::factory()->create();
    $this->staff->assignRole('Staff');
});

test('admin has full access to settings', function () {
    expect($this->admin->can('settings.view'))->toBeTrue()
        ->and($this->admin->can('settings.create'))->toBeTrue()
        ->and($this->admin->can('settings.update'))->toBeTrue()
        ->and($this->admin->can('settings.delete'))->toBeTrue();
});

test('manager cannot modify settings', function () {
    expect($this->manager->can('settings.view'))->toBeFalse()
        ->and($this->manager->can('settings.update'))->toBeFalse()
        ->and($this->manager->can('settings.delete'))->toBeFalse();
});

test('staff cannot view settings', function () {
    expect($this->staff->can('settings.view'))->toBeFalse();
});
