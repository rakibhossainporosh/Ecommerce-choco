<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('percentage value cannot exceed 100', function () {
    expect(fn () => Coupon::factory()->create([
        'type' => CouponType::Percentage,
        'value' => 101,
    ]))->toThrow(DomainException::class, 'Percentage coupon value cannot exceed 100%.');
});

test('value cannot be negative', function () {
    expect(fn () => Coupon::factory()->create([
        'value' => -5,
    ]))->toThrow(DomainException::class, 'Coupon value cannot be negative.');
});

test('fixed value must be greater than zero', function () {
    expect(fn () => Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 0,
    ]))->toThrow(DomainException::class, 'Fixed coupon value must be greater than zero.');
});

test('starts_at cannot be after expires_at', function () {
    expect(fn () => Coupon::factory()->create([
        'starts_at' => now()->addDays(2),
        'expires_at' => now()->addDay(),
    ]))->toThrow(DomainException::class, 'Start date cannot be after expiry date.');
});

test('discount calculation for percentage', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Percentage,
        'value' => 10,
        'minimum_order_amount' => null,
        'maximum_discount_amount' => null,
    ]);
    expect($coupon->calculateDiscount(200))->toBe(20.0);
});

test('discount calculation for fixed', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 50,
        'minimum_order_amount' => null,
        'maximum_discount_amount' => null,
    ]);
    expect($coupon->calculateDiscount(200))->toBe(50.0);
});

test('discount is capped by maximum_discount_amount', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Percentage,
        'value' => 50,
        'maximum_discount_amount' => 30,
        'minimum_order_amount' => null,
    ]);
    expect($coupon->calculateDiscount(100))->toBe(30.0); // normally 50, capped to 30
});

test('discount is capped by subtotal', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 500,
        'minimum_order_amount' => null,
        'maximum_discount_amount' => null,
    ]);
    expect($coupon->calculateDiscount(200))->toBe(200.0);
});

test('minimum order amount must be met', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 50,
        'minimum_order_amount' => 100,
    ]);
    expect($coupon->calculateDiscount(99))->toBe(0.0)
        ->and($coupon->calculateDiscount(100))->toBe(50.0);
});

test('eligibility checks', function () {
    $coupon = Coupon::factory()->create([
        'is_active' => false,
        'minimum_order_amount' => null,
    ]);
    expect(fn () => $coupon->checkEligibility(100))->toThrow(DomainException::class, 'Coupon is inactive.');

    $coupon->update(['is_active' => true, 'starts_at' => now()->addDay()]);
    expect(fn () => $coupon->checkEligibility(100))->toThrow(DomainException::class, 'Coupon is not yet valid.');

    $coupon->update(['starts_at' => now()->subDay(), 'expires_at' => now()->subHour()]);
    expect(fn () => $coupon->checkEligibility(100))->toThrow(DomainException::class, 'Coupon has expired.');
});

test('usage limit checks', function () {
    $coupon = Coupon::factory()->create([
        'usage_limit' => 1,
        'used_count' => 1,
        'minimum_order_amount' => null,
    ]);
    expect(fn () => $coupon->checkEligibility(100))->toThrow(DomainException::class, 'Coupon usage limit has been reached.');
});

test('per user usage limit checks', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'usage_limit_per_user' => 1,
        'minimum_order_amount' => null,
    ]);

    $order = Order::factory()->create(['user_id' => $user->id, 'subtotal' => 100]);
    $coupon->consumeForOrder($order);

    expect(fn () => $coupon->checkEligibility(100, $user->id))->toThrow(DomainException::class, 'You have reached the usage limit for this coupon.');
});

test('consumeForOrder records usage and increments count', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 20,
        'minimum_order_amount' => null,
    ]);
    $order = Order::factory()->create(['subtotal' => 100]);

    $usage = $coupon->consumeForOrder($order);

    expect($usage->discount_amount)->toBe('20.00')
        ->and($usage->coupon_id)->toBe($coupon->id)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('consumeForOrder rejects 0 discount', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Fixed,
        'value' => 20,
        'minimum_order_amount' => 200,
    ]);
    $order = Order::factory()->create(['subtotal' => 100]);

    expect(fn () => $coupon->consumeForOrder($order))->toThrow(DomainException::class, 'Order subtotal does not meet the minimum amount for this coupon.');
});
