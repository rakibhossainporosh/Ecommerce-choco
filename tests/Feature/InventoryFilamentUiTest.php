<?php

use App\Enums\InventoryMovementType;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
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
use Illuminate\Support\Facades\DB;
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

// Test helpers
function createUiTestProductWithVariant(): array
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'VAR-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
    ]);

    return [$product, $variant];
}

/*
|--------------------------------------------------------------------------
| 1. STOCK DISPLAY & STATUS TESTS
|--------------------------------------------------------------------------
*/

test('1. Variant with inventory displays stock, threshold, and in-stock status', function () {
    [$product, $variant] = createUiTestProductWithVariant();

    $inventory = Inventory::createForVariant(
        variant: $variant,
        openingQuantity: 25,
        lowStockThreshold: 5,
    );

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertCanSeeTableRecords([$variant])
        ->assertTableColumnStateSet('inventory.quantity', 25, $variant)
        ->assertTableColumnStateSet('inventory.low_stock_threshold', 5, $variant)
        ->assertTableColumnStateSet('stock_status', 'In Stock', $variant);
});

test('2. Variant with low stock displays Low Stock status', function () {
    [$product, $variant] = createUiTestProductWithVariant();

    Inventory::createForVariant(
        variant: $variant,
        openingQuantity: 3,
        lowStockThreshold: 5,
    );

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnStateSet('inventory.quantity', 3, $variant)
        ->assertTableColumnStateSet('inventory.low_stock_threshold', 5, $variant)
        ->assertTableColumnStateSet('stock_status', 'Low Stock', $variant);
});

test('3. Variant with zero stock displays Out of Stock status', function () {
    [$product, $variant] = createUiTestProductWithVariant();

    Inventory::createForVariant(
        variant: $variant,
        openingQuantity: 0,
        lowStockThreshold: 5,
    );

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnStateSet('inventory.quantity', 0, $variant)
        ->assertTableColumnStateSet('inventory.low_stock_threshold', 5, $variant)
        ->assertTableColumnStateSet('stock_status', 'Out of Stock', $variant);
});

test('4. Variant without inventory record displays 0 and Out of Stock gracefully', function () {
    [$product, $variant] = createUiTestProductWithVariant();

    expect($variant->inventory)->toBeNull();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnStateSet('stock_status', 'Out of Stock', $variant);
});

/*
|--------------------------------------------------------------------------
| 2. AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('5. Admin has full inventory UI access: view stock, adjust stock, and edit threshold', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnVisible('inventory.quantity')
        ->assertTableColumnVisible('inventory.low_stock_threshold')
        ->assertTableColumnVisible('stock_status')
        ->assertTableActionVisible('adjustStock', $variant)
        ->assertTableActionVisible('editThreshold', $variant);
});

test('6. Manager has full inventory UI access: view stock, adjust stock, and edit threshold', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnVisible('inventory.quantity')
        ->assertTableColumnVisible('inventory.low_stock_threshold')
        ->assertTableColumnVisible('stock_status')
        ->assertTableActionVisible('adjustStock', $variant)
        ->assertTableActionVisible('editThreshold', $variant);
});

test('7. Staff can view stock but cannot adjust stock or edit threshold', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnVisible('inventory.quantity')
        ->assertTableColumnVisible('inventory.low_stock_threshold')
        ->assertTableColumnVisible('stock_status')
        ->assertTableActionHidden('adjustStock', $variant)
        ->assertTableActionHidden('editThreshold', $variant);
});

test('8. Staff cannot see or execute adjustStock action', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableActionHidden('adjustStock', $variant);

    expect(Gate::forUser($staff)->allows('adjust', Inventory::class))->toBeFalse();
    expect($variant->fresh()->inventory->quantity)->toBe(10);
});

test('9. Staff cannot see or execute editThreshold action', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableActionHidden('editThreshold', $variant);

    expect(Gate::forUser($staff)->allows('adjust', Inventory::class))->toBeFalse();
    expect($variant->fresh()->inventory->low_stock_threshold)->toBe(2);
});

test('10. Unauthorized user without inventory permissions cannot see inventory columns', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $user = User::factory()->create(['can_access_admin_panel' => true]);
    // No roles assigned

    $this->actingAs($user);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableColumnHidden('inventory.quantity')
        ->assertTableColumnHidden('inventory.low_stock_threshold')
        ->assertTableColumnHidden('stock_status')
        ->assertTableActionHidden('adjustStock', $variant)
        ->assertTableActionHidden('editThreshold', $variant);
});

/*
|--------------------------------------------------------------------------
| 3. STOCK IN TESTS
|--------------------------------------------------------------------------
*/

test('11. Stock In successfully increases stock and creates domain movement', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20, 5);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 10,
            'reason' => 'Restock',
            'note' => 'Supplier shipment #104',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Stock adjusted successfully.');

    expect($inventory->fresh()->quantity)->toBe(30);

    $movement = InventoryMovement::where('product_variant_id', $variant->id)->latest('id')->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe(InventoryMovementType::AdjustmentIn)
        ->and($movement->quantity)->toBe(10)
        ->and($movement->quantity_before)->toBe(20)
        ->and($movement->quantity_after)->toBe(30)
        ->and($movement->reason)->toBe('Restock')
        ->and($movement->note)->toBe('Supplier shipment #104')
        ->and($movement->created_by)->toBe($manager->id);
});

test('12. Stock In with Other reason stores custom reason', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 5,
            'reason' => 'Other',
            'custom_reason' => 'Found misplaced inventory',
        ])
        ->assertHasNoTableActionErrors();

    expect($variant->fresh()->inventory->quantity)->toBe(15);

    $movement = InventoryMovement::latest('id')->first();
    expect($movement->reason)->toBe('Found misplaced inventory');
});

