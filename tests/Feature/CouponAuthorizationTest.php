<?php

use App\Models\CouponUsage;
use App\Models\User;
use App\Policies\CouponPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->manager = User::factory()->create();
    $this->manager->assignRole('Manager');

    $this->staff = User::factory()->create();
    $this->staff->assignRole('Staff');
});

test('admin has full access to coupons', function () {
    expect($this->admin->can('coupons.view'))->toBeTrue()
        ->and($this->admin->can('coupons.create'))->toBeTrue()
        ->and($this->admin->can('coupons.update'))->toBeTrue()
        ->and($this->admin->can('coupons.delete'))->toBeTrue()
        ->and($this->admin->can('coupon-usages.view'))->toBeTrue();
});

test('manager has limited access to coupons', function () {
    expect($this->manager->can('coupons.view'))->toBeTrue()
        ->and($this->manager->can('coupons.create'))->toBeTrue()
        ->and($this->manager->can('coupons.update'))->toBeTrue()
        ->and($this->manager->can('coupons.delete'))->toBeFalse()
        ->and($this->manager->can('coupon-usages.view'))->toBeTrue();
});

test('staff has view only access to coupons', function () {
    expect($this->staff->can('coupons.view'))->toBeTrue()
        ->and($this->staff->can('coupons.create'))->toBeFalse()
        ->and($this->staff->can('coupons.update'))->toBeFalse()
        ->and($this->staff->can('coupons.delete'))->toBeFalse()
        ->and($this->staff->can('coupon-usages.view'))->toBeTrue();
});

test('coupon policy prevents deletion if usage exists', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('coupons.delete');

    $usage = CouponUsage::factory()->create();
    $coupon = $usage->coupon;

    expect((new CouponPolicy)->delete($user, $coupon))->toBeFalse();

    $usage->delete();

    expect((new CouponPolicy)->delete($user, $coupon))->toBeTrue();
});
