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

test('admin has full access to reviews', function () {
    expect($this->admin->can('reviews.view'))->toBeTrue()
        ->and($this->admin->can('reviews.create'))->toBeTrue()
        ->and($this->admin->can('reviews.update'))->toBeTrue()
        ->and($this->admin->can('reviews.delete'))->toBeTrue();
});

test('manager has full access to reviews', function () {
    expect($this->manager->can('reviews.view'))->toBeTrue()
        ->and($this->manager->can('reviews.update'))->toBeTrue()
        ->and($this->manager->can('reviews.delete'))->toBeTrue();
});

test('staff has view only access to reviews', function () {
    expect($this->staff->can('reviews.view'))->toBeTrue()
        ->and($this->staff->can('reviews.create'))->toBeFalse()
        ->and($this->staff->can('reviews.update'))->toBeFalse()
        ->and($this->staff->can('reviews.delete'))->toBeFalse();
});
