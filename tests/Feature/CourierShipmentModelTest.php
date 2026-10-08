<?php

use App\Enums\CourierShipmentStatus;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');
});

test('courier shipment can be created', function () {
    $order = Order::factory()->create();
    $courier = Courier::factory()->create();

    $shipment = CourierShipment::factory()->create([
        'order_id' => $order->id,
        'courier_id' => $courier->id,
        'tracking_number' => 'TRACK123',
    ]);

    expect($shipment->id)->not->toBeNull()
        ->and($shipment->tracking_number)->toBe('TRACK123')
        ->and($shipment->order->id)->toBe($order->id)
        ->and($shipment->courier->id)->toBe($courier->id)
        ->and($shipment->status)->toBe(CourierShipmentStatus::Pending);
});

test('courier cannot be hard deleted if shipments exist (restrict foreign key)', function () {
    $shipment = CourierShipment::factory()->create();
    $courier = $shipment->courier;

    expect(fn () => $courier->delete())
        ->toThrow(QueryException::class); // foreign key restrict
});

test('order deletion cascades to courier shipments', function () {
    $shipment = CourierShipment::factory()->create();
    $order = $shipment->order;
    $shipmentId = $shipment->id;

    $order->delete();

    expect(CourierShipment::where('id', $shipmentId)->exists())->toBeFalse();
});
