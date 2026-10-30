<?php

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryException;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

function createHardeningTestProductWithVariant(): array
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'HARD-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
    ]);

    return [$product, $variant];
}

/*
|--------------------------------------------------------------------------
| 1. STOCK INVARIANT TESTS
|--------------------------------------------------------------------------
*/

test('1. Stock In with positive integer succeeds and increases stock', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10);

    $movement = $inventory->adjustIn(15, 'Restock');

    expect($inventory->fresh()->quantity)->toBe(25)
        ->and($movement->quantity_after)->toBe(25)
        ->and($inventory->fresh()->quantity)->toBeGreaterThanOrEqual(0);
});

test('2. Stock Out with positive integer succeeds when sufficient stock exists', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20);

    $movement = $inventory->adjustOut(8, 'Damaged');

    expect($inventory->fresh()->quantity)->toBe(12)
        ->and($movement->quantity_after)->toBe(12)
        ->and($inventory->fresh()->quantity)->toBeGreaterThanOrEqual(0);
});

test('3. Stock Out greater than current stock is rejected and does not alter inventory', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10);

    expect(fn () => $inventory->adjustOut(15))
        ->toThrow(InventoryException::class, 'Insufficient stock');

    expect($inventory->fresh()->quantity)->toBe(10)
        ->and($inventory->fresh()->quantity)->toBeGreaterThanOrEqual(0);
});

test('4. Invalid zero and negative adjustment quantities are rejected by domain', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10);

    // Zero adjustIn
    expect(fn () => $inventory->adjustIn(0))
        ->toThrow(InventoryException::class);

    // Negative adjustIn
    expect(fn () => $inventory->adjustIn(-5))
        ->toThrow(InventoryException::class);

    // Zero adjustOut
    expect(fn () => $inventory->adjustOut(0))
        ->toThrow(InventoryException::class);

    // Negative adjustOut
    expect(fn () => $inventory->adjustOut(-5))
        ->toThrow(InventoryException::class);

    // Stock untouched
    expect($inventory->fresh()->quantity)->toBe(10);
});

/*
|--------------------------------------------------------------------------
| 2. MOVEMENT INTEGRITY & CONTINUITY TESTS
|--------------------------------------------------------------------------
*/

test('5. Movement ledger maintains unbroken before/after continuity across sequential mutations', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant);

    // Opening: 0 -> 50
    $m1 = $inventory->openStock(50, 'Opening initial stock');
    expect($m1->quantity_before)->toBe(0)
        ->and($m1->quantity)->toBe(50)
        ->and($m1->quantity_after)->toBe(50)
        ->and($m1->type)->toBe(InventoryMovementType::Opening);

    // Stock In: 50 -> 75
    $m2 = $inventory->adjustIn(25, 'Supplier delivery');
    expect($m2->quantity_before)->toBe(50)
        ->and($m2->quantity)->toBe(25)
        ->and($m2->quantity_after)->toBe(75)
        ->and($m2->type)->toBe(InventoryMovementType::AdjustmentIn);

    // Stock Out: 75 -> 60
    $m3 = $inventory->adjustOut(15, 'Quality inspection damage');
    expect($m3->quantity_before)->toBe(75)
        ->and($m3->quantity)->toBe(15)
        ->and($m3->quantity_after)->toBe(60)
        ->and($m3->type)->toBe(InventoryMovementType::AdjustmentOut);

    // Stock In: 60 -> 70
    $m4 = $inventory->adjustIn(10, 'Return from QA');
    expect($m4->quantity_before)->toBe(60)
        ->and($m4->quantity)->toBe(10)
        ->and($m4->quantity_after)->toBe(70)
        ->and($m4->type)->toBe(InventoryMovementType::AdjustmentIn);

    // Stock Out: 70 -> 40
    $m5 = $inventory->adjustOut(30, 'Bulk sample shipment');
    expect($m5->quantity_before)->toBe(70)
        ->and($m5->quantity)->toBe(30)
        ->and($m5->quantity_after)->toBe(40)
        ->and($m5->type)->toBe(InventoryMovementType::AdjustmentOut);

    // Final inventory strictly equals the last movement's quantity_after
    expect($inventory->fresh()->quantity)->toBe(40)
        ->and($inventory->fresh()->quantity)->toBe($m5->quantity_after);

    // Exactly 5 movements exist in sequence
    expect($inventory->movements()->count())->toBe(5);
});

