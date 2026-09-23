<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
});

test('1. Product can be created with explicit attributes', function () {
    $brand = Brand::factory()->create();

    $product = Product::create([
        'name' => 'Single Origin Dark Chocolate 70%',
        'slug' => 'single-origin-dark-chocolate-70',
        'short_description' => 'Rich single-origin bar from Madagascar.',
        'description' => 'Detailed notes of raspberry and citrus with dark cacao.',
        'meta_title' => 'Single Origin Dark Chocolate 70% | Madagascar',
        'meta_description' => 'Buy pure dark chocolate made with single-origin beans.',
        'is_active' => true,
        'is_featured' => true,
        'brand_id' => $brand->id,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBeGreaterThan(0)
        ->and($product->name)->toBe('Single Origin Dark Chocolate 70%')
        ->and($product->slug)->toBe('single-origin-dark-chocolate-70')
        ->and($product->short_description)->toBe('Rich single-origin bar from Madagascar.')
        ->and($product->is_active)->toBeTrue()
        ->and($product->is_featured)->toBeTrue()
        ->and($product->brand_id)->toBe($brand->id);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'slug' => 'single-origin-dark-chocolate-70',
    ]);
});

test('2. Product defaults: is_active defaults to true and is_featured defaults to false', function () {
    $product = Product::create([
        'name' => 'Classic Milk Chocolate',
        'slug' => 'classic-milk-chocolate',
    ]);

    $product->refresh();

    expect($product->is_active)->toBeTrue()
        ->and($product->is_featured)->toBeFalse()
        ->and($product->brand_id)->toBeNull()
        ->and($product->short_description)->toBeNull()
        ->and($product->description)->toBeNull()
        ->and($product->meta_title)->toBeNull()
        ->and($product->meta_description)->toBeNull();
});

test('3. Casts correctly cast attributes to native types', function () {
    $brand = Brand::factory()->create();

    $product = Product::create([
        'name' => 'Hazelnut Praline',
        'slug' => 'hazelnut-praline',
        'is_active' => 1,
        'is_featured' => '1',
        'brand_id' => (string) $brand->id,
    ]);

    $product->refresh();

    expect($product->is_active)->toBeTrue()
        ->and($product->is_active)->toBeBool()
        ->and($product->is_featured)->toBeTrue()
        ->and($product->is_featured)->toBeBool()
        ->and($product->brand_id)->toBe($brand->id)
        ->and($product->brand_id)->toBeInt();
});

test('4. ProductFactory creates valid products', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeEmpty()
        ->and($product->slug)->not->toBeEmpty()
        ->and($product->is_active)->toBeTrue()
        ->and($product->is_featured)->toBeFalse();
});

test('5. ProductFactory active state works', function () {
    $product = Product::factory()->active()->create();
    expect($product->is_active)->toBeTrue();
});

test('6. ProductFactory inactive state works', function () {
    $product = Product::factory()->inactive()->create();
    expect($product->is_active)->toBeFalse();
});

test('7. ProductFactory featured state works', function () {
    $product = Product::factory()->featured()->create();
    expect($product->is_featured)->toBeTrue();
});

test('8. Soft delete marks product as deleted without removing from database', function () {
    $product = Product::factory()->create();

    $product->delete();

    expect($product->trashed())->toBeTrue();
    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('9. Default query excludes soft-deleted Product', function () {
    $product = Product::factory()->create(['slug' => 'to-be-deleted']);

    $product->delete();

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::where('slug', 'to-be-deleted')->first())->toBeNull();
});

test('10. withTrashed retrieves soft-deleted Product', function () {
    $product = Product::factory()->create();
    $product->delete();

    $found = Product::withTrashed()->find($product->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($product->id)
        ->and($found->trashed())->toBeTrue();
});

test('11. Soft-deleted Product can be restored', function () {
    $product = Product::factory()->create();
    $product->delete();
    expect($product->trashed())->toBeTrue();

    $product->restore();

    expect($product->fresh()->trashed())->toBeFalse();
    $this->assertNotSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('12. Brand relationship works', function () {
    $brand = Brand::factory()->create(['name' => 'Lindt']);
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($product->brand)->toBeInstanceOf(Brand::class)
        ->and($product->brand->id)->toBe($brand->id)
        ->and($product->brand->name)->toBe('Lindt');
});

test('13. Categories relationship works and multiple categories can be attached', function () {
    $category1 = Category::factory()->create(['name' => 'Dark Chocolate']);
    $category2 = Category::factory()->create(['name' => 'Vegan Treats']);

    $product = Product::factory()->create();
    $product->categories()->attach([$category1->id, $category2->id]);

    expect($product->categories)->toHaveCount(2)
        ->and($product->categories->pluck('id'))->toContain($category1->id, $category2->id);
});

test('14. Duplicate category pair in pivot is prevented by unique constraint', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();

    $product->categories()->attach($category->id);

    expect(function () use ($product, $category) {
        // Direct DB insert of duplicate pair throws QueryException
        DB::table('category_product')->insert([
            'product_id' => $product->id,
            'category_id' => $category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('15. Nullable brand works without issues', function () {
    $product = Product::factory()->create(['brand_id' => null]);

    expect($product->brand_id)->toBeNull()
        ->and($product->brand)->toBeNull();
});

test('16. Brand deletion sets product brand_id to null at database level', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($product->brand_id)->toBe($brand->id);

    // Force delete brand from DB to trigger foreign key nullOnDelete constraint
    $brand->forceDeleteQuietly();

    $product->refresh();
    expect($product->brand_id)->toBeNull();
});

test('17. Pivot rows are removed when Product is deleted from database', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    $this->assertDatabaseHas('category_product', [
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]);

    // Force delete product triggers cascadeOnDelete on pivot table
    $product->forceDelete();

    $this->assertDatabaseMissing('category_product', [
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]);
});

test('18. Soft-deleting a product leaves pivot rows intact in category_product', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    $product->delete();

    expect($product->trashed())->toBeTrue();

    // Pivot rows remain in category_product because soft delete does not trigger database DELETE
    $this->assertDatabaseHas('category_product', [
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]);
});

test('19. Restoring a soft-deleted product restores access to its categories', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    $product->delete();
    $product->restore();

    expect($product->fresh()->categories)->toHaveCount(1)
        ->and($product->fresh()->categories->first()->id)->toBe($category->id);
});
