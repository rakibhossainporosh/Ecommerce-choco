<?php

use App\Enums\InventoryMovementType;
use App\Filament\Pages\StockOverview;
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
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);

    Permission::firstOrCreate([
        'name' => 'inventory.history',
        'guard_name' => 'web',
    ]);

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
function createOverviewProductAndVariant(array $variantOverrides = [], array $productOverrides = []): array
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(array_merge(['brand_id' => $brand->id], $productOverrides));
    $product->categories()->attach($category->id);

    $variant = ProductVariant::factory()->create(array_merge([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'VAR-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
    ], $variantOverrides));

    return [$product, $variant];
}

/*
|--------------------------------------------------------------------------
| CAT-8G: INVENTORY OVERVIEW & NAVIGATION TESTS
|--------------------------------------------------------------------------
*/

test('1. User with inventory.view can access Stock Overview', function () {
    $user = User::factory()->create(['can_access_admin_panel' => true]);
    $user->givePermissionTo('inventory.view');

    $this->actingAs($user)
        ->get(StockOverview::getUrl())
        ->assertSuccessful();

    Livewire::actingAs($user)
        ->test(StockOverview::class)
        ->assertSuccessful();
});

test('2. Staff with inventory.view can access Stock Overview', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    expect($staff->can('inventory.view'))->toBeTrue();

    $this->actingAs($staff)
        ->get(StockOverview::getUrl())
        ->assertSuccessful();

    Livewire::actingAs($staff)
        ->test(StockOverview::class)
        ->assertSuccessful();
});

test('3. Admin can access Stock Overview', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(StockOverview::getUrl())
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(StockOverview::class)
        ->assertSuccessful();
});

test('4. Manager can access Stock Overview', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(StockOverview::getUrl())
        ->assertSuccessful();

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertSuccessful();
});

test('5. Unauthorized user cannot access Stock Overview', function () {
    $user = User::factory()->create(['can_access_admin_panel' => true]);

    expect($user->can('inventory.view'))->toBeFalse();

    $this->actingAs($user)
        ->get(StockOverview::getUrl())
        ->assertForbidden();

    expect(StockOverview::canAccess())->toBeFalse()
        ->and(StockOverview::shouldRegisterNavigation())->toBeFalse();
});

test('6. ProductVariant table displays product, variant, SKU, stock, threshold and status', function () {
    [$product, $variant] = createOverviewProductAndVariant([
        'name' => 'Special 100g Bar',
        'sku' => 'CHOCO-SPEC-100',
    ], [
        'name' => 'Dark Truffle Chocolate',
    ]);

    Inventory::createForVariant(
        variant: $variant,
        openingQuantity: 42,
        lowStockThreshold: 10,
    );

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$variant])
        ->assertTableColumnStateSet('product.name', 'Dark Truffle Chocolate', $variant)
        ->assertTableColumnStateSet('name', 'Special 100g Bar', $variant)
        ->assertTableColumnStateSet('sku', 'CHOCO-SPEC-100', $variant)
        ->assertTableColumnStateSet('inventory.quantity', 42, $variant)
        ->assertTableColumnStateSet('inventory.low_stock_threshold', 10, $variant)
        ->assertTableColumnStateSet('stock_status', 'In Stock', $variant);
});

test('7. Stock status correctly identifies In Stock, Low Stock, Out of Stock, and Not Initialized', function () {
    // 1. In Stock: quantity > threshold
    [$product1, $inStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($inStockVariant, openingQuantity: 20, lowStockThreshold: 5);

    // 2. Low Stock: quantity > 0 AND quantity <= threshold
    [$product2, $lowStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($lowStockVariant, openingQuantity: 4, lowStockThreshold: 5);

    // 3. Out of Stock: quantity = 0
    [$product3, $outOfStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($outOfStockVariant, openingQuantity: 0, lowStockThreshold: 5);

    // 4. Not Initialized: no inventory record
    [$product4, $notInitVariant] = createOverviewProductAndVariant();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertTableColumnStateSet('stock_status', 'In Stock', $inStockVariant)
        ->assertTableColumnStateSet('stock_status', 'Low Stock', $lowStockVariant)
        ->assertTableColumnStateSet('stock_status', 'Out of Stock', $outOfStockVariant)
        ->assertTableColumnStateSet('stock_status', 'Not Initialized', $notInitVariant);
});

test('8. Soft-deleted variants do not appear', function () {
    [$product, $variant1] = createOverviewProductAndVariant(['is_default' => true]);
    $unit = Unit::first();
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'VAR-DELETE-ME-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
        'is_default' => false,
    ]);
    Inventory::createForVariant($variant2, openingQuantity: 10);

    $variant2->delete();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$variant1])
        ->assertCanNotSeeTableRecords([$variant2]);
});

