<?php

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryException;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

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
    Schema::enableForeignKeyConstraints();
});

/*
|--------------------------------------------------------------------------
| OPENING STOCK TESTS
|--------------------------------------------------------------------------
*/

test('1. Opening stock initializes zero inventory', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 0,
        'low_stock_threshold' => 5,
    ]);

    $movement = $inventory->openStock(
        quantity: 50,
        reason: 'Initial warehouse intake',
        note: 'Counted and verified'
    );

    expect($inventory->quantity)->toBe(50)
        ->and($movement)->toBeInstanceOf(InventoryMovement::class)
        ->and($movement->inventory_id)->toBe($inventory->id);

    // Verify raw database row is updated
    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(50);
});

test('2. Opening movement is created', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $inventory->openStock(35);

    expect($inventory->movements()->count())->toBe(1);
});

test('3. Opening movement has correct snapshots and attributes', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $movement = $inventory->openStock(
        quantity: 80,
        reason: 'Opening balance',
        note: 'Checked by warehouse manager',
        createdBy: $user
    );

    expect($movement->type)->toBe(InventoryMovementType::Opening)
        ->and($movement->quantity)->toBe(80)
        ->and($movement->quantity_before)->toBe(0)
        ->and($movement->quantity_after)->toBe(80)
        ->and($movement->reason)->toBe('Opening balance')
        ->and($movement->note)->toBe('Checked by warehouse manager')
        ->and($movement->created_by)->toBe($user->id);
});

test('4. Opening stock cannot be initialized twice', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $inventory->openStock(50);

    expect(function () use ($inventory) {
        $inventory->openStock(30);
    })->toThrow(InventoryException::class, 'Opening stock has already been initialized');
});

test('5. Opening stock cannot be initialized after previous adjustment history', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $inventory->openStock(50);
    $inventory->adjustOut(50);

    // Current quantity is 0, but movements exist
    expect($inventory->quantity)->toBe(0)
        ->and($inventory->movements()->count())->toBe(2);

    expect(function () use ($inventory) {
        $inventory->openStock(25);
    })->toThrow(InventoryException::class, 'Opening stock cannot be initialized after previous adjustment history');
});

test('6. Opening stock can be zero only if the chosen opening rule explicitly allows it', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $movement = $inventory->openStock(0);

    expect($inventory->quantity)->toBe(0)
        ->and($movement->quantity)->toBe(0)
        ->and($movement->quantity_before)->toBe(0)
        ->and($movement->quantity_after)->toBe(0);

    // Second opening call must fail even though quantity is 0 because movement now exists
    expect(function () use ($inventory) {
        $inventory->openStock(10);
    })->toThrow(InventoryException::class);
});

test('7. Opening stock rejects negative quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    expect(function () use ($inventory) {
        $inventory->openStock(-10);
    })->toThrow(InventoryException::class, 'Opening stock quantity must be greater than or equal to zero');
});

/*
|--------------------------------------------------------------------------
| ADJUSTMENT IN TESTS
|--------------------------------------------------------------------------
*/

test('8. Adjustment in increases inventory', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    $inventory->adjustIn(20);

    expect($inventory->quantity)->toBe(70);

    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(70);
});

test('9. Adjustment in creates movement', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    $inventory->adjustIn(15);

    expect($inventory->movements()->count())->toBe(2)
        ->and($inventory->movements()->latest('id')->first()->type)->toBe(InventoryMovementType::AdjustmentIn);
});

test('10. Adjustment in has correct before and after snapshots', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(40);

    $movement = $inventory->adjustIn(25, reason: 'Restock batch #1');

    expect($movement->type)->toBe(InventoryMovementType::AdjustmentIn)
        ->and($movement->quantity)->toBe(25)
        ->and($movement->quantity_before)->toBe(40)
        ->and($movement->quantity_after)->toBe(65);
});

test('11. Adjustment in stores positive movement quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    $movement = $inventory->adjustIn(15);

    expect($movement->quantity)->toBe(15)
        ->and($movement->quantity)->toBeGreaterThan(0);
});

test('12. Adjustment in rejects zero', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    expect(function () use ($inventory) {
        $inventory->adjustIn(0);
    })->toThrow(InventoryException::class, 'Adjustment in quantity must be greater than zero');
});

test('13. Adjustment in rejects negative quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    expect(function () use ($inventory) {
        $inventory->adjustIn(-5);
    })->toThrow(InventoryException::class, 'Adjustment in quantity must be greater than zero');
});

/*
|--------------------------------------------------------------------------
| ADJUSTMENT OUT TESTS
|--------------------------------------------------------------------------
*/

test('14. Adjustment out decreases inventory', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    $inventory->adjustOut(20);

    expect($inventory->quantity)->toBe(30);

    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(30);
});

test('15. Adjustment out creates movement', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    $inventory->adjustOut(10);

    expect($inventory->movements()->count())->toBe(2)
        ->and($inventory->movements()->latest('id')->first()->type)->toBe(InventoryMovementType::AdjustmentOut);
});

