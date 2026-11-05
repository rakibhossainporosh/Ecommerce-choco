<?php

use App\Enums\ShippingProvider;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

test('track order requires phone when an order number is provided', function () {
    $this->get('/track-order?order_number=ORD-TEST-1')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderTrack', false)
            ->where('order', null)
            ->where('error', __('storefront.track.phone_required'))
        );
});

test('track order returns shipment tracking when phone matches', function () {
    $customer = Customer::factory()->create([
        'phone' => '01890000001',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => '01890000001',
        'order_number' => 'ORD-TRACK-1',
    ]);

    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'provider' => ShippingProvider::Steadfast,
        'tracking_code' => 'SF123456',
    ]);

    $this->get('/track-order?order_number=ORD-TRACK-1&phone=01890000001')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderTrack', false)
            ->where('order.order_number', 'ORD-TRACK-1')
            ->has('order.shipments', 1)
            ->where('order.shipments.0.tracking_code', 'SF123456')
            ->where('order.shipments.0.tracking_url', $shipment->getTrackingUrl())
        );
});

test('track order without shipment still succeeds with empty shipments', function () {
    Order::factory()->create([
        'customer_phone' => '01890000002',
        'order_number' => 'ORD-TRACK-2',
    ]);

    $this->get('/track-order?order_number=ORD-TRACK-2&phone=01890000002')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderTrack', false)
            ->where('order.order_number', 'ORD-TRACK-2')
            ->has('order.shipments', 0)
        );
});

test('wrong phone does not reveal the order', function () {
    Order::factory()->create([
        'customer_phone' => '01890000003',
        'order_number' => 'ORD-TRACK-3',
    ]);

    $this->get('/track-order?order_number=ORD-TRACK-3&phone=01890000099')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('OrderTrack', false)
            ->where('order', null)
            ->where('error', __('storefront.track.not_found'))
        );
});
