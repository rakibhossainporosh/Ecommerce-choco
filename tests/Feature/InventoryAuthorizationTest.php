<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Policies\InventoryPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Unit::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    ProductVariant::truncate();
    DB::table('permissions')->where('name', 'inventory.history')->delete();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/*
|--------------------------------------------------------------------------
| A. ADMIN AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('1. Admin authorizes all inventory operations via centralized Gate::before', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    // Admin passes all Gate ability checks
    expect(Gate::forUser($admin)->allows('inventory.view'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('inventory.adjust'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('inventory.history'))->toBeTrue();

    // Admin passes all InventoryPolicy methods via Gate
    expect(Gate::forUser($admin)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('adjust', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewHistory', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', Inventory::class))->toBeTrue();
});

test('2. Admin has zero direct database permissions but retains full inventory access', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    // Admin role has zero direct DB permission assignments (clean security pattern)
    expect($admin->permissions)->toBeEmpty()
        ->and($admin->getPermissionsViaRoles())->toBeEmpty();

    // Yet Gate authorization allows everything
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(Gate::forUser($admin)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('adjust', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewHistory', $inventory))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| B. MANAGER AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('3. Manager can view, adjust, and view history of inventory', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    // Manager role permissions
    expect($manager->can('inventory.view'))->toBeTrue()
        ->and($manager->can('inventory.adjust'))->toBeTrue()
        ->and($manager->can('inventory.history'))->toBeTrue();

    // Gate checks with policy
    expect(Gate::forUser($manager)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('adjust', $inventory))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('viewHistory', $inventory))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('viewAny', Inventory::class))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| C. STAFF AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('4. Staff can view inventory but CANNOT adjust stock or view movement history', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    // Staff permissions
    expect($staff->can('inventory.view'))->toBeTrue()
        ->and($staff->can('inventory.adjust'))->toBeFalse()
        ->and($staff->can('inventory.history'))->toBeFalse();

    // Gate checks with policy
    expect(Gate::forUser($staff)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('viewHistory', $inventory))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('viewAny', Inventory::class))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| D. UNAUTHORIZED USER & GUEST TESTS
|--------------------------------------------------------------------------
*/

test('5. User with zero permissions is denied all inventory policy abilities', function () {
    $user = User::factory()->create();

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(Gate::forUser($user)->allows('inventory.view'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('inventory.adjust'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('inventory.history'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $inventory))->toBeFalse()
        ->and(Gate::forUser($user)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewHistory', $inventory))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Inventory::class))->toBeFalse();
});

test('6. Guest (unauthenticated) cannot authorize any inventory operation', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(Gate::allows('view', $inventory))->toBeFalse()
        ->and(Gate::allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::allows('viewHistory', $inventory))->toBeFalse()
        ->and(Gate::allows('inventory.view'))->toBeFalse()
        ->and(Gate::allows('inventory.adjust'))->toBeFalse()
        ->and(Gate::allows('inventory.history'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| E. PRODUCT VS INVENTORY PERMISSION ISOLATION TESTS
|--------------------------------------------------------------------------
*/

test('7. products.update does NOT grant inventory.adjust or inventory.history', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.update');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    // Product update is granted
    expect(Gate::forUser($user)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $variant))->toBeTrue();

    // Inventory operations are strictly DENIED
    expect(Gate::forUser($user)->allows('inventory.adjust'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($user)->allows('inventory.history'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewHistory', $inventory))->toBeFalse()
        ->and(Gate::forUser($user)->allows('inventory.view'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $inventory))->toBeFalse();
});

test('8. products.view does NOT grant inventory.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.view');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(Gate::forUser($user)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $variant))->toBeTrue();

    expect(Gate::forUser($user)->allows('inventory.view'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $inventory))->toBeFalse();
});

test('9. inventory.adjust does NOT grant products.update or products.delete', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('inventory.adjust');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(Gate::forUser($user)->allows('inventory.adjust'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('adjust', $inventory))->toBeTrue();

    expect(Gate::forUser($user)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $variant))->toBeFalse()
        ->and(Gate::forUser($user)->allows('products.delete'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $variant))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| F. DIRECT POLICY INSTANCE & ABILITY MAPPING TESTS
|--------------------------------------------------------------------------
*/

test('10. InventoryPolicy direct instance methods evaluate correct permission strings', function () {
    $policy = new InventoryPolicy;

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('inventory.view');

    $adjuster = User::factory()->create();
    $adjuster->givePermissionTo('inventory.adjust');

    $historian = User::factory()->create();
    $historian->givePermissionTo('inventory.history');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    // Viewer
    expect($policy->view($viewer, $inventory))->toBeTrue()
        ->and($policy->viewAny($viewer))->toBeTrue()
        ->and($policy->adjust($viewer, $inventory))->toBeFalse()
        ->and($policy->viewHistory($viewer, $inventory))->toBeFalse();

    // Adjuster
    expect($policy->adjust($adjuster, $inventory))->toBeTrue()
        ->and($policy->view($adjuster, $inventory))->toBeFalse()
        ->and($policy->viewAny($adjuster))->toBeFalse()
        ->and($policy->viewHistory($adjuster, $inventory))->toBeFalse();

    // Historian
    expect($policy->viewHistory($historian, $inventory))->toBeTrue()
        ->and($policy->view($historian, $inventory))->toBeFalse()
        ->and($policy->adjust($historian, $inventory))->toBeFalse();
});

test('11. Policy resolution automatically discovers InventoryPolicy for Inventory model', function () {
    $policy = Gate::getPolicyFor(Inventory::class);

    expect($policy)->toBeInstanceOf(InventoryPolicy::class);
});