test('16. Adjustment out has correct before and after snapshots', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(60);

    $movement = $inventory->adjustOut(15, reason: 'Damaged packaging');

    expect($movement->type)->toBe(InventoryMovementType::AdjustmentOut)
        ->and($movement->quantity)->toBe(15)
        ->and($movement->quantity_before)->toBe(60)
        ->and($movement->quantity_after)->toBe(45);
});

test('17. Adjustment out stores positive movement quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(30);

    $movement = $inventory->adjustOut(12);

    expect($movement->quantity)->toBe(12)
        ->and($movement->quantity)->toBeGreaterThan(0);
});

test('18. Adjustment out rejects zero', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(20);

    expect(function () use ($inventory) {
        $inventory->adjustOut(0);
    })->toThrow(InventoryException::class, 'Adjustment out quantity must be greater than zero');
});

test('19. Adjustment out rejects negative quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(20);

    expect(function () use ($inventory) {
        $inventory->adjustOut(-8);
    })->toThrow(InventoryException::class, 'Adjustment out quantity must be greater than zero');
});

test('20. Adjustment out cannot make stock negative (insufficient stock)', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    expect(function () use ($inventory) {
        $inventory->adjustOut(15);
    })->toThrow(InventoryException::class, 'Insufficient stock');
});

test('21. Failed adjustment out creates NO movement', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    $initialMovementCount = $inventory->movements()->count();

    try {
        $inventory->adjustOut(25);
    } catch (InventoryException) {
        // Expected failure
    }

    expect($inventory->movements()->count())->toBe($initialMovementCount);
});

test('22. Failed adjustment out does NOT change inventory', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);

    try {
        $inventory->adjustOut(25);
    } catch (InventoryException) {
        // Expected failure
    }

    expect($inventory->quantity)->toBe(10);

    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(10);
});

/*
|--------------------------------------------------------------------------
| CREATOR TESTS
|--------------------------------------------------------------------------
*/

test('23. created_by is stored correctly', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $movement = $inventory->openStock(20, createdBy: $user);

    expect($movement->created_by)->toBe($user->id)
        ->and($movement->creator->id)->toBe($user->id);
});

test('24. Nullable creator remains supported if schema allows it', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $movement = $inventory->openStock(20, createdBy: null);

    expect($movement->created_by)->toBeNull()
        ->and($movement->creator)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| VARIANT CONSISTENCY TESTS
|--------------------------------------------------------------------------
*/

test('25. Movement product_variant_id matches inventory product_variant_id', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $movement = $inventory->openStock(30);

    expect($movement->product_variant_id)->toBe($variant->id)
        ->and($movement->product_variant_id)->toBe($inventory->product_variant_id);
});

test('26. Caller cannot inject a mismatched product_variant_id', function () {
    $variant1 = ProductVariant::factory()->create();
    $variant2 = ProductVariant::factory()->create();

    $inventory = Inventory::create(['product_variant_id' => $variant1->id]);
    $movement = $inventory->openStock(30);

    // The domain API does not accept product_variant_id from caller; it always binds to inventory's variant
    expect($movement->product_variant_id)->toBe($variant1->id)
        ->and($movement->product_variant_id)->not->toBe($variant2->id);
});

/*
|--------------------------------------------------------------------------
| LEDGER TESTS
|--------------------------------------------------------------------------
*/

test('27. Successful mutations append movements in chronological order', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $m1 = $inventory->openStock(100);
    $m2 = $inventory->adjustIn(20);
    $m3 = $inventory->adjustOut(30);
    $m4 = $inventory->adjustOut(10);

    expect($inventory->movements()->count())->toBe(4);

    $types = $inventory->movements()->orderBy('id')->pluck('type')->all();
    expect($types)->toBe([
        InventoryMovementType::Opening,
        InventoryMovementType::AdjustmentIn,
        InventoryMovementType::AdjustmentOut,
        InventoryMovementType::AdjustmentOut,
    ]);
});

test('28. Movement history is not updated or deleted by mutation operations', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $openingMovement = $inventory->openStock(50);
    $originalOpeningQuantity = $openingMovement->quantity;
    $originalOpeningAfter = $openingMovement->quantity_after;

    $inventory->adjustIn(20);
    $inventory->adjustOut(10);

    $freshOpening = InventoryMovement::find($openingMovement->id);
    expect($freshOpening->quantity)->toBe($originalOpeningQuantity)
        ->and($freshOpening->quantity_after)->toBe($originalOpeningAfter);
});

test('29. Snapshots are mathematically consistent across ledger entries', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $m1 = $inventory->openStock(100); // 0 -> 100
    $m2 = $inventory->adjustIn(25);   // 100 -> 125
    $m3 = $inventory->adjustOut(40);  // 125 -> 85
    $m4 = $inventory->adjustOut(15);  // 85 -> 70

    expect($m1->quantity_before)->toBe(0)
        ->and($m1->quantity_after)->toBe(100)
        ->and($m2->quantity_before)->toBe(100)
        ->and($m2->quantity_after)->toBe(125)
        ->and($m3->quantity_before)->toBe(125)
        ->and($m3->quantity_after)->toBe(85)
        ->and($m4->quantity_before)->toBe(85)
        ->and($m4->quantity_after)->toBe(70);
});

