<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
});

test('1. customers table has expected schema columns and types', function () {
    expect(Schema::hasTable('customers'))->toBeTrue();

    $expectedColumns = [
        'id',
        'user_id',
        'name',
        'phone',
        'email',
        'is_active',
        'notes',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('customers', $column))->toBeTrue("Missing column: {$column}");
    }
});

test('2. customer_addresses table has expected schema columns and types', function () {
    expect(Schema::hasTable('customer_addresses'))->toBeTrue();

    $expectedColumns = [
        'id',
        'customer_id',
        'type',
        'name',
        'phone',
        'address_line',
        'area',
        'city',
        'postcode',
        'country',
        'is_default',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('customer_addresses', $column))->toBeTrue("Missing column: {$column}");
    }
});

test('3. orders table has customer_id column with foreign key', function () {
    expect(Schema::hasColumn('orders', 'customer_id'))->toBeTrue();
});

test('4. customer phone number must be unique', function () {
    Customer::factory()->create(['phone' => '01711999888']);

    expect(fn () => Customer::factory()->create(['phone' => '01711999888']))
        ->toThrow(QueryException::class);
});

test('5. customer user_id must be unique when provided', function () {
    $user = User::factory()->create();

    Customer::factory()->create(['user_id' => $user->id]);

    expect(fn () => Customer::factory()->create(['user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

test('6. multiple guest customers can have null user_id', function () {
    $c1 = Customer::factory()->create(['user_id' => null, 'phone' => '01711000001']);
    $c2 = Customer::factory()->create(['user_id' => null, 'phone' => '01711000002']);

    expect($c1->id)->not->toBeNull()
        ->and($c2->id)->not->toBeNull();
});

test('7. deleting user sets customer user_id to null (nullOnDelete)', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    $user->delete();
    $customer->refresh();

    expect($customer->user_id)->toBeNull();
});

test('8. deleting customer cascades to customer addresses', function () {
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    $customer->delete();

    expect(CustomerAddress::find($address->id))->toBeNull();
});

test('9. deleting customer sets order customer_id to null (nullOnDelete)', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $customer->delete();
    $order->refresh();

    expect($order->customer_id)->toBeNull();
});
