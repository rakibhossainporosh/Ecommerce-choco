<?php

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('return request can be created', function () {
    $order = Order::factory()->create(['grand_total' => 100]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Pending,
        'reason' => 'Defective item',
        'refund_amount' => 50,
    ]);

    expect($returnRequest)
        ->toBeInstanceOf(ReturnRequest::class)
        ->status->toBe(ReturnRequestStatus::Pending)
        ->reason->toBe('Defective item')
        ->refund_amount->toEqual(50)
        ->order_id->toBeInt()
        ->user_id->toBeInt();
});

test('return request belongs to order and user', function () {
    $returnRequest = ReturnRequest::factory()->create();

    expect($returnRequest->order)->toBeInstanceOf(Order::class)
        ->and($returnRequest->user)->toBeInstanceOf(User::class);
});
