<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
});

/*
|--------------------------------------------------------------------------
| CAT-9B: ORDER DOMAIN + STATE TRANSITION TESTS
|--------------------------------------------------------------------------
*/

test('1. pending -> confirmed succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect($order->canTransitionTo(OrderStatus::Confirmed))->toBeTrue();

    $order->confirm();

    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->isConfirmed())->toBeTrue();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Confirmed->value,
    ]);
});

test('2. confirmed -> processing succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

    expect($order->canTransitionTo(OrderStatus::Processing))->toBeTrue();

    $order->startProcessing();

    expect($order->status)->toBe(OrderStatus::Processing)
        ->and($order->isProcessing())->toBeTrue();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Processing->value,
    ]);
});

test('3. processing -> shipped succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Processing]);

    expect($order->canTransitionTo(OrderStatus::Shipped))->toBeTrue();

    $order->ship();

    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->isShipped())->toBeTrue();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Shipped->value,
    ]);
});

test('4. shipped -> delivered succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

    expect($order->canTransitionTo(OrderStatus::Delivered))->toBeTrue();

    $order->deliver();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->isDelivered())->toBeTrue();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Delivered->value,
    ]);
});

test('5. pending -> cancelled succeeds with reason and user', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $user = User::factory()->create();

    expect($order->canBeCancelled())->toBeTrue();

    $order->cancel('Customer requested cancellation', $user);

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->isCancelled())->toBeTrue()
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($order->cancelled_by)->toBe($user->id)
        ->and($order->cancelledBy->id)->toBe($user->id)
        ->and($order->cancellation_reason)->toBe('Customer requested cancellation');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
        'cancelled_by' => $user->id,
        'cancellation_reason' => 'Customer requested cancellation',
    ]);
});

test('6. confirmed -> cancelled succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);
    $user = User::factory()->create();

    expect($order->canBeCancelled())->toBeTrue();

    $order->cancel('Item out of stock after confirmation', $user);

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->isCancelled())->toBeTrue()
        ->and($order->cancellation_reason)->toBe('Item out of stock after confirmation');
});

test('7. processing -> cancelled succeeds', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Processing]);

    expect($order->canBeCancelled())->toBeTrue();

    $order->cancel('Manufacturing defect detected during packing');

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->isCancelled())->toBeTrue()
        ->and($order->cancellation_reason)->toBe('Manufacturing defect detected during packing');
});

test('8. Invalid transitions are rejected', function () {
    $invalidTransitions = [
        [OrderStatus::Pending, OrderStatus::Processing],
        [OrderStatus::Pending, OrderStatus::Shipped],
        [OrderStatus::Pending, OrderStatus::Delivered],
        [OrderStatus::Confirmed, OrderStatus::Shipped],
        [OrderStatus::Confirmed, OrderStatus::Delivered],
        [OrderStatus::Confirmed, OrderStatus::Pending],
        [OrderStatus::Processing, OrderStatus::Pending],
        [OrderStatus::Processing, OrderStatus::Confirmed],
        [OrderStatus::Processing, OrderStatus::Delivered],
        [OrderStatus::Shipped, OrderStatus::Pending],
        [OrderStatus::Shipped, OrderStatus::Confirmed],
        [OrderStatus::Shipped, OrderStatus::Processing],
        [OrderStatus::Shipped, OrderStatus::Cancelled],
    ];

    foreach ($invalidTransitions as [$from, $to]) {
        $order = Order::factory()->create(['status' => $from]);

        expect($order->canTransitionTo($to))->toBeFalse();

        try {
            $order->transitionTo($to);
            $this->fail("Expected transition from {$from->value} to {$to->value} to fail.");
        } catch (InvalidOrderTransitionException $e) {
            expect($e->fromStatus)->toBe($from)
                ->and($e->toStatus)->toBe($to)
                ->and($e->getMessage())->toBe("Cannot transition order from {$from->value} to {$to->value}.");
        }
    }
});

test('9. Terminal states cannot transition into normal states', function () {
    $terminalStates = [
        OrderStatus::Delivered,
        OrderStatus::Cancelled,
        OrderStatus::Returned,
        OrderStatus::Refunded,
    ];

    $normalTargets = [
        OrderStatus::Pending,
        OrderStatus::Confirmed,
        OrderStatus::Processing,
        OrderStatus::Shipped,
    ];

    foreach ($terminalStates as $terminal) {
        expect($terminal->isTerminal())->toBeTrue()
            ->and($terminal->allowedTransitions())->toBeEmpty();

        foreach ($normalTargets as $target) {
            $order = Order::factory()->create(['status' => $terminal]);

            expect($order->canTransitionTo($target))->toBeFalse();

            expect(fn () => $order->transitionTo($target))
                ->toThrow(InvalidOrderTransitionException::class, "Cannot transition order from {$terminal->value} to {$target->value}.");
        }
    }
});

