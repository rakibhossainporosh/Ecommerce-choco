<?php

use App\Enums\InventoryMovementType;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Livewire\VariantStockHistory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
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
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    DB::table('permissions')->where('name', 'inventory.history')->delete();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createHistoryTestProductWithVariant(): array
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'HIST-'.uniqid(),
        'cost_price' => 100,
        'selling_price' => 150,
        'compare_at_price' => 200,
        'is_active' => true,
    ]);

    return [$product, $variant];
}

/*
|--------------------------------------------------------------------------
| 1. AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('1. Admin can access history action and view movement history component', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20, 5);

    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    // Visible in VariantsRelationManager table
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])->assertTableActionVisible('stockHistory', $variant);

    // Accessible in VariantStockHistory Livewire component
    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful()
        ->assertSee('Inventory Movement Ledger')
        ->assertSee('20');
});

test('2. Manager can access history action and view movement history component', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 15, 3);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    // Visible in VariantsRelationManager table
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])->assertTableActionVisible('stockHistory', $variant);

    // Accessible in VariantStockHistory Livewire component
    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful()
        ->assertSee('Inventory Movement Ledger')
        ->assertSee('15');
});

test('3. Staff cannot access history action and is forbidden from viewing component', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);

    // Hidden in VariantsRelationManager table
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])->assertTableActionHidden('stockHistory', $variant);

    // Policy check fails
    expect(Gate::forUser($staff)->allows('viewHistory', Inventory::class))->toBeFalse();

    // Direct Livewire component access forbidden
    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertForbidden();
});

test('4. User with inventory.history permission explicitly granted can access history', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $user = User::factory()->create(['can_access_admin_panel' => true]);
    $user->givePermissionTo('inventory.history');

    $this->actingAs($user);

    expect(Gate::forUser($user)->allows('viewHistory', Inventory::class))->toBeTrue();

    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful();
});

test('5. Unauthorized user without inventory.history permission is forbidden from history component', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $user = User::factory()->create(['can_access_admin_panel' => true]);
    // No roles, no permissions

    $this->actingAs($user);

    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| 2. QUERY SCOPING & ORDERING TESTS
|--------------------------------------------------------------------------
*/

test('6. History is scoped strictly to the selected ProductVariant', function () {
    [$productA, $variantA] = createHistoryTestProductWithVariant();
    [$productB, $variantB] = createHistoryTestProductWithVariant();

    $invA = Inventory::createForVariant($variantA, 25, reason: 'Variant A opening');
    $invB = Inventory::createForVariant($variantB, 50, reason: 'Variant B opening');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    $componentA = Livewire::test(VariantStockHistory::class, ['variant' => $variantA])
        ->assertSuccessful()
        ->assertSee('Variant A opening')
        ->assertDontSee('Variant B opening');

    $movementsA = $componentA->viewData('movements');
    expect($movementsA)->toHaveCount(1)
        ->and($movementsA->first()->product_variant_id)->toBe($variantA->id);
});

test('7. Movements are ordered newest first (created_at DESC, id DESC)', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    Carbon::setTestNow('2026-09-25 09:00:00');
    $inventory = Inventory::createForVariant($variant, 10);

    Carbon::setTestNow('2026-09-25 10:00:00');
    $inventory->adjustIn(5, reason: 'First movement');

    Carbon::setTestNow('2026-09-25 11:00:00');
    $inventory->adjustIn(10, reason: 'Second movement');

    Carbon::setTestNow('2026-09-25 12:00:00');
    $inventory->adjustOut(3, reason: 'Third movement');

    Carbon::setTestNow();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    $component = Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful();

    $movements = $component->viewData('movements');
    expect($movements)->toHaveCount(4); // Opening + 3 adjustments

    $reasons = $movements->pluck('reason')->all();
    expect($reasons)->toBe([
        'Third movement',
        'Second movement',
        'First movement',
        'Initial opening stock',
    ]);
});

/*
|--------------------------------------------------------------------------
| 3. PAGINATION TESTS
|--------------------------------------------------------------------------
*/

