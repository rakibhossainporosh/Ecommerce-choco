<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
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
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Review::query()->delete();
    CustomerAddress::query()->delete();
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

function addressCheckoutPayload(array $overrides = []): array
{
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;
    $variant->inventory->update(['quantity' => 10]);

    return array_merge([
        'customer_name' => 'সাজিদ',
        'customer_phone' => '01710000001',
        'customer_email' => 'sajid@example.com',
        'shipping_address_line' => 'House 1, Road 2',
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

test('guest checkout still works without saved addresses', function () {
    $this->get('/checkout')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout', false)
            ->where('saved_addresses', [])
        );

    $this->post('/checkout', addressCheckoutPayload())
        ->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('logged-in customer can checkout with an owned saved address', function () {
    $customer = Customer::factory()->create([
        'phone' => '01710000002',
        'name' => 'মিতা',
        'is_active' => true,
    ]);

    $address = CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->id,
        'name' => 'মিতা',
        'phone' => '01710000002',
        'address_line' => 'Banani 11',
        'area' => 'Banani',
        'city' => 'Dhaka',
    ]);

    $this->actingAs($customer, 'customer')
        ->post('/checkout', addressCheckoutPayload([
            'customer_phone' => '01710000002',
            'customer_address_id' => $address->id,
            'shipping_address_line' => 'ignored',
            'shipping_city' => 'Outside Dhaka',
        ]))
        ->assertRedirect();

    $order = Order::query()->first();

    expect($order->shipping_address_line)->toContain('Banani 11')
        ->and($order->shipping_city)->toBe('Dhaka')
        ->and($order->customer_id)->toBe($customer->id);
});

test('customer cannot use another customers saved address', function () {
    $owner = Customer::factory()->create(['phone' => '01710000003', 'is_active' => true]);
    $intruder = Customer::factory()->create(['phone' => '01710000004', 'is_active' => true]);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $owner->id,
        'address_line' => 'Secret Road',
        'city' => 'Dhaka',
    ]);

    $this->actingAs($intruder, 'customer')
        ->from('/checkout')
        ->post('/checkout', addressCheckoutPayload([
            'customer_phone' => '01710000004',
            'customer_address_id' => $address->id,
        ]))
        ->assertSessionHasErrors('customer_address_id');

    expect(Order::query()->count())->toBe(0);
});

test('account address store is scoped to the authenticated customer', function () {
    $customer = Customer::factory()->create(['phone' => '01710000005', 'is_active' => true]);

    $this->actingAs($customer, 'customer')
        ->post('/account/addresses', [
            'name' => 'Home',
            'phone' => '01710000005',
            'address_line' => 'Mirpur 10',
            'area' => 'Mirpur',
            'city' => 'Dhaka',
            'is_default' => true,
        ])
        ->assertRedirect();

    expect(CustomerAddress::query()->where('customer_id', $customer->id)->count())->toBe(1);
});
