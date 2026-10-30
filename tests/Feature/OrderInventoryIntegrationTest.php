<?php

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InventoryException;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
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
    DB::table('order_status_histories')->truncate();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    ProductVariant::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_status_histories')->truncate();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    ProductVariant::truncate();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/*
|--------------------------------------------------------------------------
| CAT-9F: ORDER + INVENTORY INTEGRATION TESTS
|--------------------------------------------------------------------------
*/

test('A. Successful confirmation deducts stock and records sale movement', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'CHOCO-BAR-100']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
        'low_stock_threshold' => 2,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 3,
    ]);

    $order->confirm();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($inventory->fresh()->quantity)->toBe(7);

    $movements = InventoryMovement::where('inventory_id', $inventory->id)->get();
    expect($movements)->toHaveCount(1);

    /** @var InventoryMovement $movement */
    $movement = $movements->first();
    expect($movement->type)->toBe(InventoryMovementType::Sale)
        ->and($movement->quantity)->toBe(3)
        ->and($movement->quantity_before)->toBe(10)
        ->and($movement->quantity_after)->toBe(7)
        ->and($movement->reference_type)->toBe(Order::class)
        ->and($movement->reference_id)->toBe($order->id)
        ->and($movement->reason)->toBe('Sale')
        ->and($movement->note)->toBe("Order #{$order->order_number}");
});

test('B. Multi-item confirmation deducts stock across multiple product variants', function () {
    $variantA = ProductVariant::factory()->create(['sku' => 'CHOCO-DARK']);
    $inventoryA = Inventory::create([
        'product_variant_id' => $variantA->id,
        'quantity' => 10,
        'low_stock_threshold' => 2,
    ]);

    $variantB = ProductVariant::factory()->create(['sku' => 'CHOCO-MILK']);
    $inventoryB = Inventory::create([
        'product_variant_id' => $variantB->id,
        'quantity' => 5,
        'low_stock_threshold' => 1,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variantA->id,
        'sku' => $variantA->sku,
        'quantity' => 2,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variantB->id,
        'sku' => $variantB->sku,
        'quantity' => 3,
    ]);

    $order->confirm();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($inventoryA->fresh()->quantity)->toBe(8)
        ->and($inventoryB->fresh()->quantity)->toBe(2);

    $movementA = InventoryMovement::where('inventory_id', $inventoryA->id)->first();
    expect($movementA->type)->toBe(InventoryMovementType::Sale)
        ->and($movementA->quantity)->toBe(2)
        ->and($movementA->quantity_before)->toBe(10)
        ->and($movementA->quantity_after)->toBe(8);

    $movementB = InventoryMovement::where('inventory_id', $inventoryB->id)->first();
    expect($movementB->type)->toBe(InventoryMovementType::Sale)
        ->and($movementB->quantity)->toBe(3)
        ->and($movementB->quantity_before)->toBe(5)
        ->and($movementB->quantity_after)->toBe(2);
});

test('C. Insufficient stock throws InventoryException, preserves pending status, and creates no movements or history', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'HAIR-100']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'low_stock_threshold' => 1,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 5,
    ]);

    expect(fn () => $order->confirm())
        ->toThrow(InventoryException::class, 'Insufficient stock for SKU HAIR-100. Available: 2. Requested: 5.');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($inventory->fresh()->quantity)->toBe(2)
        ->and(InventoryMovement::count())->toBe(0)
        ->and(OrderStatusHistory::count())->toBe(0);
});

