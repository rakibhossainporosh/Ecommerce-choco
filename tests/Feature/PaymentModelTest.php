<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Exceptions\PaymentException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

test('1. payment model casts attributes correctly', function () {
    $order = Order::factory()->create();

    $payment = Payment::create([
        'order_id' => $order->id,
        'amount' => 750.50,
        'payment_method' => PaymentMethod::Bkash,
        'status' => PaymentTransactionStatus::Completed,
        'paid_at' => now(),
        'metadata' => ['gateway_fee' => 12.50],
    ]);

    expect($payment->payment_method)->toBe(PaymentMethod::Bkash)
        ->and($payment->status)->toBe(PaymentTransactionStatus::Completed)
        ->and((float) $payment->amount)->toBe(750.50)
        ->and($payment->paid_at)->toBeInstanceOf(Carbon::class)
        ->and($payment->metadata)->toBe(['gateway_fee' => 12.50]);
});

test('2. payment relations resolve correctly', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $staff = User::factory()->create();

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'recorded_by' => $staff->id,
    ]);

    expect($payment->order->id)->toBe($order->id)
        ->and($payment->customer->id)->toBe($customer->id)
        ->and($payment->recordedBy->id)->toBe($staff->id);
});

test('3. markAsCompleted transitions status and updates order payment status', function () {
    $order = Order::factory()->create([
        'grand_total' => 1000.00,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $payment = Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => 1000.00,
    ]);

    $staff = User::factory()->create();
    $payment->markAsCompleted(actor: $staff);

    expect($payment->status)->toBe(PaymentTransactionStatus::Completed)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->recorded_by)->toBe($staff->id);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->total_paid)->toBe(1000.00)
        ->and($order->due_amount)->toBe(0.00);
});

test('4. markAsCompleted throws exception if already completed or refunded', function () {
    $payment = Payment::factory()->create([
        'status' => PaymentTransactionStatus::Completed,
    ]);

    expect(fn () => $payment->markAsCompleted())
        ->toThrow(PaymentException::class);

    $refundedPayment = Payment::factory()->refunded()->create();

    expect(fn () => $refundedPayment->markAsCompleted())
        ->toThrow(PaymentException::class);
});

test('5. markAsFailed transitions status and appends failure reason', function () {
    $payment = Payment::factory()->pending()->create();

    $payment->markAsFailed(reason: 'TrxID verification failed');

    expect($payment->status)->toBe(PaymentTransactionStatus::Failed)
        ->and($payment->notes)->toContain('Failed: TrxID verification failed');
});

test('6. refund transitions status and updates order payment status', function () {
    $order = Order::factory()->create([
        'grand_total' => 500.00,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 500.00,
        'status' => PaymentTransactionStatus::Completed,
    ]);

    $payment->refund(reason: 'Customer return');

    expect($payment->status)->toBe(PaymentTransactionStatus::Refunded)
        ->and($payment->notes)->toContain('Refunded: Customer return');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($order->total_paid)->toBe(0.00)
        ->and($order->due_amount)->toBe(500.00);
});

test('7. refund throws exception when payment is not completed', function () {
    $pendingPayment = Payment::factory()->pending()->create();

    expect(fn () => $pendingPayment->refund(reason: 'Illegal refund'))
        ->toThrow(PaymentException::class);
});

test('8. partial payments update order status to Partial and compute remaining due', function () {
    $order = Order::factory()->create([
        'grand_total' => 1500.00,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $order->recordPayment(
        amount: 500.00,
        method: PaymentMethod::Bkash,
        transactionId: 'TRX_PART_1'
    );

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Partial)
        ->and($order->total_paid)->toBe(500.00)
        ->and($order->due_amount)->toBe(1000.00);

    // Record second payment completing the balance
    $order->recordPayment(
        amount: 1000.00,
        method: PaymentMethod::Nagad,
        transactionId: 'TRX_PART_2'
    );

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->total_paid)->toBe(1500.00)
        ->and($order->due_amount)->toBe(0.00);
});

test('9. recordPayment rejects payment on cancelled order', function () {
    $order = Order::factory()->cancelled()->create();

    expect(fn () => $order->recordPayment(amount: 500.00))
        ->toThrow(PaymentException::class);
});

test('10. recordPayment rejects invalid amount', function () {
    $order = Order::factory()->create();

    expect(fn () => $order->recordPayment(amount: 0.00))
        ->toThrow(PaymentException::class);

    expect(fn () => $order->recordPayment(amount: -100.00))
        ->toThrow(PaymentException::class);
});
