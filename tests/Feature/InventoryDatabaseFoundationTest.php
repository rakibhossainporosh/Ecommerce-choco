<?php

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

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

test('1. Inventory belongs to ProductVariant', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 100,
        'low_stock_threshold' => 10,
    ]);

    expect($inventory->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($inventory->productVariant->id)->toBe($variant->id);
});

test('2. ProductVariant has one Inventory', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 45,
        'low_stock_threshold' => 5,
    ]);

    expect($variant->inventory)->toBeInstanceOf(Inventory::class)
        ->and($variant->inventory->id)->toBe($inventory->id)
        ->and($variant->inventory->quantity)->toBe(45);
});

test('3. ProductVariant inventory uniqueness is enforced', function () {
    $variant = ProductVariant::factory()->create();

    Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
        'low_stock_threshold' => 5,
    ]);

    // Attempting to create a duplicate inventory for the same variant must fail
    expect(function () use ($variant) {
        Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => 20,
            'low_stock_threshold' => 5,
        ]);
    })->toThrow(QueryException::class);
});

test('4. Default inventory quantity is 0', function () {
    $variant = ProductVariant::factory()->create();

    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
    ]);

    expect($inventory->quantity)->toBe(0);

    // Verify raw database value
    $raw = DB::table('inventories')->where('id', $inventory->id)->first();
    expect((int) $raw->quantity)->toBe(0);
});

test('5. Default low_stock_threshold is 0', function () {
    $variant = ProductVariant::factory()->create();

    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
    ]);

    expect($inventory->low_stock_threshold)->toBe(0);

    $raw = DB::table('inventories')->where('id', $inventory->id)->first();
    expect((int) $raw->low_stock_threshold)->toBe(0);
});

test('6. Quantity cannot be negative', function () {
    $variant = ProductVariant::factory()->create();

    expect(function () use ($variant) {
        DB::table('inventories')->insert([
            'product_variant_id' => $variant->id,
            'quantity' => -5,
            'low_stock_threshold' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('7. InventoryMovement belongs to Inventory', function () {
    $inventory = Inventory::factory()->create(['quantity' => 50]);
    $movement = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $inventory->product_variant_id,
        'type' => InventoryMovementType::Opening,
        'quantity' => 50,
        'quantity_before' => 0,
        'quantity_after' => 50,
    ]);

    expect($movement->inventory)->toBeInstanceOf(Inventory::class)
        ->and($movement->inventory->id)->toBe($inventory->id);
});

test('8. InventoryMovement belongs to ProductVariant', function () {
    $inventory = Inventory::factory()->create(['quantity' => 30]);
    $movement = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $inventory->product_variant_id,
        'type' => InventoryMovementType::Opening,
        'quantity' => 30,
        'quantity_before' => 0,
        'quantity_after' => 30,
    ]);

    expect($movement->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($movement->productVariant->id)->toBe($inventory->product_variant_id)
        ->and($inventory->productVariant->inventoryMovements)->toHaveCount(1)
        ->and($inventory->productVariant->inventoryMovements->first()->id)->toBe($movement->id);
});

test('9. InventoryMovement belongs to creator User and survives user deletion with nullOnDelete', function () {
    $user = User::factory()->create();
    $inventory = Inventory::factory()->create();

    $movement = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $inventory->product_variant_id,
        'type' => InventoryMovementType::Opening,
        'quantity' => 20,
        'quantity_before' => 0,
        'quantity_after' => 20,
        'created_by' => $user->id,
    ]);

    expect($movement->creator)->toBeInstanceOf(User::class)
        ->and($movement->creator->id)->toBe($user->id);

    // Hard delete user to verify nullOnDelete preserves audit ledger entry
    $user->delete();

    $movement->refresh();
    expect($movement->created_by)->toBeNull()
        ->and($movement->creator)->toBeNull();
});

test('10. InventoryMovement supports nullable polymorphic reference fields', function () {
    $inventory = Inventory::factory()->create();

    // Test with null reference
    $movementWithoutRef = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $inventory->product_variant_id,
        'type' => InventoryMovementType::Opening,
        'quantity' => 15,
        'quantity_before' => 0,
        'quantity_after' => 15,
        'reference_type' => null,
        'reference_id' => null,
    ]);

    expect($movementWithoutRef->reference_type)->toBeNull()
        ->and($movementWithoutRef->reference_id)->toBeNull()
        ->and($movementWithoutRef->reference)->toBeNull();

    // Test with morph reference pointing to an existing model (e.g. User or Product)
    $dummyTarget = User::factory()->create();
    $movementWithRef = InventoryMovement::create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $inventory->product_variant_id,
        'type' => InventoryMovementType::AdjustmentIn,
        'quantity' => 5,
        'quantity_before' => 15,
        'quantity_after' => 20,
        'reference_type' => User::class,
        'reference_id' => $dummyTarget->id,
    ]);

    expect($movementWithRef->reference_type)->toBe(User::class)
        ->and($movementWithRef->reference_id)->toBe($dummyTarget->id)
        ->and($movementWithRef->reference)->toBeInstanceOf(User::class)
        ->and($movementWithRef->reference->id)->toBe($dummyTarget->id);
});