/*
|--------------------------------------------------------------------------
| 3. IMMUTABLE LEDGER TESTS
|--------------------------------------------------------------------------
*/

test('6. InventoryMovement cannot be updated or deleted through the model architecture', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20);

    $movement = $inventory->movements()->first();
    expect($movement)->not->toBeNull();

    // Attempting update must throw DomainException
    expect(fn () => $movement->update(['reason' => 'Tampered reason', 'quantity' => 999]))
        ->toThrow(DomainException::class, 'Inventory movements are immutable ledger records and cannot be updated.');

    // Attempting delete must throw DomainException
    expect(fn () => $movement->delete())
        ->toThrow(DomainException::class, 'Inventory movements are immutable ledger records and cannot be deleted.');

    // Verify record remains completely intact in database
    $freshMovement = $movement->fresh();
    expect($freshMovement->quantity)->toBe(20)
        ->and($freshMovement->quantity_before)->toBe(0)
        ->and($freshMovement->quantity_after)->toBe(20)
        ->and($freshMovement->reason)->toBe('Initial opening stock');
});

/*
|--------------------------------------------------------------------------
| 4. TRANSACTION ATOMICITY & ROLLBACK TESTS
|--------------------------------------------------------------------------
*/

test('7. Database transaction rolls back inventory quantity if movement creation fails', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10);
    $initialMovementsCount = InventoryMovement::count();

    // Simulate failure during movement creation inside mutateStock transaction
    InventoryMovement::creating(function () {
        throw new RuntimeException('Simulated database write failure during movement ledgering');
    });

    try {
        $inventory->adjustIn(20, 'Failed batch');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Simulated database write failure during movement ledgering');
    }

    // Reset event dispatcher so subsequent tests are unaffected
    InventoryMovement::flushEventListeners();
    // Re-register the booted immutability hooks
    InventoryMovement::boot();

    // Inventory quantity in database must remain at 10 (rolled back)
    expect($inventory->fresh()->quantity)->toBe(10);

    // No partial or orphaned movement created
    expect(InventoryMovement::count())->toBe($initialMovementsCount);
});

/*
|--------------------------------------------------------------------------
| 5. REAL CONCURRENCY SAFETY TEST
|--------------------------------------------------------------------------
*/

test('8. Real concurrency test: lockForUpdate prevents overselling under simultaneous requests', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10);
    $inventoryId = $inventory->id;

    // Initial stock = 10
    // Request A attempts adjustOut(7)
    // Request B attempts adjustOut(5)
    // Total = 12 > 10
    // Only one can succeed; the other must receive Insufficient Stock

    $pidA = pcntl_fork();
    if ($pidA === 0) {
        DB::purge('mysql');
        try {
            $invA = Inventory::find($inventoryId);
            $invA->adjustOut(7, 'Process A');
            exit(10); // Success
        } catch (InventoryException) {
            exit(20); // Insufficient stock
        } catch (Throwable) {
            exit(30);
        }
    }

    $pidB = pcntl_fork();
    if ($pidB === 0) {
        DB::purge('mysql');
        try {
            $invB = Inventory::find($inventoryId);
            $invB->adjustOut(5, 'Process B');
            exit(10); // Success
        } catch (InventoryException) {
            exit(20); // Insufficient stock
        } catch (Throwable) {
            exit(30);
        }
    }

    pcntl_waitpid($pidA, $statusA);
    pcntl_waitpid($pidB, $statusB);

    $codeA = pcntl_wexitstatus($statusA);
    $codeB = pcntl_wexitstatus($statusB);

    // Exactly one process succeeded (10) and the other failed (20)
    $oneSuccess = ($codeA === 10 && $codeB === 20) || ($codeA === 20 && $codeB === 10);
    expect($oneSuccess)->toBeTrue();

    DB::purge('mysql');
    $finalQuantity = (int) DB::table('inventories')->where('id', $inventoryId)->value('quantity');

    if ($codeA === 10) {
        expect($finalQuantity)->toBe(3); // 10 - 7 = 3
    } else {
        expect($finalQuantity)->toBe(5); // 10 - 5 = 5
    }

    expect($finalQuantity)->toBeGreaterThanOrEqual(0);

    // Movements count = 1 opening + 1 successful adjustment out
    expect(InventoryMovement::where('inventory_id', $inventoryId)->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| 6. OPENING STOCK HARDENING TESTS
|--------------------------------------------------------------------------
*/

test('9. Opening stock cannot be initialized twice on the same inventory', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 20);

    expect(fn () => $inventory->openStock(30))
        ->toThrow(InventoryException::class, 'Opening stock has already been initialized');

    expect($inventory->fresh()->quantity)->toBe(20);
});

