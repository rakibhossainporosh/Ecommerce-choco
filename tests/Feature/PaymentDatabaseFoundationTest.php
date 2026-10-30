<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
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

    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
});

test('1. payments table has expected schema columns and types', function () {
    expect(Schema::hasTable('payments'))->toBeTrue();

    $expectedColumns = [
        'id',
        'payment_number',
        'order_id',
        'customer_id',
        'payment_method',
        'status',
        'amount',
        'currency',
        'transaction_id',
        'account_number',
        'paid_at',
        'recorded_by',
        'notes',
        'metadata',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('payments', $column))->toBeTrue("Missing column: {$column}");
    }
});

test('2. payment_number must be unique', function () {
    $order = Order::factory()->create();

    Payment::factory()->create([
        'payment_number' => 'PAY-UNIQUE-101',
        'order_id' => $order->id,
    ]);

    expect(fn () => Payment::factory()->create([
        'payment_number' => 'PAY-UNIQUE-101',
        'order_id' => $order->id,
    ]))->toThrow(QueryException::class);
});

test('3. order_id foreign key restricts deletion of parent order', function () {
    $order = Order::factory()->create();
    Payment::factory()->create(['order_id' => $order->id]);

    expect(fn () => $order->delete())->toThrow(QueryException::class);
});

test('4. customer_id is nulled when customer is deleted (nullOnDelete)', function () {
    $customer = Customer::factory()->create();
    $payment = Payment::factory()->create(['customer_id' => $customer->id]);

    $customer->delete();
    $payment->refresh();

    expect($payment->customer_id)->toBeNull();
});

test('5. recorded_by is nulled when recording user is deleted (nullOnDelete)', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->create(['recorded_by' => $user->id]);

    $user->delete();
    $payment->refresh();

    expect($payment->recorded_by)->toBeNull();
});

test('6. payments table assigns correct column defaults', function () {
    $order = Order::factory()->create();

    $payment = Payment::create([
        'order_id' => $order->id,
        'amount' => 500.00,
    ]);

    expect($payment->currency)->toBe('BDT')
        ->and($payment->status)->toBe(PaymentTransactionStatus::Pending)
        ->and($payment->payment_method)->toBe(PaymentMethod::Cod)
        ->and($payment->payment_number)->toStartWith('PAY-');
});
