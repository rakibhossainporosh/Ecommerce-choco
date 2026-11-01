<?php

use App\Enums\AbandonedCartStatus;
use App\Filament\Resources\AbandonedCarts\Pages\ListAbandonedCarts;
use App\Filament\Resources\AbandonedCarts\Pages\ViewAbandonedCart;
use App\Models\AbandonedCart;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AbandonedCartSettings;
use App\Support\OrderSmsSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    config([
        'services.sms.enabled' => true,
        'services.sms.driver' => 'log',
    ]);
});

test('a shop can see a cart that was left with a mobile number', function () {
    $variant = ProductVariant::factory()->create(['selling_price' => 150]);

    $this->postJson('/cart/remember', [
        'name' => 'Karim',
        'phone' => '01711111111',
        'email' => 'karim@example.com',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]],
    ])->assertOk();

    $cart = AbandonedCart::query()->first();

    expect($cart)->not->toBeNull()
        ->and($cart->name)->toBe('Karim')
        ->and($cart->phone)->toBe('01711111111')
        ->and((float) $cart->subtotal)->toBe(300.0)
        ->and($cart->status)->toBe(AbandonedCartStatus::Open);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(ListAbandonedCarts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$cart])
        ->assertSee('Karim');
});

test('the cart page lists the product that was left', function () {
    $variant = ProductVariant::factory()->create(['selling_price' => 150]);

    $this->postJson('/cart/remember', [
        'name' => 'Karim',
        'phone' => '01711111111',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]],
    ])->assertOk();

    $cart = AbandonedCart::query()->first();
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $storedName = (string) $cart->items[0]['name'];
    $visibleName = str_contains($storedName, ' - ') ? explode(' - ', $storedName, 2)[0] : $storedName;

    Livewire::test(ViewAbandonedCart::class, ['record' => $cart->id])
        ->assertSuccessful()
        ->assertSee($visibleName)
        ->assertSee('৳ 300.00')
        ->assertSee('Copy cart link')
        ->assertSee('Cart link copied.');

    Livewire::test(ListAbandonedCarts::class)
        ->assertSee('2');
});

test('a cart without a real mobile number is not saved', function () {
    $variant = ProductVariant::factory()->create();

    $this->postJson('/cart/remember', [
        'phone' => '12345',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ])->assertOk();

    expect(AbandonedCart::query()->count())->toBe(0);
});

test('an order closes the waiting cart', function () {
    $variant = ProductVariant::factory()->create(['selling_price' => 100]);

    $this->postJson('/cart/remember', [
        'name' => 'Karim',
        'phone' => '01711111111',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ])->assertOk();

    $this->post('/checkout', [
        'customer_name' => 'Karim',
        'customer_phone' => '01711111111',
        'shipping_address_line' => 'House 1, Dhaka',
        'shipping_city' => 'Dhaka',
        'shipping_amount' => 60,
        'cart_items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ])->assertRedirect();

    expect(AbandonedCart::query()->first()->status)->toBe(AbandonedCartStatus::Recovered);
});

test('a reminder is sent once after the cart has been left', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.greenweb.com.bd/*' => Http::response('Ok', 200),
    ]);

    OrderSmsSettings::save([
        'sms_enabled' => true,
        'sms_driver' => 'greenweb',
        'greenweb_token' => 'gw-secret-token',
        'phones' => [],
    ]);

    AbandonedCartSettings::save([
        'abandoned_cart_enabled' => true,
        'abandoned_cart_delay_minutes' => 15,
        'abandoned_cart_email_enabled' => false,
        'abandoned_cart_sms_enabled' => true,
        'abandoned_cart_sms_body' => 'Hi {name}, your {shop} cart is waiting. {url}',
    ]);

    $variant = ProductVariant::factory()->create();

    $this->postJson('/cart/remember', [
        'name' => 'Karim',
        'phone' => '01711111111',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ])->assertOk();

    $cart = AbandonedCart::query()->first();
    $cart->last_activity_at = now()->subMinutes(20);
    $cart->save();

    $this->artisan('carts:remind-abandoned')->assertSuccessful();
    $this->artisan('carts:remind-abandoned')->assertSuccessful();

    $cart->refresh();

    expect($cart->status)->toBe(AbandonedCartStatus::Reminded)
        ->and($cart->reminded_at)->not->toBeNull();

    Http::assertSentCount(1);
});

test('reminders stay off until the shop turns them on', function () {
    Http::preventStrayRequests();

    $variant = ProductVariant::factory()->create();

    $this->postJson('/cart/remember', [
        'name' => 'Karim',
        'phone' => '01711111111',
        'items' => [[
            'id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ]);

    AbandonedCart::query()->first()->update(['last_activity_at' => now()->subHour()]);

    $this->artisan('carts:remind-abandoned')->assertSuccessful();

    expect(AbandonedCart::query()->first()->status)->toBe(AbandonedCartStatus::Open);
    Http::assertNothingSent();
});