test('11. Movement types are restricted to opening, adjustment_in, adjustment_out, sale', function () {
    $expectedCases = [
        'opening',
        'adjustment_in',
        'adjustment_out',
        'sale',
    ];

    expect(InventoryMovementType::values())->toBe($expectedCases)
        ->and(count(InventoryMovementType::cases()))->toBe(4);

    // Invalid enum value assignment throws ValueError
    expect(function () {
        $inventory = Inventory::factory()->create();
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'type' => 'invalid_movement_type',
            'quantity' => 10,
            'quantity_before' => 20,
            'quantity_after' => 10,
        ]);
    })->toThrow(ValueError::class);
});

test('12. Movement quantity is non-negative at database level', function () {
    $inventory = Inventory::factory()->create();

    expect(function () use ($inventory) {
        DB::table('inventory_movements')->insert([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'type' => 'adjustment_out',
            'quantity' => -10,
            'quantity_before' => 20,
            'quantity_after' => 10,
            'created_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('13. quantity_before is non-negative at database level', function () {
    $inventory = Inventory::factory()->create();

    expect(function () use ($inventory) {
        DB::table('inventory_movements')->insert([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'type' => 'adjustment_in',
            'quantity' => 10,
            'quantity_before' => -5,
            'quantity_after' => 5,
            'created_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('14. quantity_after is non-negative at database level', function () {
    $inventory = Inventory::factory()->create();

    expect(function () use ($inventory) {
        DB::table('inventory_movements')->insert([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'type' => 'adjustment_out',
            'quantity' => 10,
            'quantity_before' => 5,
            'quantity_after' => -5,
            'created_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('15. InventoryMovement has no updated_at column and model declares UPDATED_AT null', function () {
    expect(Schema::hasColumn('inventory_movements', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('inventory_movements', 'created_at'))->toBeTrue()
        ->and(InventoryMovement::UPDATED_AT)->toBeNull();
});

test('16. Required indexes and unique constraints exist', function () {
    // inventories table indexes
    $inventoryIndexes = collect(Schema::getIndexes('inventories'));
    $hasUniqueVariantId = $inventoryIndexes->contains(function ($index) {
        return $index['unique'] && in_array('product_variant_id', $index['columns']);
    });
    expect($hasUniqueVariantId)->toBeTrue();

    // inventory_movements table indexes
    $movementIndexes = collect(Schema::getIndexes('inventory_movements'));

    $hasInventoryIdIndex = $movementIndexes->contains(function ($index) {
        return in_array('inventory_id', $index['columns']);
    });

    $hasVariantIdIndex = $movementIndexes->contains(function ($index) {
        return in_array('product_variant_id', $index['columns']);
    });

    $hasCreatedAtIndex = $movementIndexes->contains(function ($index) {
        return in_array('created_at', $index['columns']);
    });

    $hasReferenceCompositeIndex = $movementIndexes->contains(function ($index) {
        return $index['columns'] === ['reference_type', 'reference_id'];
    });

    expect($hasInventoryIdIndex)->toBeTrue()
        ->and($hasVariantIdIndex)->toBeTrue()
        ->and($hasCreatedAtIndex)->toBeTrue()
        ->and($hasReferenceCompositeIndex)->toBeTrue();
});

test('17. Factory creates valid Inventory records and state methods work', function () {
    // Default factory
    $defaultInventory = Inventory::factory()->create();
    expect($defaultInventory)->toBeInstanceOf(Inventory::class)
        ->and($defaultInventory->quantity)->toBe(0)
        ->and($defaultInventory->isOutOfStock())->toBeTrue()
        ->and($defaultInventory->isInStock())->toBeFalse();

    // inStock state
    $inStock = Inventory::factory()->inStock(100, 15)->create();
    expect($inStock->quantity)->toBe(100)
        ->and($inStock->low_stock_threshold)->toBe(15)
        ->and($inStock->isInStock())->toBeTrue()
        ->and($inStock->isOutOfStock())->toBeFalse()
        ->and($inStock->isLowStock())->toBeFalse();

    // lowStock state
    $lowStock = Inventory::factory()->lowStock(10, 4)->create();
    expect($lowStock->quantity)->toBe(4)
        ->and($lowStock->low_stock_threshold)->toBe(10)
        ->and($lowStock->isLowStock())->toBeTrue()
        ->and($lowStock->isInStock())->toBeTrue()
        ->and($lowStock->isOutOfStock())->toBeFalse();

    // outOfStock state
    $outOfStock = Inventory::factory()->outOfStock(5)->create();
    expect($outOfStock->quantity)->toBe(0)
        ->and($outOfStock->isOutOfStock())->toBeTrue()
        ->and($outOfStock->isInStock())->toBeFalse();
});

test('18. Factory creates valid Movement records with logically consistent snapshots', function () {
    // Default opening factory
    $openingMovement = InventoryMovement::factory()->create();
    expect($openingMovement->type)->toBe(InventoryMovementType::Opening)
        ->and($openingMovement->quantity_before)->toBe(0)
        ->and($openingMovement->quantity_after)->toBe($openingMovement->quantity);

    // Explicit opening state
    $opening = InventoryMovement::factory()->opening(75)->create();
    expect($opening->type)->toBe(InventoryMovementType::Opening)
        ->and($opening->quantity)->toBe(75)
        ->and($opening->quantity_before)->toBe(0)
        ->and($opening->quantity_after)->toBe(75);

    // Adjustment in state
    $adjustmentIn = InventoryMovement::factory()->adjustmentIn(20, 50)->create();
    expect($adjustmentIn->type)->toBe(InventoryMovementType::AdjustmentIn)
        ->and($adjustmentIn->quantity)->toBe(20)
        ->and($adjustmentIn->quantity_before)->toBe(50)
        ->and($adjustmentIn->quantity_after)->toBe(70);

    // Adjustment out state
    $adjustmentOut = InventoryMovement::factory()->adjustmentOut(15, 60)->create();
    expect($adjustmentOut->type)->toBe(InventoryMovementType::AdjustmentOut)
        ->and($adjustmentOut->quantity)->toBe(15)
        ->and($adjustmentOut->quantity_before)->toBe(60)
        ->and($adjustmentOut->quantity_after)->toBe(45);
});

test('19. Soft-deleting ProductVariant preserves Inventory and movements in database', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::factory()->inStock(50, 10)->create([
        'product_variant_id' => $variant->id,
    ]);
    $movement = InventoryMovement::factory()->create([
        'inventory_id' => $inventory->id,
        'product_variant_id' => $variant->id,
    ]);

    // Soft delete variant (ensure business rule of having another active variant is met)
    ProductVariant::factory()->create([
        'product_id' => $variant->product_id,
        'is_active' => true,
        'is_default' => true,
    ]);

    $variant->delete();

    expect($variant->trashed())->toBeTrue();

    // Verify inventory and movement still exist untouched in database
    expect(Inventory::where('id', $inventory->id)->exists())->toBeTrue()
        ->and(InventoryMovement::where('id', $movement->id)->exists())->toBeTrue();
});
