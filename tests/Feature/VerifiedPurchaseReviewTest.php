<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    Review::query()->delete();
    DB::table('order_items')->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(StorefrontSeeder::class);
});

test('buyer with fulfilled order gets verified purchase flag on review', function () {
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $variant = $product->defaultVariant;
    $customer = Customer::factory()->create([
        'phone' => '01710001122',
        'email' => 'buyer@example.com',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'status' => OrderStatus::Delivered,
    ]);

    OrderItem::factory()->forVariant($variant)->create([
        'order_id' => $order->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('product.review.submit', $product->slug), [
            'rating' => 5,
            'title' => 'Great shirt',
            'body' => 'Soft fabric and good fit after delivery.',
            'reviewer_name' => $customer->name,
            'reviewer_email' => $customer->email,
        ])
        ->assertRedirect();

    $review = Review::query()->where('product_id', $product->id)->latest('id')->first();

    expect($review)->not->toBeNull()
        ->and($review->is_verified_purchase)->toBeTrue();
});

test('product page only marks verified purchase reviews', function () {
    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
        'is_approved' => true,
        'is_verified_purchase' => false,
        'rating' => 4,
        'title' => 'Nice',
        'body' => 'Looks good.',
    ]);

    Review::factory()->verified()->create([
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 5,
        'title' => 'Verified buyer',
        'body' => 'Bought and loved it.',
    ]);

    $this->get(route('product.show', $product->slug))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('ProductDetail', false)
            ->where('product.verified_reviews_count', 1)
            ->has('product.reviews', 2)
            ->where('product.reviews', fn ($reviews) => collect($reviews)->contains(
                fn ($review) => $review['is_verified_purchase'] === true
            ) && collect($reviews)->contains(
                fn ($review) => $review['is_verified_purchase'] === false
            ))
        );
});
