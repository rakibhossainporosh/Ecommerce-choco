<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
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
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->seed(StorefrontSeeder::class);
});

test('storefront search finds products by Bengali name', function () {
    $response = $this->get('/?search=ঘড়ি');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->where('filters.search', 'ঘড়ি')
        ->has('products', 1)
        ->where('products.0.name', 'ক্লাসিক ঘড়ি')
    );
});

test('storefront search finds products by English slug keywords', function () {
    $response = $this->get('/?search=shirt');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->where('filters.search', 'shirt')
        ->has('products', 1)
        ->where('products.0.slug', 'cotton-shirt')
    );
});

test('storefront search finds products by variant SKU', function () {
    $response = $this->get('/?search=NOBO-ELE-005');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->where('filters.search', 'NOBO-ELE-005')
        ->has('products', 1)
        ->where('products.0.name', 'হেডফোন ও ক্যামেরা')
    );
});

test('storefront search finds products by category name or slug', function () {
    $response = $this->get('/?search=fashion');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->where('filters.search', 'fashion')
        ->has('products', 2)
    );
});

test('inactive products are excluded from storefront search results', function () {
    $product = Product::where('slug', 'cotton-shirt')->first();
    $product->is_active = false;
    $product->saveQuietly();

    $response = $this->get('/?search=cotton');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->where('filters.search', 'cotton')
        ->has('products', 0)
    );
});

test('empty search returns all active products', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home', false)
        ->has('products', 6)
    );
});

test('search suggestions returns matching products with JSON structure', function () {
    $response = $this->getJson('/search/suggestions?q=ঘড়ি');

    $response->assertSuccessful();
    $response->assertJsonCount(1);
    $response->assertJsonFragment([
        'name' => 'ক্লাসিক ঘড়ি',
        'slug' => 'classic-watch',
        'category_name' => 'এক্সেসরিজ',
    ]);
});

test('search suggestions returns empty array for empty query', function () {
    $response = $this->getJson('/search/suggestions?q=');

    $response->assertSuccessful();
    $response->assertExactJson([]);
});

test('search suggestions excludes inactive products', function () {
    $product = Product::where('slug', 'cotton-shirt')->first();
    $product->is_active = false;
    $product->saveQuietly();

    $response = $this->getJson('/search/suggestions?q=cotton');

    $response->assertSuccessful();
    $response->assertExactJson([]);
});
