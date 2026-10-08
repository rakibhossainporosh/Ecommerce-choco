<?php

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

test('reason is trimmed', function () {
    $order = Order::factory()->create(['grand_total' => 100]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'reason' => '   Item is broken.    ',
        'refund_amount' => 50,
    ]);

    expect($returnRequest->reason)->toBe('Item is broken.');
});
