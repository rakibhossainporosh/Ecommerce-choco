<?php

use App\Enums\CouponType;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('can list coupons', function () {
    Coupon::factory(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(ListCoupons::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Coupon::all());
});

test('can create coupon', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'TESTCOUPON10',
            'name' => 'Test Coupon 10',
            'type' => CouponType::Fixed->value,
            'value' => 50.00,
            'minimum_order_amount' => null,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('coupons', [
        'code' => 'TESTCOUPON10',
        'type' => CouponType::Fixed->value,
        'value' => '50.00',
    ]);
});

test('can edit coupon', function () {
    $coupon = Coupon::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(EditCoupon::class, ['record' => $coupon->id])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($coupon->fresh()->name)->toBe('Updated Name');
});

test('can validate unique code', function () {
    $coupon = Coupon::factory()->create(['code' => 'EXISTING']);

    $this->actingAs($this->admin);

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'EXISTING',
            'name' => 'Test',
            'type' => CouponType::Fixed->value,
            'value' => 50,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});