test('9. Inactive variants appear by default', function () {
    [$product, $variant] = createOverviewProductAndVariant([
        'is_active' => false,
    ]);
    Inventory::createForVariant($variant, openingQuantity: 15);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$variant]);
});

test('10. Variant Status filter works for active and inactive', function () {
    [$product1, $activeVariant] = createOverviewProductAndVariant(['is_active' => true]);
    [$product2, $inactiveVariant] = createOverviewProductAndVariant(['is_active' => false]);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    // Default: both visible
    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$activeVariant, $inactiveVariant])
        // Filter: active
        ->filterTable('variant_status', 'active')
        ->assertCanSeeTableRecords([$activeVariant])
        ->assertCanNotSeeTableRecords([$inactiveVariant])
        // Filter: inactive
        ->filterTable('variant_status', 'inactive')
        ->assertCanSeeTableRecords([$inactiveVariant])
        ->assertCanNotSeeTableRecords([$activeVariant]);
});

test('11. Stock Status filter works for in_stock, low_stock, out_of_stock, and not_initialized', function () {
    [$product1, $inStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($inStockVariant, openingQuantity: 20, lowStockThreshold: 5);

    [$product2, $lowStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($lowStockVariant, openingQuantity: 3, lowStockThreshold: 5);

    [$product3, $outOfStockVariant] = createOverviewProductAndVariant();
    Inventory::createForVariant($outOfStockVariant, openingQuantity: 0, lowStockThreshold: 5);

    [$product4, $notInitVariant] = createOverviewProductAndVariant();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        // in_stock
        ->filterTable('stock_status', 'in_stock')
        ->assertCanSeeTableRecords([$inStockVariant])
        ->assertCanNotSeeTableRecords([$lowStockVariant, $outOfStockVariant, $notInitVariant])
        // low_stock
        ->filterTable('stock_status', 'low_stock')
        ->assertCanSeeTableRecords([$lowStockVariant])
        ->assertCanNotSeeTableRecords([$inStockVariant, $outOfStockVariant, $notInitVariant])
        // out_of_stock
        ->filterTable('stock_status', 'out_of_stock')
        ->assertCanSeeTableRecords([$outOfStockVariant])
        ->assertCanNotSeeTableRecords([$inStockVariant, $lowStockVariant, $notInitVariant])
        // not_initialized
        ->filterTable('stock_status', 'not_initialized')
        ->assertCanSeeTableRecords([$notInitVariant])
        ->assertCanNotSeeTableRecords([$inStockVariant, $lowStockVariant, $outOfStockVariant]);
});

test('12. Search works for Product name', function () {
    [$product1, $variant1] = createOverviewProductAndVariant([], ['name' => 'Belgian Hazelnut Dark']);
    [$product2, $variant2] = createOverviewProductAndVariant([], ['name' => 'Swiss White Cream']);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->searchTable('Belgian Hazelnut')
        ->assertCanSeeTableRecords([$variant1])
        ->assertCanNotSeeTableRecords([$variant2]);
});

test('13. Search works for SKU', function () {
    [$product1, $variant1] = createOverviewProductAndVariant(['sku' => 'SEARCH-SKU-ALPHA']);
    [$product2, $variant2] = createOverviewProductAndVariant(['sku' => 'SEARCH-SKU-BETA']);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->searchTable('SEARCH-SKU-ALPHA')
        ->assertCanSeeTableRecords([$variant1])
        ->assertCanNotSeeTableRecords([$variant2]);
});

test('14. Pagination works with default 25 records per page', function () {
    $variants = [];
    for ($i = 0; $i < 30; $i++) {
        [, $variant] = createOverviewProductAndVariant(['sku' => sprintf('PAG-SKU-%03d', $i)]);
        $variants[] = $variant;
    }

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $firstPageRecords = array_slice($variants, 0, 25);
    $secondPageRecords = array_slice($variants, 25);

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords($firstPageRecords)
        ->assertCanNotSeeTableRecords($secondPageRecords);
});

test('15. Stock sorting works ascending and descending', function () {
    [$p1, $varLow] = createOverviewProductAndVariant(['sku' => 'SORT-LOW-STOCK']);
    Inventory::createForVariant($varLow, openingQuantity: 5);

    [$p2, $varHigh] = createOverviewProductAndVariant(['sku' => 'SORT-HIGH-STOCK']);
    Inventory::createForVariant($varHigh, openingQuantity: 100);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->sortTable('inventory.quantity', 'asc')
        ->assertCanSeeTableRecords([$varLow, $varHigh], inOrder: true)
        ->sortTable('inventory.quantity', 'desc')
        ->assertCanSeeTableRecords([$varHigh, $varLow], inOrder: true);
});

test('16. Threshold sorting works ascending and descending', function () {
    [$p1, $varLowThresh] = createOverviewProductAndVariant(['sku' => 'THRESH-LOW']);
    Inventory::createForVariant($varLowThresh, openingQuantity: 10, lowStockThreshold: 2);

    [$p2, $varHighThresh] = createOverviewProductAndVariant(['sku' => 'THRESH-HIGH']);
    Inventory::createForVariant($varHighThresh, openingQuantity: 10, lowStockThreshold: 50);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->sortTable('inventory.low_stock_threshold', 'asc')
        ->assertCanSeeTableRecords([$varLowThresh, $varHighThresh], inOrder: true)
        ->sortTable('inventory.low_stock_threshold', 'desc')
        ->assertCanSeeTableRecords([$varHighThresh, $varLowThresh], inOrder: true);
});

test('17. Updated sorting works ascending and descending', function () {
    [$p1, $varEarly] = createOverviewProductAndVariant(['sku' => 'UPDATE-EARLY']);
    $inv1 = Inventory::createForVariant($varEarly, openingQuantity: 10);
    $inv1->updateQuietly(['updated_at' => Carbon::now()->subDays(5)]);

    [$p2, $varRecent] = createOverviewProductAndVariant(['sku' => 'UPDATE-RECENT']);
    $inv2 = Inventory::createForVariant($varRecent, openingQuantity: 10);
    $inv2->updateQuietly(['updated_at' => Carbon::now()]);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->sortTable('inventory.updated_at', 'asc')
        ->assertCanSeeTableRecords([$varEarly, $varRecent], inOrder: true)
        ->sortTable('inventory.updated_at', 'desc')
        ->assertCanSeeTableRecords([$varRecent, $varEarly], inOrder: true);
});

test('18. KPI counts are correct and mutually exclusive', function () {
    // 2 in-stock
    [$p1, $v1] = createOverviewProductAndVariant();
    Inventory::createForVariant($v1, openingQuantity: 20, lowStockThreshold: 5);
    [$p2, $v2] = createOverviewProductAndVariant();
    Inventory::createForVariant($v2, openingQuantity: 30, lowStockThreshold: 10);

    // 1 low-stock
    [$p3, $v3] = createOverviewProductAndVariant();
    Inventory::createForVariant($v3, openingQuantity: 5, lowStockThreshold: 5);

    // 1 out-of-stock
    [$p4, $v4] = createOverviewProductAndVariant();
    Inventory::createForVariant($v4, openingQuantity: 0, lowStockThreshold: 5);

    // 2 not-initialized
    [$p5, $v5] = createOverviewProductAndVariant();
    [$p6, $v6] = createOverviewProductAndVariant();

    // 1 soft-deleted (must not count)
    $unit = Unit::first();
    $v_deleted = ProductVariant::factory()->create([
        'product_id' => $p1->id,
        'unit_id' => $unit->id,
        'sku' => 'VAR-DEL-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
        'is_default' => false,
    ]);
    Inventory::createForVariant($v_deleted, openingQuantity: 15, lowStockThreshold: 5);
    $v_deleted->delete();

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $component = Livewire::actingAs($manager)->test(StockOverview::class);
    $kpis = $component->instance()->getKpiSummary();

    expect($kpis['total_variants'])->toBe(6)
        ->and($kpis['in_stock'])->toBe(2)
        ->and($kpis['low_stock'])->toBe(1)
        ->and($kpis['out_of_stock'])->toBe(1)
        ->and($kpis['not_initialized'])->toBe(2);

    expect($kpis['in_stock'] + $kpis['low_stock'] + $kpis['out_of_stock'] + $kpis['not_initialized'])
        ->toBe($kpis['total_variants']);
});

test('19. Adjust Stock is visible only with inventory.adjust', function () {
    [$product, $variant] = createOverviewProductAndVariant();
    Inventory::createForVariant($variant, openingQuantity: 10);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    // Manager has inventory.adjust
    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertTableActionVisible('adjustStock', $variant);

    // Staff lacks inventory.adjust
    Livewire::actingAs($staff)
        ->test(StockOverview::class)
        ->assertTableActionHidden('adjustStock', $variant);
});

test('20. Stock History is visible only with inventory.history', function () {
    [$product, $variant] = createOverviewProductAndVariant();
    Inventory::createForVariant($variant, openingQuantity: 10);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    // Manager has inventory.history
    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertTableActionVisible('stockHistory', $variant);

    // Staff lacks inventory.history
    Livewire::actingAs($staff)
        ->test(StockOverview::class)
        ->assertTableActionHidden('stockHistory', $variant);
});

test('21. Staff can view stock but cannot adjust stock or view history', function () {
    [$product, $variant] = createOverviewProductAndVariant();
    Inventory::createForVariant($variant, openingQuantity: 50, lowStockThreshold: 10);

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    expect($staff->can('inventory.view'))->toBeTrue()
        ->and($staff->can('inventory.adjust'))->toBeFalse()
        ->and($staff->can('inventory.history'))->toBeFalse();

    // Staff can access and see data
    Livewire::actingAs($staff)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$variant])
        ->assertTableColumnStateSet('inventory.quantity', 50, $variant)
        ->assertTableActionHidden('adjustStock', $variant)
        ->assertTableActionHidden('stockHistory', $variant);
});

