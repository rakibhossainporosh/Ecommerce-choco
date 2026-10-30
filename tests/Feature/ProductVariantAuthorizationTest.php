<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    ProductVariant::truncate();
    Product::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    ProductVariant::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Admin authorizes all product and variant operations via Gate::before', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    expect(Gate::forUser($admin)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.create'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.delete'))->toBeTrue();
});

test('2. Manager can view, create, and update products and variants but cannot delete', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    expect(Gate::forUser($manager)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('products.create'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('products.delete'))->toBeFalse();
});

test('3. Staff has view-only access to products and variants (Option A preserved)', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    expect(Gate::forUser($staff)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('products.create'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('products.delete'))->toBeFalse();
});

test('4. Guest has no access to product or variant operations', function () {
    expect(Gate::allows('products.view'))->toBeFalse()
        ->and(Gate::allows('products.create'))->toBeFalse()
        ->and(Gate::allows('products.update'))->toBeFalse()
        ->and(Gate::allows('products.delete'))->toBeFalse();
});

test('5. Admin role with can_access_admin_panel false has business abilities but panel access is forbidden', function () {
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');

    expect(Gate::forUser($adminWithoutPanel)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($adminWithoutPanel)->allows('products.delete'))->toBeTrue();

    $this->actingAs($adminWithoutPanel)
        ->get('/admin/products')
        ->assertForbidden();
});

test('6. User with can_access_admin_panel true but zero permissions has no product variant abilities', function () {
    $userWithoutPerms = User::factory()->create(['can_access_admin_panel' => true]);

    expect(Gate::forUser($userWithoutPerms)->allows('products.view'))->toBeFalse()
        ->and(Gate::forUser($userWithoutPerms)->allows('products.create'))->toBeFalse()
        ->and(Gate::forUser($userWithoutPerms)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($userWithoutPerms)->allows('products.delete'))->toBeFalse();

    $this->actingAs($userWithoutPerms)
        ->get('/admin/products')
        ->assertForbidden();
});