test('30. Final movement quantity_after equals final inventory quantity', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);

    $inventory->openStock(50);
    $inventory->adjustIn(30);
    $lastMovement = $inventory->adjustOut(15);

    expect($lastMovement->quantity_after)->toBe($inventory->quantity)
        ->and($inventory->quantity)->toBe(65);
});

/*
|--------------------------------------------------------------------------
| TRANSACTION AND ROLLBACK TESTS
|--------------------------------------------------------------------------
*/

test('31. If movement creation fails, inventory update rolls back atomically', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    expect($inventory->quantity)->toBe(50);

    // Register a saving hook on InventoryMovement to simulate an unexpected database failure
    InventoryMovement::saving(function () {
        throw new RuntimeException('Simulated database ledger write failure');
    });

    try {
        expect(function () use ($inventory) {
            $inventory->adjustOut(10);
        })->toThrow(RuntimeException::class, 'Simulated database ledger write failure');
    } finally {
        // Clear model events to avoid polluting other tests
        InventoryMovement::flushEventListeners();
    }

    // Verify inventory quantity was NOT modified in the database
    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(50)
        ->and($inventory->movements()->count())->toBe(1); // Only the opening movement remains
});

test('32. If inventory update fails, movement is not created', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(50);

    // Register an inventory updating hook to simulate an update failure
    Inventory::updating(function () {
        throw new RuntimeException('Simulated inventory update failure');
    });

    try {
        expect(function () use ($inventory) {
            $inventory->adjustIn(20);
        })->toThrow(RuntimeException::class, 'Simulated inventory update failure');
    } finally {
        Inventory::flushEventListeners();
    }

    $dbQty = DB::table('inventories')->where('id', $inventory->id)->value('quantity');
    expect((int) $dbQty)->toBe(50)
        ->and($inventory->movements()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| REAL CONCURRENCY TEST (SECTION 28)
|--------------------------------------------------------------------------
*/

test('33. Concurrency test: simultaneous adjustment out on same inventory respects row lock', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create(['product_variant_id' => $variant->id]);
    $inventory->openStock(10);
    $inventoryId = $inventory->id;

    // Initial stock is exactly 10.
    // Process A attempts adjustOut(7).
    // Process B attempts adjustOut(5).
    // Total requested = 12, which exceeds 10.
    // With row-level locking (SELECT ... FOR UPDATE inside transaction),
    // only ONE transaction can acquire the lock first and succeed.
    // The other must wait for the lock, observe the updated stock (3 or 5), and fail due to insufficient stock.

    // Fork child A
    $pidA = pcntl_fork();
    if ($pidA === 0) {
        DB::purge('mysql');
        try {
            $invA = Inventory::find($inventoryId);
            $invA->adjustOut(7, 'Process A');
            exit(10); // Code 10 = success
        } catch (InventoryException) {
            exit(20); // Code 20 = insufficient stock
        } catch (Throwable) {
            exit(30);
        }
    }

    // Fork child B
    $pidB = pcntl_fork();
    if ($pidB === 0) {
        DB::purge('mysql');
        try {
            $invB = Inventory::find($inventoryId);
            $invB->adjustOut(5, 'Process B');
            exit(10); // Code 10 = success
        } catch (InventoryException) {
            exit(20); // Code 20 = insufficient stock
        } catch (Throwable) {
            exit(30);
        }
    }

    // Parent process waits for both child processes to complete
    pcntl_waitpid($pidA, $statusA);
    pcntl_waitpid($pidB, $statusB);

    $codeA = pcntl_wexitstatus($statusA);
    $codeB = pcntl_wexitstatus($statusB);

    // Exactly ONE child must exit with code 10 (success), and the other with code 20 (insufficient stock)
    $oneSuccess = ($codeA === 10 && $codeB === 20) || ($codeA === 20 && $codeB === 10);
    expect($oneSuccess)->toBeTrue();

    DB::purge('mysql');
    $finalQuantity = (int) DB::table('inventories')->where('id', $inventoryId)->value('quantity');

    if ($codeA === 10) {
        expect($finalQuantity)->toBe(3); // 10 - 7 = 3
    } else {
        expect($finalQuantity)->toBe(5); // 10 - 5 = 5
    }

    // Verify exactly 2 movements exist: 1 opening + 1 successful adjustment out
    $movementCount = DB::table('inventory_movements')->where('inventory_id', $inventoryId)->count();
    expect($movementCount)->toBe(2);
});

test('34. createForVariant initializes inventory and optional opening stock', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::createForVariant(
        variant: $variant,
        openingQuantity: 40,
        lowStockThreshold: 10,
        reason: 'Initial setup'
    );

    expect($inventory)->toBeInstanceOf(Inventory::class)
        ->and($inventory->product_variant_id)->toBe($variant->id)
        ->and($inventory->quantity)->toBe(40)
        ->and($inventory->low_stock_threshold)->toBe(10)
        ->and($inventory->movements()->count())->toBe(1);
});