test('10. Opening stock cannot be initialized after adjustment history exists', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 0,
        'low_stock_threshold' => 0,
    ]);

    // Perform an adjustment in and out so quantity returns to 0
    $inventory->adjustIn(10, 'Temporary restock');
    $inventory->adjustOut(10, 'Sold all');

    expect($inventory->fresh()->quantity)->toBe(0);

    // Attempting opening stock when movements exist must fail
    expect(fn () => $inventory->openStock(50))
        ->toThrow(InventoryException::class, 'Opening stock cannot be initialized after previous adjustment history.');

    expect($inventory->fresh()->quantity)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| 7. SOFT DELETE & RESTORE HARDENING TESTS
|--------------------------------------------------------------------------
*/

test('11. ProductVariant soft deletion preserves Inventory and InventoryMovements', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    // Create 2 active variants so business rules permit deleting one
    $var1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
        'is_default' => true,
    ]);

    $var2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'cost_price' => 50,
        'selling_price' => 100,
        'compare_at_price' => 150,
        'is_active' => true,
        'is_default' => false,
    ]);

    $inventory2 = Inventory::createForVariant($var2, 35, 5);
    $inventory2->adjustIn(10, 'Pre-deletion restock');

    expect($var2->inventory->quantity)->toBe(45)
        ->and($var2->inventoryMovements()->count())->toBe(2);

    // Soft delete variant 2
    $var2->delete();

    expect($var2->trashed())->toBeTrue();

    // Inventory row in database is preserved and NOT zeroed
    $dbInventory = DB::table('inventories')->where('product_variant_id', $var2->id)->first();
    expect($dbInventory)->not->toBeNull()
        ->and($dbInventory->quantity)->toBe(45)
        ->and($dbInventory->low_stock_threshold)->toBe(5);

    // All movements in database are preserved
    $dbMovementsCount = DB::table('inventory_movements')->where('product_variant_id', $var2->id)->count();
    expect($dbMovementsCount)->toBe(2);

    // Restore variant 2
    $var2->restore();
    expect($var2->trashed())->toBeFalse()
        ->and($var2->fresh()->inventory->quantity)->toBe(45)
        ->and($var2->fresh()->inventoryMovements()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| 8. AUTHORIZATION & POLICY HARDENING TESTS
|--------------------------------------------------------------------------
*/

test('12. Authorization matrix strictly enforces role permissions across all operations', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10, 2);

    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $unauthorized = User::factory()->create(['can_access_admin_panel' => true]);

    // Admin passes all checks via Gate::before
    expect(Gate::forUser($admin)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('adjust', $inventory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewHistory', $inventory))->toBeTrue();

    // Manager passes all checks
    expect(Gate::forUser($manager)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('adjust', $inventory))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('viewHistory', $inventory))->toBeTrue();

    // Staff passes view, denied adjust & viewHistory
    expect(Gate::forUser($staff)->allows('view', $inventory))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('viewHistory', $inventory))->toBeFalse();

    // Unauthorized user denied all
    expect(Gate::forUser($unauthorized)->allows('view', $inventory))->toBeFalse()
        ->and(Gate::forUser($unauthorized)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($unauthorized)->allows('viewHistory', $inventory))->toBeFalse();
});

