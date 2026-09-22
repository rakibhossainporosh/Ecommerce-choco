<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);
});

test('A & C: exactly 3 expected roles exist and no unexpected roles exist', function () {
    $roles = Role::pluck('name')->sort()->values()->all();

    expect($roles)->toBe(['Admin', 'Manager', 'Staff']);
});

test('B & D: all 29 defined permissions exist and no unexpected permissions exist', function () {
    $expectedPermissions = [
        // Dashboard
        'dashboard.view',
        // Categories
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        // Brands
        'brands.view', 'brands.create', 'brands.update', 'brands.delete',
        // Units
        'units.view', 'units.create', 'units.update', 'units.delete',
        // Products
        'products.view', 'products.create', 'products.update', 'products.delete',
        // Customers
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        // Orders
        'orders.view', 'orders.create', 'orders.update', 'orders.cancel', 'orders.refund',
        // Inventory
        'inventory.view', 'inventory.adjust',
        // Reports
        'reports.view',
    ];
    sort($expectedPermissions);

    $actualPermissions = Permission::pluck('name')->sort()->values()->all();

    expect($actualPermissions)->toBe($expectedPermissions)
        ->and(Permission::count())->toBe(29);
});

test('E: Manager has exactly the 23 intended permissions', function () {
    $manager = Role::findByName('Manager', 'web');

    $expectedManagerPermissions = [
        'dashboard.view',
        'categories.view', 'categories.create', 'categories.update',
        'brands.view', 'brands.create', 'brands.update',
        'units.view', 'units.create', 'units.update',
        'products.view', 'products.create', 'products.update',
        'customers.view', 'customers.create', 'customers.update',
        'orders.view', 'orders.create', 'orders.update', 'orders.cancel',
        'inventory.view', 'inventory.adjust',
        'reports.view',
    ];
    sort($expectedManagerPermissions);

    $actualManagerPermissions = $manager->permissions->pluck('name')->sort()->values()->all();

    expect($actualManagerPermissions)->toBe($expectedManagerPermissions)
        ->and($manager->permissions()->count())->toBe(23);
});

test('F: Staff has exactly the 9 intended permissions', function () {
    $staff = Role::findByName('Staff', 'web');

    $expectedStaffPermissions = [
        'dashboard.view',
        'products.view',
        'customers.view', 'customers.create', 'customers.update',
        'orders.view', 'orders.create', 'orders.update',
        'inventory.view',
    ];
    sort($expectedStaffPermissions);

    $actualStaffPermissions = $staff->permissions->pluck('name')->sort()->values()->all();

    expect($actualStaffPermissions)->toBe($expectedStaffPermissions)
        ->and($staff->permissions()->count())->toBe(9);
});

test('G & H: Admin has no database permissions but is recognized by centralized Gate authorization', function () {
    $adminRole = Role::findByName('Admin', 'web');

    // Admin has no direct permissions in database
    expect($adminRole->permissions)->toBeEmpty();

    $adminUser = User::factory()->create();
    $adminUser->assignRole('Admin');

    // No permissions assigned via database pivots
    expect($adminUser->permissions)->toBeEmpty()
        ->and($adminUser->getPermissionsViaRoles())->toBeEmpty();

    // Recognized by centralized Gate::before authorization
    expect(Gate::forUser($adminUser)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($adminUser)->allows('products.delete'))->toBeTrue()
        ->and(Gate::forUser($adminUser)->allows('categories.delete'))->toBeTrue()
        ->and(Gate::forUser($adminUser)->allows('orders.refund'))->toBeTrue()
        ->and(Gate::forUser($adminUser)->allows('nonexistent.permission'))->toBeTrue()
        ->and($adminUser->can('products.delete'))->toBeTrue()
        ->and($adminUser->can('orders.refund'))->toBeTrue();
});

test('I: Manager does NOT receive delete, refund, or nonexistent permissions', function () {
    $managerUser = User::factory()->create();
    $managerUser->assignRole('Manager');

    // Denied permissions
    expect(Gate::forUser($managerUser)->allows('products.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('categories.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('brands.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('units.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('customers.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('orders.refund'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('reports.delete'))->toBeFalse()
        ->and(Gate::forUser($managerUser)->allows('nonexistent.permission'))->toBeFalse();

    // Granted permissions
    expect(Gate::forUser($managerUser)->allows('dashboard.view'))->toBeTrue()
        ->and(Gate::forUser($managerUser)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($managerUser)->allows('products.create'))->toBeTrue()
        ->and(Gate::forUser($managerUser)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($managerUser)->allows('orders.cancel'))->toBeTrue();
});

test('J: Staff user does NOT receive administrative, delete, refund, or catalog management permissions', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('Staff');

    // Denied permissions
    expect(Gate::forUser($staffUser)->allows('products.create'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('products.delete'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('orders.cancel'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('orders.refund'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('inventory.adjust'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('reports.view'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('categories.view'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('brands.view'))->toBeFalse()
        ->and(Gate::forUser($staffUser)->allows('units.view'))->toBeFalse();

    // Granted permissions
    expect(Gate::forUser($staffUser)->allows('dashboard.view'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('orders.view'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('orders.create'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('orders.update'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('customers.view'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('customers.create'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('customers.update'))->toBeTrue()
        ->and(Gate::forUser($staffUser)->allows('inventory.view'))->toBeTrue();
});

test('K & L: panel access remains separate from roles and permissions', function () {
    // Admin role with can_access_admin_panel = false cannot access /admin
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');

    $this->actingAs($adminWithoutPanel)
        ->get('/admin')
        ->assertForbidden();

    // Manager role with can_access_admin_panel = true can access /admin
    $managerWithPanel = User::factory()->create(['can_access_admin_panel' => true]);
    $managerWithPanel->assignRole('Manager');

    $this->actingAs($managerWithPanel)
        ->get('/admin')
        ->assertOk();
});

test('seeder is idempotent and running multiple times creates no duplicates', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe(3)
        ->and(Permission::count())->toBe(29)
        ->and(Role::findByName('Admin', 'web')->permissions()->count())->toBe(0)
        ->and(Role::findByName('Manager', 'web')->permissions()->count())->toBe(23)
        ->and(Role::findByName('Staff', 'web')->permissions()->count())->toBe(9);
});