test('10. Cancellation requires a non-empty reason', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Empty string
    expect(fn () => $order->cancel(''))
        ->toThrow(InvalidOrderTransitionException::class, 'Cancellation reason cannot be empty.');

    // Whitespace only
    expect(fn () => $order->cancel("   \t\n  "))
        ->toThrow(InvalidOrderTransitionException::class, 'Cancellation reason cannot be empty.');

    // Direct transitionTo without reason
    expect(fn () => $order->transitionTo(OrderStatus::Cancelled))
        ->toThrow(InvalidOrderTransitionException::class, 'Cancellation reason cannot be empty.');
});

test('11. Cancellation stores cancelled_at timestamp', function () {
    Carbon::setTestNow('2026-10-02 14:30:00');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $order->cancel('Customer changed mind');

    expect($order->cancelled_at->format('Y-m-d H:i:s'))->toBe('2026-10-02 14:30:00');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'cancelled_at' => '2026-10-02 14:30:00',
    ]);

    Carbon::setTestNow();
});

test('12. Cancellation stores cancelled_by user ID', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $staff = User::factory()->create();

    $order->cancel('Support staff cancelled upon phone call', $staff);

    expect($order->cancelled_by)->toBe($staff->id)
        ->and($order->cancelledBy)->not->toBeNull()
        ->and($order->cancelledBy->id)->toBe($staff->id);
});

test('13. Cancellation works with nullable cancelled_by for system cancellations', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $order->cancel('System automated timeout: payment unreceived', null);

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->cancelled_by)->toBeNull()
        ->and($order->cancelledBy)->toBeNull()
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($order->cancellation_reason)->toBe('System automated timeout: payment unreceived');
});

test('14. PaymentStatus is not automatically changed by OrderStatus transition', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $order->confirm();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $order->startProcessing();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $order->ship();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $order->deliver();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    // Cancel an order that had paid status
    $paidOrder = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $paidOrder->cancel('Customer cancelled before processing');
    expect($paidOrder->status)->toBe(OrderStatus::Cancelled)
        ->and($paidOrder->payment_status)->toBe(PaymentStatus::Paid);
});

test('15. Convenience methods delegate to central transition logic', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // confirm delegates to transitionTo(Confirmed)
    $order->confirm();
    expect($order->status)->toBe(OrderStatus::Confirmed);

    // startProcessing delegates to transitionTo(Processing)
    $order->startProcessing();
    expect($order->status)->toBe(OrderStatus::Processing);

    // ship delegates to transitionTo(Shipped)
    $order->ship();
    expect($order->status)->toBe(OrderStatus::Shipped);

    // deliver delegates to transitionTo(Delivered)
    $order->deliver();
    expect($order->status)->toBe(OrderStatus::Delivered);
});

test('16. Invalid transition does not partially modify the order in memory or database', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    try {
        $order->transitionTo(OrderStatus::Processing);
    } catch (InvalidOrderTransitionException) {
        // Expected
    }

    expect($order->status)->toBe(OrderStatus::Delivered);

    $freshOrder = Order::find($order->id);
    expect($freshOrder->status)->toBe(OrderStatus::Delivered);
});

test('17. Cancellation failure does not partially update cancellation metadata', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);
    $admin = User::factory()->create();

    try {
        $order->cancel('Delivered package cancellation attempt', $admin);
    } catch (InvalidOrderTransitionException) {
        // Expected
    }

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->cancelled_at)->toBeNull()
        ->and($order->cancelled_by)->toBeNull()
        ->and($order->cancellation_reason)->toBeNull();

    $fresh = Order::find($order->id);
    expect($fresh->status)->toBe(OrderStatus::Delivered)
        ->and($fresh->cancelled_at)->toBeNull()
        ->and($fresh->cancelled_by)->toBeNull()
        ->and($fresh->cancellation_reason)->toBeNull();

    // Test failed cancellation on Pending order with empty reason
    $pendingOrder = Order::factory()->create(['status' => OrderStatus::Pending]);

    try {
        $pendingOrder->cancel('   ');
    } catch (InvalidOrderTransitionException) {
        // Expected
    }

    expect($pendingOrder->status)->toBe(OrderStatus::Pending)
        ->and($pendingOrder->cancelled_at)->toBeNull()
        ->and($pendingOrder->cancellation_reason)->toBeNull();
});

