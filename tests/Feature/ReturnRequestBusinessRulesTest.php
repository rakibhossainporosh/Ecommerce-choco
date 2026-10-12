<?php

use App\Enums\OrderStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('refund amount cannot be negative', function () {
    $order = Order::factory()->create(['grand_total' => 100]);

    expect(fn () => ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => -10,
    ]))->toThrow(DomainException::class, 'Refund amount cannot be negative.');
});

test('refund amount cannot exceed order grand total', function () {
    $order = Order::factory()->create(['grand_total' => 100]);

    expect(fn () => ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 150,
    ]))->toThrow(DomainException::class, 'Refund amount cannot exceed the order grand total.');
});

test('cumulative refund amount across active return requests cannot exceed order grand total', function () {
    $order = Order::factory()->create(['grand_total' => 100]);

    ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 60,
        'status' => ReturnRequestStatus::Approved,
    ]);

    expect(fn () => ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 50,
        'status' => ReturnRequestStatus::Pending,
    ]))->toThrow(DomainException::class, 'Total refund amount across return requests cannot exceed the order grand total.');
});

test('rejected return request does not consume cumulative refund capacity', function () {
    $order = Order::factory()->create(['grand_total' => 100]);

    ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 60,
        'status' => ReturnRequestStatus::Rejected,
    ]);

    $second = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 60,
        'status' => ReturnRequestStatus::Pending,
    ]);

    expect($second->refund_amount)->toEqual(60);
});

test('cannot create return request for a cancelled order', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Cancelled,
        'grand_total' => 200,
    ]);

    expect(fn () => ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'refund_amount' => 100,
    ]))->toThrow(DomainException::class, 'Cannot create a return request for a cancelled order.');
});

test('completed return request cannot change status or refund amount', function () {
    $order = Order::factory()->create(['grand_total' => 200]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Completed,
        'refund_amount' => 100,
    ]);

    expect(function () use ($returnRequest) {
        $returnRequest->status = ReturnRequestStatus::Pending;
        $returnRequest->save();
    })->toThrow(DomainException::class, 'Completed return requests cannot change status.');

    $returnRequest->refresh();

    expect(function () use ($returnRequest) {
        $returnRequest->refund_amount = 150;
        $returnRequest->save();
    })->toThrow(DomainException::class, 'Refund amount cannot be modified once the return request is completed.');
});

test('rejected return request cannot change status', function () {
    $order = Order::factory()->create(['grand_total' => 200]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Rejected,
        'refund_amount' => 100,
    ]);

    expect(function () use ($returnRequest) {
        $returnRequest->status = ReturnRequestStatus::Approved;
        $returnRequest->save();
    })->toThrow(DomainException::class, 'Rejected return requests cannot change status.');
});

test('reason is trimmed', function () {
    $order = Order::factory()->create(['grand_total' => 100]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'reason' => '   Item is broken.    ',
        'refund_amount' => 50,
    ]);

    expect($returnRequest->reason)->toBe('Item is broken.');
});