test('D. Atomic rollback ensures no partial deductions if one item in multi-item order has insufficient stock', function () {
    $variantA = ProductVariant::factory()->create(['sku' => 'ITEM-A']);
    $inventoryA = Inventory::create([
        'product_variant_id' => $variantA->id,
        'quantity' => 10,
    ]);

    $variantB = ProductVariant::factory()->create(['sku' => 'ITEM-B']);
    $inventoryB = Inventory::create([
        'product_variant_id' => $variantB->id,
        'quantity' => 1,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variantA->id,
        'sku' => $variantA->sku,
        'quantity' => 2,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variantB->id,
        'sku' => $variantB->sku,
        'quantity' => 5,
    ]);

    expect(fn () => $order->confirm())
        ->toThrow(InventoryException::class, 'Insufficient stock for SKU ITEM-B. Available: 1. Requested: 5.');

    // Verify neither item was deducted
    expect($inventoryA->fresh()->quantity)->toBe(10)
        ->and($inventoryB->fresh()->quantity)->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(InventoryMovement::count())->toBe(0)
        ->and(OrderStatusHistory::count())->toBe(0);
});

test('E. Sale movement ledger record contains exact audit and metadata values', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $variant = ProductVariant::factory()->create(['sku' => 'AUDIT-SKU-1']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 15,
        'low_stock_threshold' => 3,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 4,
    ]);

    $this->actingAs($admin);
    $order->confirm();

    /** @var InventoryMovement $movement */
    $movement = InventoryMovement::where('inventory_id', $inventory->id)->first();

    expect($movement)->not->toBeNull()
        ->and($movement->inventory_id)->toBe($inventory->id)
        ->and($movement->product_variant_id)->toBe($variant->id)
        ->and($movement->type)->toBe(InventoryMovementType::Sale)
        ->and($movement->quantity)->toBe(4)
        ->and($movement->quantity_before)->toBe(15)
        ->and($movement->quantity_after)->toBe(11)
        ->and($movement->reference_type)->toBe(Order::class)
        ->and($movement->reference_id)->toBe($order->id)
        ->and($movement->reference)->toBeInstanceOf(Order::class)
        ->and($movement->reference->id)->toBe($order->id)
        ->and($movement->reason)->toBe('Sale')
        ->and($movement->note)->toBe("Order #{$order->order_number}")
        ->and($movement->created_by)->toBe($admin->id)
        ->and($movement->createdBy->id)->toBe($admin->id);
});

test('F. Duplicate confirmation on already confirmed order is rejected without duplicate deductions or movements', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'NO-DOUBLE-DEDUCT']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 2,
    ]);

    $order->confirm();

    expect($inventory->fresh()->quantity)->toBe(8)
        ->and(InventoryMovement::count())->toBe(1)
        ->and(OrderStatusHistory::count())->toBe(1);

    // Attempting duplicate confirm
    expect(fn () => $order->confirm())
        ->toThrow(InvalidOrderTransitionException::class);

    // Assert no double deduction or extra movements/histories
    expect($inventory->fresh()->quantity)->toBe(8)
        ->and(InventoryMovement::count())->toBe(1)
        ->and(OrderStatusHistory::count())->toBe(1);
});

test('G. Order confirmation authorization respects roles and permissions', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 1,
    ]);

    // Staff cannot confirm
    expect($staff->can('confirm', $order))->toBeFalse();

    // Manager can confirm
    expect($manager->can('confirm', $order))->toBeTrue();

    // Admin can confirm
    expect($admin->can('confirm', $order))->toBeTrue();
});

