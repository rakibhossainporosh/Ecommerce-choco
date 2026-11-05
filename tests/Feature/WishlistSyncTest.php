<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\WishlistItem;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    WishlistItem::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(StorefrontSeeder::class);
});

test('guest cannot sync wishlist', function () {
    $this->postJson(route('customer.wishlist.sync'), [
        'product_ids' => [1],
    ])->assertUnauthorized();
});

test('customer can toggle and sync wishlist across sessions', function () {
    $customer = Customer::factory()->create(['is_active' => true]);
    $shirt = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $watch = Product::where('slug', 'classic-watch')->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.wishlist.toggle'), [
            'product_id' => $shirt->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('in_wishlist', true)
        ->assertJsonCount(1, 'items');

    $this->assertDatabaseHas('wishlist_items', [
        'customer_id' => $customer->id,
        'product_id' => $shirt->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.wishlist.sync'), [
            'product_ids' => [$watch->id],
        ])
        ->assertSuccessful()
        ->assertJsonCount(2, 'items');

    $this->assertDatabaseHas('wishlist_items', [
        'customer_id' => $customer->id,
        'product_id' => $watch->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->getJson(route('customer.wishlist.index'))
        ->assertSuccessful()
        ->assertJsonCount(2, 'items');
});