test('8. Pagination works: displays 10 movements per page and navigates correctly', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10, reason: 'Movement 0');

    // Create 14 additional movements (15 total)
    for ($i = 1; $i <= 14; $i++) {
        $inventory->adjustIn(1, reason: "Movement {$i}");
    }

    expect($variant->inventoryMovements()->count())->toBe(15);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    // Page 1 displays 10 records
    $page1 = Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful();

    $page1Movements = $page1->viewData('movements');
    expect($page1Movements->count())->toBe(10)
        ->and($page1Movements->total())->toBe(15)
        ->and($page1Movements->hasMorePages())->toBeTrue();

    // Page 2 displays the remaining 5 records
    $page2 = Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->call('gotoPage', 2)
        ->assertSuccessful();

    $page2Movements = $page2->viewData('movements');
    expect($page2Movements->count())->toBe(5)
        ->and($page2Movements->currentPage())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| 4. DATA FIELDS DISPLAY TESTS
|--------------------------------------------------------------------------
*/

test('9. History displays all 9 required fields correctly', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant);

    $admin = User::factory()->create(['name' => 'John Admin', 'can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    // Create a movement with explicit fields including reference
    $movement = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $variant->id,
        'type' => InventoryMovementType::AdjustmentIn,
        'quantity' => 20,
        'quantity_before' => 0,
        'quantity_after' => 20,
        'reason' => 'Audit Correction #42',
        'note' => 'Warehouse box re-count inspection note',
        'created_by' => $admin->id,
        'reference_type' => 'App\Models\Order',
        'reference_id' => 999,
    ]);

    $this->actingAs($admin);

    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful()
        // 1. Date
        ->assertSee($movement->created_at->format('Y-m-d H:i:s'))
        // 2. Type
        ->assertSee('Stock In')
        // 3. Before
        ->assertSee('0')
        // 4. Change
        ->assertSee('+20')
        // 5. After
        ->assertSee('20')
        // 6. Reason
        ->assertSee('Audit Correction #42')
        // 7. Note
        ->assertSee('Warehouse box re-count inspection note')
        // 8. User
        ->assertSee('John Admin')
        // 9. Reference
        ->assertSee('Order #999');
});

test('10. Reference displays dash placeholder when null', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10, reason: 'No reference movement');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    $movement = $variant->inventoryMovements()->first();
    expect($movement->reference_type)->toBeNull()
        ->and($movement->reference_id)->toBeNull();

    Livewire::test(VariantStockHistory::class, ['variant' => $variant])
        ->assertSuccessful()
        ->assertSee('—');
});

/*
|--------------------------------------------------------------------------
| 5. READ-ONLY REQUIREMENT & PERFORMANCE TESTS
|--------------------------------------------------------------------------
*/

test('11. History component is strictly read-only and contains no mutation methods', function () {
    $reflector = new ReflectionClass(VariantStockHistory::class);
    $methods = array_map(fn ($m) => $m->getName(), $reflector->getMethods(ReflectionMethod::IS_PUBLIC));

    // Must NOT have any mutation methods
    expect($methods)->not->toContain('update')
        ->and($methods)->not->toContain('edit')
        ->and($methods)->not->toContain('delete')
        ->and($methods)->not->toContain('destroy')
        ->and($methods)->not->toContain('adjust')
        ->and($methods)->not->toContain('save')
        ->and($methods)->not->toContain('create');
});

test('12. Query behavior eager-loads creator and avoids N+1 queries', function () {
    [$product, $variant] = createHistoryTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant);

    $users = User::factory()->count(5)->create();

    // Create 10 movements across multiple users
    for ($i = 0; $i < 10; $i++) {
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $variant->id,
            'type' => InventoryMovementType::AdjustmentIn,
            'quantity' => 5,
            'quantity_before' => $i * 5,
            'quantity_after' => ($i + 1) * 5,
            'reason' => "Batch {$i}",
            'created_by' => $users[$i % 5]->id,
        ]);
    }

    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    DB::enableQueryLog();

    Livewire::test(VariantStockHistory::class, ['variant' => $variant]);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Query 1: count query for pagination
    // Query 2: movements query
    // Query 3: eager-loaded users query (where id in (...))
    $userQueries = array_filter($queries, function ($q) {
        return str_contains($q['query'], 'users') && ! str_contains($q['query'], 'permissions');
    });

    // Should eager load users in at most 1 query, not 1 query per movement row (which would be 10)
    expect(count($userQueries))->toBeLessThanOrEqual(2);
});
