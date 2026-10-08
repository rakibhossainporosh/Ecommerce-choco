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

test('admin has full access to return requests', function () {
    expect($this->admin->can('returns.view'))->toBeTrue()
        ->and($this->admin->can('returns.create'))->toBeTrue()
        ->and($this->admin->can('returns.update'))->toBeTrue()
        ->and($this->admin->can('returns.delete'))->toBeTrue();
});

test('manager has full access to return requests', function () {
    expect($this->manager->can('returns.view'))->toBeTrue()
        ->and($this->manager->can('returns.update'))->toBeTrue()
        ->and($this->manager->can('returns.delete'))->toBeTrue();
});

test('staff has view only access to return requests', function () {
    expect($this->staff->can('returns.view'))->toBeTrue()
        ->and($this->staff->can('returns.create'))->toBeFalse()
        ->and($this->staff->can('returns.update'))->toBeFalse()
        ->and($this->staff->can('returns.delete'))->toBeFalse();
});
