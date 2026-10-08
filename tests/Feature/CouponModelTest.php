<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('coupon can be created', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'SUMMER2026',
        'type' => CouponType::Percentage,
        'value' => 20,
    ]);

    expect($coupon)
        ->code->toBe('SUMMER2026')
        ->type->toBe(CouponType::Percentage)
        ->value->toBe('20.00');
});

test('coupon code is upper cased', function () {
    $coupon = Coupon::factory()->create([
        'code' => ' lowercase_code ',
    ]);

    expect($coupon->code)->toBe('LOWERCASE_CODE');
});

test('coupon has usages relationship', function () {
    $coupon = Coupon::factory()->create();
    expect($coupon->usages())->toBeInstanceOf(HasMany::class);
});
