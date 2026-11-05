<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('category_product')->delete();
    Inventory::query()->delete();
    ProductVariant::query()->delete();
    Product::query()->delete();
    Category::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(StorefrontSeeder::class);
});

test('home catalog includes pagination meta and first page products', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->has('products', 6)
            ->where('pagination.current_page', 1)
            ->where('pagination.per_page', 24)
            ->where('pagination.total', 6)
            ->where('filters.category', 'all')
        );
});

test('category path filters products and sets the category filter', function () {
    $this->get('/category/fashion')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->where('filters.category', 'fashion')
            ->has('products', 2)
            ->where('products.0.category_slug', 'fashion')
        );
});

test('legacy category query redirects permanently to the category path', function () {
    $this->get('/?category=fashion&sort=price_asc')
        ->assertRedirect('/category/fashion?sort=price_asc');
});

test('catalog page two returns the next product window', function () {
    $extra = Product::factory()->count(20)->create(['is_active' => true]);

    foreach ($extra as $index => $product) {
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_default' => true,
            'is_active' => true,
            'selling_price' => 100 + $index,
        ]);
    }

    expect(Product::query()->where('is_active', true)->count())->toBe(26);

    $this->get('/?page=2')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->where('pagination.current_page', 2)
            ->where('pagination.last_page', 2)
            ->where('pagination.total', 26)
            ->has('products', 2)
        );
});
