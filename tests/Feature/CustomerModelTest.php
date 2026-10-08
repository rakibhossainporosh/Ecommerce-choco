<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);

    Schema::disableForeignKeyConstraints();
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Customer relates to User, CustomerAddresses, and Orders', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->withUser($user)->create();
    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    expect($customer->user->id)->toBe($user->id)
        ->and($user->customer->id)->toBe($customer->id)
        ->and($customer->addresses)->toHaveCount(1)
        ->and($customer->addresses->first()->id)->toBe($address->id)
        ->and($customer->orders)->toHaveCount(1)
        ->and($customer->orders->first()->id)->toBe($order->id)
        ->and($order->customer->id)->toBe($customer->id);
});

test('2. canBeDeleted returns true only when customer has zero orders', function () {
    $customerWithoutOrders = Customer::factory()->create();
    expect($customerWithoutOrders->canBeDeleted())->toBeTrue();

    $customerWithOrders = Customer::factory()->create();
    Order::factory()->create(['customer_id' => $customerWithOrders->id]);

    expect($customerWithOrders->canBeDeleted())->toBeFalse();
});

test('3. total_spent sums non-cancelled orders', function () {
    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'grand_total' => 1500.00,
        'status' => OrderStatus::Delivered,
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'grand_total' => 500.00,
        'status' => OrderStatus::Processing,
    ]);

    Order::factory()->cancelled()->create([
        'customer_id' => $customer->id,
        'grand_total' => 800.00,
    ]);

    expect($customer->total_spent)->toEqual(2000.00);
});

test('4. orders_count returns total count of placed orders', function () {
    $customer = Customer::factory()->create();

    Order::factory()->count(3)->create(['customer_id' => $customer->id]);

    expect($customer->orders_count)->toBe(3);
});

test('5. has_user_account correctly identifies registered vs guest customers', function () {
    $guest = Customer::factory()->create(['user_id' => null]);
    $registered = Customer::factory()->withUser()->create();

    expect($guest->has_user_account)->toBeFalse()
        ->and($registered->has_user_account)->toBeTrue();
});

test('6. defaultShippingAddress and defaultBillingAddress resolve correct addresses', function () {
    $customer = Customer::factory()->create();

    $shipping1 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'shipping',
        'is_default' => false,
    ]);

    $shipping2 = CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->id,
        'type' => 'shipping',
    ]);

    $billing = CustomerAddress::factory()->billing()->default()->create([
        'customer_id' => $customer->id,
    ]);

    $customer->refresh();

    expect($customer->defaultShippingAddress->id)->toBe($shipping2->id)
        ->and($customer->defaultBillingAddress->id)->toBe($billing->id);
});

test('7. setAsDefault atomically clears previous default of same type', function () {
    $customer = Customer::factory()->create();

    $addr1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->id,
        'type' => 'shipping',
    ]);

    $addr2 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'shipping',
        'is_default' => false,
    ]);

    expect($addr1->fresh()->is_default)->toBeTrue()
        ->and($addr2->fresh()->is_default)->toBeFalse();

    $addr2->setAsDefault();

    expect($addr1->fresh()->is_default)->toBeFalse()
        ->and($addr2->fresh()->is_default)->toBeTrue();
});

test('8. query scopes filter active, registered, and guest customers', function () {
    Customer::factory()->create(['is_active' => true, 'user_id' => null]);
    Customer::factory()->inactive()->create(['user_id' => null]);
    Customer::factory()->withUser()->create(['is_active' => true]);

    expect(Customer::active()->count())->toBe(2)
        ->and(Customer::registered()->count())->toBe(1)
        ->and(Customer::guest()->count())->toBe(2);
});