test('H. Concurrency test: competing orders for same inventory respect row-level locks without overselling', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'CONCURRENCY-SKU']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    $orderA = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $orderA->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 4,
    ]);

    $orderB = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $orderB->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 3,
    ]);

    $orderAId = $orderA->id;
    $orderBId = $orderB->id;
    $inventoryId = $inventory->id;

    // Fork child A
    $pidA = pcntl_fork();
    if ($pidA === 0) {
        DB::purge('mysql');
        try {
            $order = Order::find($orderAId);
            $order->confirm();
            exit(10); // Success
        } catch (InventoryException) {
            exit(20); // Insufficient stock
        } catch (Throwable) {
            exit(30);
        }
    }

    // Fork child B
    $pidB = pcntl_fork();
    if ($pidB === 0) {
        DB::purge('mysql');
        try {
            $order = Order::find($orderBId);
            $order->confirm();
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

    // Exactly one succeeds (code 10) and one fails due to insufficient stock (code 20)
    $oneSuccess = ($codeA === 10 && $codeB === 20) || ($codeA === 20 && $codeB === 10);
    expect($oneSuccess)->toBeTrue();

    DB::purge('mysql');
    $finalQuantity = (int) DB::table('inventories')->where('id', $inventoryId)->value('quantity');

    if ($codeA === 10) {
        expect($finalQuantity)->toBe(1); // 5 - 4 = 1
        expect(DB::table('orders')->where('id', $orderAId)->value('status'))->toBe(OrderStatus::Confirmed->value)
            ->and(DB::table('orders')->where('id', $orderBId)->value('status'))->toBe(OrderStatus::Pending->value);
    } else {
        expect($finalQuantity)->toBe(2); // 5 - 3 = 2
        expect(DB::table('orders')->where('id', $orderBId)->value('status'))->toBe(OrderStatus::Confirmed->value)
            ->and(DB::table('orders')->where('id', $orderAId)->value('status'))->toBe(OrderStatus::Pending->value);
    }

    // Final stock is non-negative and exactly 1 sale movement exists
    expect($finalQuantity)->toBeGreaterThanOrEqual(0)
        ->and(DB::table('inventory_movements')->where('inventory_id', $inventoryId)->count())->toBe(1);
});

test('I. Order confirmation creates exactly one pending to confirmed status history record', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 2,
    ]);

    $order->confirm();

    expect($order->statusHistories()->count())->toBe(1);

    /** @var OrderStatusHistory $history */
    $history = $order->statusHistories()->first();
    expect($history->from_status)->toBe(OrderStatus::Pending)
        ->and($history->to_status)->toBe(OrderStatus::Confirmed)
        ->and($history->order_id)->toBe($order->id);
});

test('J. Subsequent lifecycle transitions and cancellation do not mutate stock in CAT-9F', function () {
    $variant = ProductVariant::factory()->create();
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 3,
    ]);

    // Confirm: deducts 10 -> 7
    $order->confirm();
    expect($inventory->fresh()->quantity)->toBe(7)
        ->and(InventoryMovement::count())->toBe(1);

    // Processing: no additional stock deduction
    $order->startProcessing();
    expect($inventory->fresh()->quantity)->toBe(7)
        ->and(InventoryMovement::count())->toBe(1);

    // Shipped: no additional stock deduction
    $order->ship();
    expect($inventory->fresh()->quantity)->toBe(7)
        ->and(InventoryMovement::count())->toBe(1);

    // Delivered: no additional stock deduction
    $order->deliver();
    expect($inventory->fresh()->quantity)->toBe(7)
        ->and(InventoryMovement::count())->toBe(1);

    // Cancellation of a confirmed order does not restore stock in CAT-9F
    $order2 = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 2,
    ]);
    $order2->confirm();
    expect($inventory->fresh()->quantity)->toBe(5);

    $order2->cancel('Customer requested cancellation');
    expect($order2->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($inventory->fresh()->quantity)->toBe(5)
        ->and(InventoryMovement::count())->toBe(2);
});

test('K. Filament confirm action catches InventoryException and displays error notification', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $variant = ProductVariant::factory()->create(['sku' => 'LOW-STOCK-SKU']);
    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 5,
    ]);

    $this->actingAs($manager);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('confirm')
        ->assertNotified('Transition Failed');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($inventory->fresh()->quantity)->toBe(1)
        ->and(InventoryMovement::count())->toBe(0);
});

test('L. Confirmation of order without initialized inventory throws notInitializedForOrder exception', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'UNINITIALIZED-SKU']);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'quantity' => 1,
    ]);

    expect(fn () => $order->confirm())
        ->toThrow(InventoryException::class, 'Inventory record not initialized for SKU UNINITIALIZED-SKU.');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(InventoryMovement::count())->toBe(0)
        ->and(OrderStatusHistory::count())->toBe(0);
});
