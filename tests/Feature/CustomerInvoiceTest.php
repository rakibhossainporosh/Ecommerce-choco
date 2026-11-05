<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(StorefrontSeeder::class);
});

test('guest cannot download customer invoice', function () {
    $order = Order::factory()->create();

    $this->get(route('customer.orders.invoice', $order))
        ->assertForbidden();
});

test('customer can download own order invoice', function () {
    $customer = Customer::factory()->create([
        'phone' => '01715556677',
        'is_active' => true,
    ]);
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'customer_name' => $customer->name,
        'order_number' => 'ORD-CUST-INV-1',
        'status' => OrderStatus::Confirmed,
    ]);

    OrderItem::factory()->forVariant($product->defaultVariant)->create([
        'order_id' => $order->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.invoice', $order))
        ->assertSuccessful()
        ->assertSee('ORD-CUST-INV-1')
        ->assertSee($customer->name, false);
});

test('customer cannot download another customer invoice', function () {
    $owner = Customer::factory()->create(['phone' => '01710000001', 'is_active' => true]);
    $intruder = Customer::factory()->create(['phone' => '01710000002', 'is_active' => true]);

    $order = Order::factory()->create([
        'customer_id' => $owner->id,
        'customer_phone' => $owner->phone,
    ]);

    $this->actingAs($intruder, 'customer')
        ->get(route('customer.orders.invoice', $order))
        ->assertForbidden();
});