test('13. Product update permission does NOT grant inventory adjustment permission', function () {
    [$product, $variant] = createHardeningTestProductWithVariant();
    $inventory = Inventory::createForVariant($variant, 10, 2);

    $productEditor = User::factory()->create(['can_access_admin_panel' => true]);
    $productEditor->givePermissionTo('products.update');

    $this->actingAs($productEditor);

    expect(Gate::forUser($productEditor)->allows('update', $variant))->toBeTrue()
        ->and(Gate::forUser($productEditor)->allows('adjust', $inventory))->toBeFalse()
        ->and(Gate::forUser($productEditor)->allows('viewHistory', $inventory))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| 9. DATA ISOLATION HARDENING TESTS
|--------------------------------------------------------------------------
*/

test('14. Cross-variant movement data isolation is strictly maintained in queries and UI', function () {
    [$productA, $variantA] = createHardeningTestProductWithVariant();
    [$productB, $variantB] = createHardeningTestProductWithVariant();

    $invA = Inventory::createForVariant($variantA, 100, reason: 'Variant A batch');
    $invA->adjustIn(20, 'Variant A restock');

    $invB = Inventory::createForVariant($variantB, 200, reason: 'Variant B batch');
    $invB->adjustIn(30, 'Variant B restock');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    // Component for Variant A contains only Variant A records
    $compA = Livewire::test(VariantStockHistory::class, ['variant' => $variantA])
        ->assertSuccessful()
        ->assertSee('Variant A batch')
        ->assertSee('Variant A restock')
        ->assertDontSee('Variant B batch')
        ->assertDontSee('Variant B restock');

    $movementsA = $compA->viewData('movements');
    expect($movementsA)->toHaveCount(2);
    foreach ($movementsA as $m) {
        expect($m->product_variant_id)->toBe($variantA->id);
    }

    // Component for Variant B contains only Variant B records
    $compB = Livewire::test(VariantStockHistory::class, ['variant' => $variantB])
        ->assertSuccessful()
        ->assertSee('Variant B batch')
        ->assertSee('Variant B restock')
        ->assertDontSee('Variant A batch')
        ->assertDontSee('Variant A restock');

    $movementsB = $compB->viewData('movements');
    expect($movementsB)->toHaveCount(2);
    foreach ($movementsB as $m) {
        expect($m->product_variant_id)->toBe($variantB->id);
    }
});

/*
|--------------------------------------------------------------------------
| 10. FILAMENT UI & N+1 REGRESSION TESTS
|--------------------------------------------------------------------------
*/

test('15. VariantsRelationManager and VariantStockHistory prevent N+1 queries', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category->id);

    $users = User::factory()->count(3)->create();

    // Create 4 variants with inventories
    for ($i = 0; $i < 4; $i++) {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'cost_price' => 50,
            'selling_price' => 100,
            'compare_at_price' => 150,
            'sku' => "NPLUS-{$i}",
        ]);
        $inv = Inventory::createForVariant($variant, 10 + $i, 2);
        $inv->adjustIn(5, "Adjustment {$i}", createdBy: $users[$i % 3]);
    }

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);

    // 1. Table listing eager-loads inventories in a single batch query
    DB::enableQueryLog();

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ]);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $invQueries = array_filter($queries, fn ($q) => str_contains($q['query'], 'inventories'));
    expect(count($invQueries))->toBeLessThanOrEqual(2);

    // 2. History modal query eager-loads creator in a single batch query
    $testVariant = $product->variants()->first();

    DB::enableQueryLog();

    Livewire::test(VariantStockHistory::class, ['variant' => $testVariant]);

    $historyQueries = DB::getQueryLog();
    DB::disableQueryLog();

    $userQueries = array_filter($historyQueries, fn ($q) => str_contains($q['query'], 'users') && ! str_contains($q['query'], 'permissions'));
    expect(count($userQueries))->toBeLessThanOrEqual(2);
});
