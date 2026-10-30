<?php

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\ShippingException;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');
});

test('shipment can transition to packed and update order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::Pending,
    ]);
    $user = User::factory()->create();

    $shipment->markAsPacked($user);

    expect($shipment->status)->toBe(ShipmentStatus::Packed)
        ->and($shipment->packed_at)->not->toBeNull()
        ->and($shipment->dispatched_by)->toBe($user->id);

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('shipment can transition to shipped and update order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Processing]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::Packed,
    ]);
    $user = User::factory()->create();

    $shipment->markAsShipped('TRACK123', $user);

    expect($shipment->status)->toBe(ShipmentStatus::InTransit)
        ->and($shipment->shipped_at)->not->toBeNull()
        ->and($shipment->tracking_code)->toBe('TRACK123')
        ->and($shipment->dispatched_by)->toBe($user->id);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('shipment can transition to out for delivery', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::InTransit,
    ]);
    $user = User::factory()->create();

    $shipment->markAsOutForDelivery($user);

    expect($shipment->status)->toBe(ShipmentStatus::OutForDelivery)
        ->and($shipment->dispatched_by)->toBe($user->id);

    // Order status should remain Shipped
    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('shipment can transition to delivered and update order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::OutForDelivery,
    ]);
    $user = User::factory()->create();

    $shipment->markAsDelivered($user);

    expect($shipment->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->delivered_at)->not->toBeNull()
        ->and($shipment->dispatched_by)->toBe($user->id);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

test('shipment can transition to failed delivery', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::OutForDelivery,
    ]);
    $user = User::factory()->create();

    $shipment->markAsFailedDelivery('Customer not available', $user);

    expect($shipment->status)->toBe(ShipmentStatus::FailedDelivery)
        ->and($shipment->notes)->toContain('Attempt Failed: Customer not available')
        ->and($shipment->dispatched_by)->toBe($user->id);
});

test('shipment can transition to returned', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'status' => ShipmentStatus::FailedDelivery,
    ]);
    $user = User::factory()->create();

    $shipment->markAsReturned('Address incorrect', $user);

    expect($shipment->status)->toBe(ShipmentStatus::Returned)
        ->and($shipment->returned_at)->not->toBeNull()
        ->and($shipment->notes)->toContain('Returned: Address incorrect')
        ->and($shipment->dispatched_by)->toBe($user->id);
});

test('shipment throws exception on invalid transition', function () {
    $shipment = Shipment::factory()->create([
        'status' => ShipmentStatus::Delivered,
    ]);

    expect(fn () => $shipment->markAsPacked())
        ->toThrow(ShippingException::class);
});

test('shipment cancels successfully', function () {
    $shipment = Shipment::factory()->create([
        'status' => ShipmentStatus::Pending,
    ]);
    $user = User::factory()->create();

    $shipment->cancel('Order cancelled by customer', $user);

    expect($shipment->status)->toBe(ShipmentStatus::Cancelled)
        ->and($shipment->cancelled_at)->not->toBeNull()
        ->and($shipment->notes)->toContain('Cancelled: Order cancelled by customer')
        ->and($shipment->dispatched_by)->toBe($user->id);
});
