<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Support\ReturnItemValidator;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('create always gets an rma number and status history entry', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered, 'grand_total' => 200]);

    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 50,
    ]);

    expect($returnRequest->rma_number)->toStartWith('RMA-')
        ->and($returnRequest->statusHistories)->toHaveCount(1)
        ->and($returnRequest->statusHistories->first()->to_status)->toBe(ReturnRequestStatus::Pending);
});

test('approve and reject use locked lifecycle transitions and timeline', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered, 'grand_total' => 200]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 50,
    ]);

    $returnRequest->approve();
    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Approved);

    $returnRequest->reject('Damaged packaging not eligible');
    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Rejected)
        ->and($returnRequest->fresh()->statusHistories->count())->toBeGreaterThanOrEqual(3);
});

test('line item validator blocks over-return quantities', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered, 'grand_total' => 400]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => 200,
        'line_total' => 400,
    ]);

    ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Completed,
        'refund_amount' => 200,
    ])->items()->create([
        'order_item_id' => $item->id,
        'quantity' => 1,
        'refund_amount' => 200,
    ]);

    expect(fn () => ReturnItemValidator::normalize($order, [
        ['order_item_id' => $item->id, 'quantity' => 2],
    ]))->toThrow(DomainException::class);
});

test('full remaining return includes shipping in refund total', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 460,
        'shipping_amount' => 60,
        'subtotal' => 400,
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => 200,
        'line_total' => 400,
    ]);

    $normalized = ReturnItemValidator::normalize($order, [
        ['order_item_id' => $item->id, 'quantity' => 2],
    ]);

    expect(ReturnItemValidator::refundTotal($order, $normalized))->toBe(460.0);
});

test('partial refund ledger marks payment status partially refunded', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 1000,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 1000,
        'status' => PaymentTransactionStatus::Completed,
        'payment_method' => 'cod',
    ]);

    $refunded = $order->refundCompletedPayments(reason: 'Partial RMA', upToAmount: 250);

    expect($refunded)->toHaveCount(1)
        ->and((float) $refunded->first()->amount)->toBe(250.0)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('empty-line create auto-expands to all remaining items and blocks double restock', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 460,
        'shipping_amount' => 60,
        'subtotal' => 400,
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => 200,
        'line_total' => 400,
    ]);

    $returnRequest = ReturnRequest::createPendingForOrder($order, [
        'reason' => 'Full return without lines',
        'refund_amount' => 200,
    ], []);

    expect($returnRequest->items)->toHaveCount(1)
        ->and($returnRequest->items->first()->order_item_id)->toBe($item->id)
        ->and($returnRequest->items->first()->quantity)->toBe(2)
        ->and((float) $returnRequest->refund_amount)->toBe(200.0);

    $returnRequest->approve();
    $returnRequest->markReceived();
    $returnRequest->complete();

    expect(ReturnItemValidator::remainingQuantities($order->fresh())->get($item->id))->toBe(0);

    expect(fn () => ReturnRequest::createPendingForOrder($order->fresh(), [
        'reason' => 'Should fail — nothing left',
        'refund_amount' => 0,
    ], []))->toThrow(DomainException::class, 'No returnable items remain on this order.');
});

test('admin cannot open a second in-flight return for the same order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered, 'grand_total' => 300]);

    ReturnRequest::createPendingForOrder($order, [
        'reason' => 'First return',
        'refund_amount' => 100,
    ]);

    expect(fn () => ReturnRequest::createPendingForOrder($order, [
        'reason' => 'Second return',
        'refund_amount' => 50,
    ]))->toThrow(DomainException::class, 'This order already has an open return request.');
});

test('complete does not mark order refunded when payment is only partially refunded', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 1000,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 1000,
        'status' => PaymentTransactionStatus::Completed,
        'payment_method' => 'cod',
    ]);

    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Approved,
        'refund_amount' => 250,
    ]);

    $returnRequest->markReceived();
    $returnRequest->complete();

    $order->refresh();

    // Empty line items = full-order return → Returned; partial money must NOT force Refunded.
    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Completed)
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->status)->toBe(OrderStatus::Returned);
});

test('stacked partial refunds that cover the capture become fully refunded', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'grand_total' => 1000,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 1000,
        'status' => PaymentTransactionStatus::Completed,
        'payment_method' => 'cod',
    ]);

    $order->refundCompletedPayments(reason: 'RMA part 1', upToAmount: 400);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PartiallyRefunded);

    $order->refundCompletedPayments(reason: 'RMA part 2', upToAmount: 600);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Refunded);
});