test('22. Overview rendering does not create missing Inventory records', function () {
    [$product, $variant] = createOverviewProductAndVariant();

    expect($variant->inventory)->toBeNull();
    expect(Inventory::where('product_variant_id', $variant->id)->count())->toBe(0);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertCanSeeTableRecords([$variant])
        ->assertTableColumnStateSet('inventory.quantity', null, $variant)
        ->assertTableColumnStateSet('stock_status', 'Not Initialized', $variant);

    expect(Inventory::where('product_variant_id', $variant->id)->count())->toBe(0);
    expect($variant->fresh()->inventory)->toBeNull();
});

test('23. ProductVariant A cannot expose Variant B inventory or history', function () {
    [$p1, $varA] = createOverviewProductAndVariant(['sku' => 'SKU-ISOLATED-A']);
    $invA = Inventory::createForVariant($varA, openingQuantity: 10);
    $invA->adjustIn(5, 'Restock', 'Variant A restock', auth()->user());

    [$p2, $varB] = createOverviewProductAndVariant(['sku' => 'SKU-ISOLATED-B']);
    $invB = Inventory::createForVariant($varB, openingQuantity: 99);
    $invB->adjustIn(20, 'Restock', 'Variant B restock', auth()->user());

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    // Rendering history for Variant A
    Livewire::actingAs($manager)
        ->test(VariantStockHistory::class, ['variant' => $varA])
        ->assertSee('Variant A restock')
        ->assertDontSee('Variant B restock');

    // Rendering history for Variant B
    Livewire::actingAs($manager)
        ->test(VariantStockHistory::class, ['variant' => $varB])
        ->assertSee('Variant B restock')
        ->assertDontSee('Variant A restock');
});