test('18. Transition exception contains useful current and target status information', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    try {
        $order->transitionTo(OrderStatus::Processing);
        $this->fail('Exception not thrown.');
    } catch (InvalidOrderTransitionException $e) {
        expect($e->fromStatus)->toBe(OrderStatus::Delivered)
            ->and($e->toStatus)->toBe(OrderStatus::Processing)
            ->and($e->fromStatus->value)->toBe('delivered')
            ->and($e->toStatus->value)->toBe('processing')
            ->and($e->getMessage())->toContain('delivered')
            ->and($e->getMessage())->toContain('processing');
    }
});

test('19. Multiple sequential valid transitions work correctly across full order lifecycle', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $order->confirm();
    expect($order->status)->toBe(OrderStatus::Confirmed);

    $order->startProcessing();
    expect($order->status)->toBe(OrderStatus::Processing);

    $order->ship();
    expect($order->status)->toBe(OrderStatus::Shipped);

    $order->deliver();
    expect($order->status)->toBe(OrderStatus::Delivered);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Delivered->value,
    ]);
});

test('20. A previously cancelled order cannot be reactivated', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $order->cancel('Cancelled by customer');

    expect($order->isCancelled())->toBeTrue();

    expect(fn () => $order->confirm())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from cancelled to confirmed.');

    expect(fn () => $order->startProcessing())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from cancelled to processing.');

    expect(fn () => $order->ship())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from cancelled to shipped.');

    expect(fn () => $order->deliver())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from cancelled to delivered.');
});

test('21. A delivered order cannot be cancelled', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    expect($order->canBeCancelled())->toBeFalse();

    expect(fn () => $order->cancel('Cannot cancel delivered parcel'))
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from delivered to cancelled.');
});

test('22. A shipped order can only transition to delivered and cannot be cancelled', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

    expect($order->canTransitionTo(OrderStatus::Delivered))->toBeTrue()
        ->and($order->canTransitionTo(OrderStatus::Cancelled))->toBeFalse()
        ->and($order->canTransitionTo(OrderStatus::Processing))->toBeFalse()
        ->and($order->canTransitionTo(OrderStatus::Confirmed))->toBeFalse()
        ->and($order->canTransitionTo(OrderStatus::Pending))->toBeFalse();

    expect(fn () => $order->cancel('Courier returned early'))
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from shipped to cancelled.');
});

test('23. Database state remains consistent after failed transitions', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'customer_note' => 'Original note',
    ]);

    try {
        $order->transitionTo(OrderStatus::Pending);
    } catch (InvalidOrderTransitionException) {
        // Expected
    }

    $rawRecord = DB::table('orders')->where('id', $order->id)->first();
    expect($rawRecord->status)->toBe(OrderStatus::Processing->value)
        ->and($rawRecord->customer_note)->toBe('Original note');
});

test('24. Cannot transition an unsaved order', function () {
    $unsavedOrder = new Order(['status' => OrderStatus::Pending]);

    expect(fn () => $unsavedOrder->confirm())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition an unsaved order.');
});

test('25. transitionTo method signature does not accept arbitrary attributes', function () {
    $reflection = new ReflectionMethod(Order::class, 'transitionTo');
    $parameters = $reflection->getParameters();

    expect($parameters)->toHaveCount(1)
        ->and($parameters[0]->getName())->toBe('targetStatus')
        ->and($parameters[0]->getType()->getName())->toBe(OrderStatus::class);
});

test('26. Concurrency: conflicting state transitions on stale model state are rejected by row locking', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Processing]);

    // Model instance 1 (Process A)
    $orderInstance1 = Order::find($order->id);

    // Model instance 2 (Process B) holding stale in-memory state
    $orderInstance2 = Order::find($order->id);

    // Process A ships the order
    $orderInstance1->ship();
    expect($orderInstance1->status)->toBe(OrderStatus::Shipped);

    // Process B (stale in-memory, thinks order is still Processing) attempts to cancel
    // Under row locking, transaction reads fresh locked status (Shipped) and rejects cancellation
    expect(fn () => $orderInstance2->cancel('Cancellation attempt while package was already shipped'))
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from shipped to cancelled.');

    // Database remains Shipped
    $fresh = Order::find($order->id);
    expect($fresh->status)->toBe(OrderStatus::Shipped);
});
