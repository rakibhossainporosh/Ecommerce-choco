<?php

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Review::query()->delete();
    CouponUsage::query()->delete();
    Coupon::query()->delete();
    DB::table('category_product')->delete();
    Inventory::query()->delete();
    ProductVariant::query()->delete();
    Product::query()->delete();
    Category::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(RolePermissionSeeder::class);
    $this->seed(StorefrontSeeder::class);
});

function hardeningCheckoutPayload(array $overrides = [], ?int $stockQuantity = 10): array
{
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;

    if ($stockQuantity !== null) {
        $variant->inventory->update(['quantity' => $stockQuantity]);
    }

    return array_merge([
        'customer_name' => 'আরিফ হোসেন',
        'customer_phone' => '01822222222',
        'customer_email' => 'arif-hard@example.com',
        'shipping_address_line' => 'মিরপুর ১০, ঢাকা',
        'shipping_city' => 'Dhaka',
        'shipping_amount' => 60,
        'payment_method' => 'cod',
        'cart_items' => [[
            'id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ], $overrides);
}

test('checkout rejects oversell when quantity exceeds inventory', function () {
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;

    $this->from('/checkout')
        ->post('/checkout', hardeningCheckoutPayload([
            'cart_items' => [[
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 5,
            ]],
        ], stockQuantity: 2))
        ->assertSessionHasErrors('stock');

    expect(Order::query()->count())->toBe(0)
        ->and($variant->inventory->fresh()->quantity)->toBe(2);
});

test('pending orders soft-hold stock so a second checkout cannot oversell', function () {
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;

    $this->post('/checkout', hardeningCheckoutPayload([
        'customer_phone' => '01833333333',
        'cart_items' => [[
            'id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 4,
        ]],
    ], stockQuantity: 5))->assertRedirect();

    expect(Order::query()->count())->toBe(1)
        ->and($variant->inventory->fresh()->quantity)->toBe(5)
        ->and(Order::query()->first()->status)->toBe(OrderStatus::Pending);

    $this->from('/checkout')
        ->post('/checkout', hardeningCheckoutPayload([
            'customer_phone' => '01844444444',
            'cart_items' => [[
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]],
        ], stockQuantity: null))
        ->assertSessionHasErrors('stock');

    expect(Order::query()->count())->toBe(1);
});

test('valid coupon is consumed once via consumeForOrder', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Fixed,
        'value' => 10,
        'minimum_order_amount' => 0,
        'maximum_discount_amount' => null,
        'usage_limit' => 5,
        'usage_limit_per_user' => null,
        'used_count' => 0,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDay(),
    ]);

    $this->post('/checkout', hardeningCheckoutPayload([
        'coupon_code' => 'save10',
    ]))->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and((float) $order->discount_amount)->toBe(10.0)
        ->and($coupon->fresh()->used_count)->toBe(1)
        ->and(CouponUsage::query()->where('coupon_id', $coupon->id)->where('order_id', $order->id)->count())->toBe(1);
});

test('expired coupon is rejected at checkout and apply endpoint', function () {
    Coupon::factory()->create([
        'code' => 'OLD50',
        'type' => CouponType::Fixed,
        'value' => 50,
        'minimum_order_amount' => 0,
        'maximum_discount_amount' => null,
        'usage_limit' => null,
        'usage_limit_per_user' => null,
        'used_count' => 0,
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'expires_at' => now()->subDay(),
    ]);

    $this->from('/checkout')
        ->post('/checkout', hardeningCheckoutPayload([
            'coupon_code' => 'OLD50',
        ]))
        ->assertSessionHasErrors('coupon_code');

    expect(Order::query()->count())->toBe(0);

    $this->postJson('/checkout/apply-coupon', [
        'code' => 'OLD50',
        'subtotal' => 500,
    ])->assertStatus(400);
});

test('coupon usage limit blocks further checkout consumption', function () {
    Coupon::factory()->create([
        'code' => 'ONCE',
        'type' => CouponType::Fixed,
        'value' => 20,
        'minimum_order_amount' => 0,
        'maximum_discount_amount' => null,
        'usage_limit' => 1,
        'usage_limit_per_user' => null,
        'used_count' => 1,
        'is_active' => true,
        'starts_at' => null,
        'expires_at' => null,
    ]);

    $this->from('/checkout')
        ->post('/checkout', hardeningCheckoutPayload([
            'coupon_code' => 'ONCE',
        ]))
        ->assertSessionHasErrors('coupon_code');

    expect(Order::query()->count())->toBe(0);
});

test('line totals use locked database selling price not client price', function () {
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;
    $variant->update(['selling_price' => 450]);
    $variant->inventory->update(['quantity' => 10]);

    $this->post('/checkout', hardeningCheckoutPayload([
        'cart_items' => [[
            'id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 1,
        ]],
    ]))->assertRedirect();

    $order = Order::query()->first();
    $item = $order->items()->first();

    expect((float) $item->unit_price)->toBe(450.0)
        ->and((float) $item->line_total)->toBe(900.0)
        ->and((float) $order->subtotal)->toBe(900.0);
});