test('24. No N+1 regression is introduced for ProductVariant/Product/Inventory loading', function () {
    for ($i = 0; $i < 10; $i++) {
        [, $var] = createOverviewProductAndVariant();
        Inventory::createForVariant($var, openingQuantity: $i * 5);
    }

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->assertSuccessful();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Verify eager loading queries exist for products and inventories
    $eagerLoadedProducts = collect($queries)->contains(fn ($q) => str_contains($q['query'], '`products`') && str_contains($q['query'], 'where `products`.`id` in'));
    $eagerLoadedInventories = collect($queries)->contains(fn ($q) => str_contains($q['query'], '`inventories`') && str_contains($q['query'], 'where `inventories`.`product_variant_id` in'));

    expect($eagerLoadedProducts)->toBeTrue('Products must be eager loaded in a single IN query.')
        ->and($eagerLoadedInventories)->toBeTrue('Inventories must be eager loaded in a single IN query.');
});

test('25. Existing CAT-8B mutation methods remain the only authoritative stock mutation path', function () {
    [$product, $variant] = createOverviewProductAndVariant();
    $inventory = Inventory::createForVariant($variant, openingQuantity: 10);

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    Livewire::actingAs($manager)
        ->test(StockOverview::class)
        ->callTableAction('adjustStock', $variant, [
            'type' => 'in',
            'quantity' => 15,
            'reason' => 'Restock',
            'note' => 'Delivery from factory',
        ])
        ->assertHasNoTableActionErrors();

    $inventory->refresh();
    expect($inventory->quantity)->toBe(25);

    $movement = InventoryMovement::where('inventory_id', $inventory->id)->latest('id')->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe(InventoryMovementType::AdjustmentIn)
        ->and($movement->quantity)->toBe(15)
        ->and($movement->quantity_before)->toBe(10)
        ->and($movement->quantity_after)->toBe(25)
        ->and($movement->reason)->toBe('Restock')
        ->and($movement->note)->toBe('Delivery from factory');
});
