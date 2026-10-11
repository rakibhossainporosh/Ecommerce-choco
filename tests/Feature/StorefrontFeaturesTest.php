<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Inventory::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    Review::truncate();
    Order::truncate();
    Customer::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->seed(StorefrontSeeder::class);
});

test('product detail page returns product data with stock, brand and rating info', function () {
    $response = $this->get('/product/cotton-shirt');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('ProductDetail', false)
        ->has('product', fn (Assert $product) => $product
            ->where('slug', 'cotton-shirt')
            ->where('name', 'কটন শার্ট')
            ->has('stock_quantity')
            ->has('is_in_stock')
            ->has('rating')
            ->has('reviews_count')
            ->has('images')
            ->etc()
        )
    );
});

test('user can submit a review for a product', function () {
    $product = Product::where('slug', 'cotton-shirt')->first();

    $response = $this->post("/product/{$product->slug}/reviews", [
        'rating' => 5,
        'reviewer_name' => 'তানভীর আহমেদ',
        'title' => 'খুব ভালো কোয়ালিটি',
        'body' => 'কাপড়টি সত্যিই আরামদায়ক এবং কালারও খুব চমৎকার।',
    ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'rating' => 5,
        'body' => 'কাপড়টি সত্যিই আরামদায়ক এবং কালারও খুব চমৎকার।',
        'is_approved' => true,
    ]);
});

test('contact form submits successfully', function () {
    $response = $this->post('/contact', [
        'name' => 'রাকিবুল হাসান',
        'email' => 'rakib@example.com',
        'phone' => '01700000000',
        'subject' => 'ডেলিভারি সংক্রান্ত প্রশ্ন',
        'message' => 'আমার পার্সেলটি কবে নাগাদ পৌঁছাবে জানতে চাই।',
    ]);

    $response->assertSessionHas('success');
});

test('checkout fails when requested product is out of stock', function () {
    $product = Product::where('slug', 'cotton-shirt')->first();
    $variant = $product->defaultVariant;

    // Set inventory to 2
    $variant->inventory->update(['quantity' => 2]);

    $response = $this->post('/checkout', [
        'customer_name' => 'আরিফ হোসেন',
        'customer_phone' => '01811111111',
        'shipping_address' => 'মিরপুর ১০, ঢাকা',
        'shipping_city' => 'ঢাকা',
        'shipping_method' => 'inside_dhaka',
        'payment_method' => 'cod',
        'items' => [
            [
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 5, // Requesting more than available 2
                'price' => $variant->selling_price,
            ],
        ],
    ]);

    $response->assertSessionHasErrors(['stock']);
    $this->assertDatabaseCount('orders', 0);
});

test('checkout succeeds when stock is available and creates customer record', function () {
    $product = Product::where('slug', 'cotton-shirt')->first();
    $variant = $product->defaultVariant;

    $variant->inventory->update(['quantity' => 10]);

    $response = $this->post('/checkout', [
        'customer_name' => 'আরিফ হোসেন',
        'customer_phone' => '01811111111',
        'shipping_address' => 'মিরপুর ১০, ঢাকা',
        'shipping_city' => 'ঢাকা',
        'shipping_method' => 'inside_dhaka',
        'payment_method' => 'cod',
        'items' => [
            [
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
                'price' => $variant->selling_price,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseHas('customers', [
        'phone' => '01811111111',
        'name' => 'আরিফ হোসেন',
    ]);
});
