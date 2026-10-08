<?php

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('coupon usage can be created', function () {
    $usage = CouponUsage::factory()->create();

    expect($usage)
        ->toBeInstanceOf(CouponUsage::class)
        ->coupon_id->toBeInt()
        ->order_id->toBeInt()
        ->discount_amount->toBeString();
});

test('coupon usage belongs to coupon, order, and user', function () {
    $usage = CouponUsage::factory()->create();

    expect($usage->coupon)->toBeInstanceOf(Coupon::class)
        ->and($usage->order)->toBeInstanceOf(Order::class)
        ->and($usage->user)->toBeInstanceOf(User::class);
});

test('order can only use a specific coupon once via unique constraint', function () {
    $coupon = Coupon::factory()->create();
    $order = Order::factory()->create();

    CouponUsage::factory()->create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
    ]);

    expect(fn () => CouponUsage::factory()->create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
    ]))->toThrow(QueryException::class);
});