/*
|--------------------------------------------------------------------------
| 4. STOCK OUT TESTS
|--------------------------------------------------------------------------
*/

test('13. Stock Out successfully decreases stock and creates domain movement', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20, 5);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'out',
            'quantity' => 5,
            'reason' => 'Damaged',
            'note' => 'Damaged in transit',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Stock adjusted successfully.');

    expect($inventory->fresh()->quantity)->toBe(15);

    $movement = InventoryMovement::where('product_variant_id', $variant->id)->latest('id')->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe(InventoryMovementType::AdjustmentOut)
        ->and($movement->quantity)->toBe(5)
        ->and($movement->quantity_before)->toBe(20)
        ->and($movement->quantity_after)->toBe(15)
        ->and($movement->reason)->toBe('Damaged')
        ->and($movement->created_by)->toBe($manager->id);
});

/*
|--------------------------------------------------------------------------
| 5. INSUFFICIENT STOCK HANDLING
|--------------------------------------------------------------------------
*/

test('14. Stock Out with insufficient stock fails cleanly, preserves quantity, and creates no movement', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 3, 2);

    $movementsBeforeCount = InventoryMovement::count();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'out',
            'quantity' => 5,
            'reason' => 'Damaged',
        ])
        ->assertNotified('Stock Adjustment Failed');

    // Persisted quantity remains unchanged
    expect($inventory->fresh()->quantity)->toBe(3);

    // No new movement was recorded
    expect(InventoryMovement::count())->toBe($movementsBeforeCount);
});

/*
|--------------------------------------------------------------------------
| 6. VALIDATION TESTS
|--------------------------------------------------------------------------
*/

test('15. Adjust Stock rejects zero, negative, non-integer, and missing quantity', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    // Zero quantity rejected
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 0,
            'reason' => 'Restock',
        ])
        ->assertHasTableActionErrors(['quantity']);

    // Negative quantity rejected
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => -5,
            'reason' => 'Restock',
        ])
        ->assertHasTableActionErrors(['quantity']);

    // Missing quantity rejected
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'reason' => 'Restock',
        ])
        ->assertHasTableActionErrors(['quantity']);

    // Non-integer / decimal rejected
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 3.5,
            'reason' => 'Restock',
        ])
        ->assertHasTableActionErrors(['quantity']);
});

test('16. Adjust Stock rejects missing reason', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 10, 2);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 5,
        ])
        ->assertHasTableActionErrors(['reason']);
});

/*
|--------------------------------------------------------------------------
| 7. THRESHOLD EDITING TESTS
|--------------------------------------------------------------------------
*/

test('17. Authorized user can edit low stock threshold without creating movements or changing quantity', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20, 2);

    $movementsBeforeCount = InventoryMovement::count();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('editThreshold', $variant, [
            'low_stock_threshold' => 12,
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Low stock threshold updated successfully.');

    expect($inventory->fresh()->low_stock_threshold)->toBe(12)
        ->and($inventory->fresh()->quantity)->toBe(20) // Quantity unchanged
        ->and(InventoryMovement::count())->toBe($movementsBeforeCount); // No movement created
});

test('18. Low stock threshold rejects negative values', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    Inventory::createForVariant($variant, 20, 2);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('editThreshold', $variant, [
            'low_stock_threshold' => -3,
        ])
        ->assertHasTableActionErrors(['low_stock_threshold']);
});

/*
|--------------------------------------------------------------------------
| 8. OPENING STOCK & UNINITIALIZED INVENTORY TESTS
|--------------------------------------------------------------------------
*/

test('19. Adjust stock on variant without inventory record initializes inventory safely', function () {
    [$product, $variant] = createUiTestProductWithVariant();

    expect($variant->inventory)->toBeNull();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 15,
            'reason' => 'Opening Stock',
            'note' => 'Initial stock arrival',
        ])
        ->assertHasNoTableActionErrors();

    $freshInventory = $variant->fresh()->inventory;
    expect($freshInventory)->not->toBeNull()
        ->and($freshInventory->quantity)->toBe(15);

    $movement = InventoryMovement::where('product_variant_id', $variant->id)->first();
    expect($movement->type)->toBe(InventoryMovementType::Opening)
        ->and($movement->quantity)->toBe(15);
});

test('20. Opening Stock on already initialized inventory with stock fails cleanly', function () {
    [$product, $variant] = createUiTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20, 5);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 10,
            'reason' => 'Opening Stock',
        ])
        ->assertNotified('Stock Adjustment Failed');

    // Quantity unchanged
    expect($inventory->fresh()->quantity)->toBe(20);
});

/*
|--------------------------------------------------------------------------
| 9. PERFORMANCE / N+1 QUERY VERIFICATION
|--------------------------------------------------------------------------
*/

test('21. VariantsRelationManager eager-loads inventory to prevent N+1 queries', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    // Create 5 variants with inventories
    for ($i = 0; $i < 5; $i++) {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => "PERF-VAR-{$i}",
        ]);
        Inventory::createForVariant($variant, 10 + $i, 2);
    }

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    DB::enableQueryLog();

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ]);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Find queries targeting the inventories table
    $inventoryQueries = array_filter($queries, function ($q) {
        return str_contains($q['query'], 'inventories');
    });

    // Should be at most 1 query for eager-loading inventories (where in (...))
    // NOT 5 separate queries (1 per variant row)
    expect(count($inventoryQueries))->toBeLessThanOrEqual(2);
});
