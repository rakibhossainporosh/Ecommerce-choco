<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\ReturnRequestStatus;
use App\Enums\SettingType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    Setting::query()->updateOrCreate(
        ['key' => 'return_window_days'],
        [
            'label' => 'Return Window (Days)',
            'value' => '3',
            'type' => SettingType::Integer,
            'group' => 'orders',
            'is_public' => true,
            'is_system' => false,
        ]
    );
});

test('customer can submit a line-item return request for a delivered order', function () {
    $customer = Customer::factory()->create([
        'phone' => '01711112222',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'status' => OrderStatus::Delivered,
        'grand_total' => 500,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => 200,
        'line_total' => 400,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.orders.returns.store', $order), [
            'reason' => 'Product arrived damaged',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect(route('customer.account', ['tab' => 'returns']));

    $returnRequest = ReturnRequest::query()->where('order_id', $order->id)->first();

    expect($returnRequest)->not->toBeNull()
        ->and($returnRequest->customer_id)->toBe($customer->id)
        ->and($returnRequest->status)->toBe(ReturnRequestStatus::Pending)
        ->and((float) $returnRequest->refund_amount)->toBe(200.0)
        ->and($returnRequest->items)->toHaveCount(1)
        ->and($returnRequest->items->first()->quantity)->toBe(1);
});

test('customer cannot request return for undelivered order', function () {
    $customer = Customer::factory()->create([
        'phone' => '01711113333',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'status' => OrderStatus::Processing,
        'grand_total' => 300,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.orders.returns.store', $order), [
            'reason' => 'Changed my mind',
        ])
        ->assertSessionHasErrors('order');

    expect(ReturnRequest::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('return complete restocks and marks order returned for full return', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Delivered,
        'grand_total' => 100,
    ]);

    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'status' => ReturnRequestStatus::Approved,
        'refund_amount' => 0,
    ]);

    $returnRequest->markReceived();
    $returnRequest->complete();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Completed)
        ->and($order->fresh()->status)->toBe(OrderStatus::Returned);
});

test('customer can cancel a pending return request', function () {
    $customer = Customer::factory()->create([
        'phone' => '01711114444',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'status' => OrderStatus::Delivered,
        'grand_total' => 250,
    ]);

    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 100,
    ]);

    $this->actingAs($customer, 'customer')
        ->delete(route('customer.returns.destroy', $returnRequest))
        ->assertRedirect(route('customer.account', ['tab' => 'returns']));

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Cancelled)
        ->and($returnRequest->fresh()->admin_note)->toContain('Cancelled by customer')
        ->and($returnRequest->fresh()->rma_number)->not->toBeEmpty();
});

test('partial refund creates ledger when payment row exceeds refund amount', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 1000,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 1000,
        'status' => PaymentTransactionStatus::Completed,
    ]);

    $refunded = $order->refundCompletedPayments(
        reason: 'Partial return',
        upToAmount: 250,
    );

    expect($refunded)->toHaveCount(1)
        ->and((float) $refunded->first()->amount)->toBe(250.0)
        ->and($refunded->first()->status)->toBe(PaymentTransactionStatus::Refunded)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});
