<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    Schema::disableForeignKeyConstraints();
    Product::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

test('Scenario 1: direct $product->categories()->detach() removes all categories and leaves Product with zero categories', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect($product->categories()->count())->toBe(1);

    // Direct Eloquent relationship operation
    $product->categories()->detach();

    expect($product->categories()->count())->toBe(0)
        ->and(DB::table('category_product')->where('product_id', $product->id)->count())->toBe(0);
});

test('Scenario 2: direct $product->categories()->sync([]) removes all categories and leaves Product with zero categories', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect($product->categories()->count())->toBe(1);

    // Direct Eloquent sync([]) operation
    $product->categories()->sync([]);

    expect($product->categories()->count())->toBe(0)
        ->and(DB::table('category_product')->where('product_id', $product->id)->count())->toBe(0);
});

test('Scenario 3: direct $product->categories()->attach($inactiveCategoryId) directly attaches an inactive Category', function () {
    $validCategory = Category::factory()->create(['is_active' => true]);
    $inactiveCategory = Category::factory()->inactive()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($validCategory->id);

    // Direct Eloquent attach of inactive category
    $product->categories()->attach($inactiveCategory->id);

    expect($product->categories()->where('categories.id', $inactiveCategory->id)->exists())->toBeTrue()
        ->and(DB::table('category_product')->where('product_id', $product->id)->where('category_id', $inactiveCategory->id)->exists())->toBeTrue();
});

test('Scenario 4: direct $product->categories()->attach($trashedCategoryId) inserts pivot row for soft-deleted Category', function () {
    $validCategory = Category::factory()->create(['is_active' => true]);
    $trashedCategory = Category::factory()->create(['is_active' => true]);
    $trashedCategory->delete();
    $product = Product::factory()->create();
    $product->categories()->attach($validCategory->id);

    // Direct Eloquent attach of soft-deleted category succeeds at DB level
    $product->categories()->attach($trashedCategory->id);

    // Pivot row was created in database
    expect(DB::table('category_product')->where('product_id', $product->id)->where('category_id', $trashedCategory->id)->exists())->toBeTrue()
        // But model query filters out trashed category due to SoftDeletingScope on Category
        ->and($product->categories()->where('categories.id', $trashedCategory->id)->exists())->toBeFalse();
});

test('Scenario 5: direct $product->categories()->sync([$valid, $inactive]) creates invalid relationship state without calling validateCategoryAssignment()', function () {
    $validCategory = Category::factory()->create(['is_active' => true]);
    $inactiveCategory = Category::factory()->inactive()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($validCategory->id);

    // Direct Eloquent sync with mixed valid and inactive category IDs
    $product->categories()->sync([$validCategory->id, $inactiveCategory->id]);

    expect(DB::table('category_product')->where('product_id', $product->id)->count())->toBe(2)
        ->and($product->categories()->count())->toBe(2)
        ->and($product->categories()->where('categories.id', $inactiveCategory->id)->exists())->toBeTrue();
});
