<?php

use App\Models\Brand;
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
    Brand::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(StorefrontSeeder::class);
});

test('catalog exposes brands and filters by brand price and stock', function () {
    $brand = Brand::factory()->create([
        'name' => 'Facet Brand',
        'slug' => 'facet-brand',
        'is_active' => true,
    ]);

    $product = Product::where('slug', 'cotton-shirt')->firstOrFail();
    $product->update(['brand_id' => $brand->id]);
    $product->defaultVariant->update(['selling_price' => 850]);
    $product->defaultVariant->inventory->update(['quantity' => 5]);

    $outOfStock = Product::where('slug', 'classic-watch')->firstOrFail();
    $outOfStock->defaultVariant->update(['selling_price' => 1200]);
    $outOfStock->defaultVariant->inventory->update(['quantity' => 0]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->has('brands')
            ->where('filters.brand', '')
        );

    $this->get('/?brand=facet-brand&min_price=800&max_price=900&in_stock=1')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->where('filters.brand', 'facet-brand')
            ->where('filters.min_price', 800)
            ->where('filters.max_price', 900)
            ->where('filters.in_stock', true)
            ->has('products', 1)
            ->where('products.0.slug', 'cotton-shirt')
        );

    $this->get('/?in_stock=1')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home', false)
            ->where('filters.in_stock', true)
            ->where('products', fn ($products) => collect($products)->every(
                fn ($product) => $product['slug'] !== 'classic-watch'
            ))
        );
});
