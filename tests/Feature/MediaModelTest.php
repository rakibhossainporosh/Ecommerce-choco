<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    Schema::disableForeignKeyConstraints();
    Media::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

function createTestProduct(): Product
{
    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    return Product::createWithDefaultVariant(
        productAttributes: [
            'name' => 'Artisanal Dark Chocolate',
            'slug' => 'artisanal-dark-chocolate',
            'is_active' => false,
        ],
        variantAttributes: [
            'unit_id' => $unit->id,
            'sku' => 'SKU-MEDIA-001',
            'cost_price' => '40.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ],
        categoryIds: [$category->id]
    );
}

test('1. Media belongs to Product through morphTo', function () {
    $product = createTestProduct();

    $media = Media::factory()->forProduct($product)->create([
        'path' => 'products/dark-choc-front.jpg',
        'original_name' => 'dark-choc-front.jpg',
    ]);

    expect($media->mediable)->toBeInstanceOf(Product::class)
        ->and($media->mediable->id)->toBe($product->id)
        ->and($media->mediable())->toBeInstanceOf(MorphTo::class)
        ->and($media->mediable_type)->toBe($product->getMorphClass())
        ->and($media->mediable_id)->toBe($product->id);
});

test('2. Product has many Media through morphMany', function () {
    $product = createTestProduct();

    expect($product->media())->toBeInstanceOf(MorphMany::class);

    $media1 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/img-1.jpg',
        'original_name' => 'img-1.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 102400,
        'width' => 800,
        'height' => 800,
        'alt_text' => 'Product Front View',
        'sort_order' => 1,
        'is_primary' => true,
    ]);

    $media2 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/img-2.jpg',
        'original_name' => 'img-2.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 204800,
        'width' => 1200,
        'height' => 1200,
        'alt_text' => 'Product Back View',
        'sort_order' => 2,
        'is_primary' => false,
    ]);

    $freshProduct = $product->fresh();
    expect($freshProduct->media)->toHaveCount(2)
        ->and($freshProduct->media->pluck('id')->all())->toEqualCanonicalizing([$media1->id, $media2->id]);
});

test('3. Media belongs to ProductVariant through morphTo', function () {
    $product = createTestProduct();
    $variant = $product->defaultVariant;

    $media = Media::factory()->forVariant($variant)->create([
        'path' => 'variants/variant-front.jpg',
        'original_name' => 'variant-front.jpg',
    ]);

    expect($media->mediable)->toBeInstanceOf(ProductVariant::class)
        ->and($media->mediable->id)->toBe($variant->id)
        ->and($media->mediable())->toBeInstanceOf(MorphTo::class)
        ->and($media->mediable_type)->toBe($variant->getMorphClass())
        ->and($media->mediable_id)->toBe($variant->id);
});

test('4. ProductVariant has many Media through morphMany', function () {
    $product = createTestProduct();
    $variant = $product->defaultVariant;

    expect($variant->media())->toBeInstanceOf(MorphMany::class);

    $media = $variant->media()->create([
        'disk' => 'public',
        'path' => 'variants/sku-media-001.jpg',
        'original_name' => 'sku-media-001.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 500000,
        'width' => 1000,
        'height' => 1000,
        'alt_text' => '100g Bar Wrapper',
        'sort_order' => 0,
        'is_primary' => true,
    ]);

    $freshVariant = $variant->fresh();
    expect($freshVariant->media)->toHaveCount(1)
        ->and($freshVariant->media->first()->id)->toBe($media->id);
});

test('5. Media can exist for both Product and ProductVariant simultaneously', function () {
    $product = createTestProduct();
    $variant = $product->defaultVariant;

    $productMedia = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/choc.jpg',
        'sort_order' => 0,
    ]);

    $variantMedia = $variant->media()->create([
        'disk' => 'public',
        'path' => 'variants/choc-bar.jpg',
        'sort_order' => 0,
    ]);

    expect(Media::count())->toBe(2)
        ->and($productMedia->fresh()->mediable)->toBeInstanceOf(Product::class)
        ->and($variantMedia->fresh()->mediable)->toBeInstanceOf(ProductVariant::class)
        ->and($product->fresh()->media)->toHaveCount(1)
        ->and($variant->fresh()->media)->toHaveCount(1);
});

test('6. Multiple media records can belong to the same Product', function () {
    $product = createTestProduct();

    Media::factory()->count(4)->forProduct($product)->sequence(
        ['sort_order' => 1, 'is_primary' => true],
        ['sort_order' => 2, 'is_primary' => false],
        ['sort_order' => 3, 'is_primary' => false],
        ['sort_order' => 4, 'is_primary' => false],
    )->create();

    expect($product->fresh()->media()->count())->toBe(4);
});

test('7. Multiple media records can belong to the same ProductVariant', function () {
    $product = createTestProduct();
    $variant = $product->defaultVariant;

    Media::factory()->count(3)->forVariant($variant)->sequence(
        ['sort_order' => 0, 'is_primary' => true],
        ['sort_order' => 1, 'is_primary' => false],
        ['sort_order' => 2, 'is_primary' => false],
    )->create();

    expect($variant->fresh()->media()->count())->toBe(3);
});

test('8. sort_order and is_primary are persisted and cast correctly', function () {
    $product = createTestProduct();

    $media = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/cast-test.jpg',
        'sort_order' => '15',
        'is_primary' => 1,
    ]);

    $fresh = $media->fresh();
    expect($fresh->sort_order)->toBe(15)
        ->and($fresh->sort_order)->toBeInt()
        ->and($fresh->is_primary)->toBe(true)
        ->and($fresh->is_primary)->toBeBool();

    $mediaInactive = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/cast-test-2.jpg',
        'sort_order' => 0,
        'is_primary' => 0,
    ]);

    expect($mediaInactive->fresh()->is_primary)->toBeFalse()
        ->and($mediaInactive->fresh()->is_primary)->toBeBool();
});

test('9. nullable metadata fields work correctly', function () {
    $product = createTestProduct();

    // All nullable fields explicitly null
    $media = $product->media()->create([
        'disk' => 's3',
        'path' => 'products/minimal.jpg',
        'original_name' => null,
        'mime_type' => null,
        'size' => null,
        'width' => null,
        'height' => null,
        'alt_text' => null,
    ]);

    $fresh = $media->fresh();
    expect($fresh->disk)->toBe('s3')
        ->and($fresh->path)->toBe('products/minimal.jpg')
        ->and($fresh->original_name)->toBeNull()
        ->and($fresh->mime_type)->toBeNull()
        ->and($fresh->size)->toBeNull()
        ->and($fresh->width)->toBeNull()
        ->and($fresh->height)->toBeNull()
        ->and($fresh->alt_text)->toBeNull()
        ->and($fresh->sort_order)->toBe(0)
        ->and($fresh->is_primary)->toBe(false);

    // Populated values with integer casts
    $media->update([
        'original_name' => 'photo.png',
        'mime_type' => 'image/png',
        'size' => '843200',
        'width' => '1920',
        'height' => '1080',
        'alt_text' => 'Rich Swiss Chocolate',
    ]);

    $updated = $media->fresh();
    expect($updated->original_name)->toBe('photo.png')
        ->and($updated->mime_type)->toBe('image/png')
        ->and($updated->size)->toBe(843200)
        ->and($updated->size)->toBeInt()
        ->and($updated->width)->toBe(1920)
        ->and($updated->width)->toBeInt()
        ->and($updated->height)->toBe(1080)
        ->and($updated->height)->toBeInt()
        ->and($updated->alt_text)->toBe('Rich Swiss Chocolate');
});

test('10. media table does not use soft deletes and hard deletes records', function () {
    $product = createTestProduct();

    // 1. deleted_at column does not exist on media table
    expect(Schema::hasColumn('media', 'deleted_at'))->toBeFalse();

    $media = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/delete-test.jpg',
    ]);

    expect(Media::where('id', $media->id)->exists())->toBeTrue();

    // 2. Calling delete performs a hard DELETE in the database
    $media->delete();

    expect(Media::where('id', $media->id)->exists())->toBeFalse()
        ->and(DB::table('media')->where('id', $media->id)->exists())->toBeFalse();

    // 3. Soft-deleting parent Product does not delete media records
    $mediaKeep = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/keep-after-soft-delete.jpg',
    ]);

    $product->delete();
    expect($product->trashed())->toBeTrue();

    // Media row remains in database
    expect(Media::where('id', $mediaKeep->id)->exists())->toBeTrue()
        ->and(DB::table('media')->where('id', $mediaKeep->id)->exists())->toBeTrue();
});

test('11. expected composite indexes exist on media table', function () {
    $indexes = collect(Schema::getIndexes('media'));

    // Check composite index on [mediable_type, mediable_id]
    $typeIdIndex = $indexes->first(function ($index) {
        return $index['columns'] === ['mediable_type', 'mediable_id'];
    });
    expect($typeIdIndex)->not->toBeNull();

    // Check composite index on [mediable_type, mediable_id, sort_order]
    $typeIdSortIndex = $indexes->first(function ($index) {
        return $index['columns'] === ['mediable_type', 'mediable_id', 'sort_order'];
    });
    expect($typeIdSortIndex)->not->toBeNull();
});

test('12. Product and ProductVariant existing relationships and behavior remain unaffected by media relationship', function () {
    $product = createTestProduct();
    $variant = $product->defaultVariant;

    // Media added to both
    $product->media()->create(['disk' => 'public', 'path' => 'products/p1.jpg']);
    $variant->media()->create(['disk' => 'public', 'path' => 'variants/v1.jpg']);

    // Standard product relationships continue to function
    expect($product->defaultVariant->id)->toBe($variant->id)
        ->and($product->variants()->count())->toBe(1)
        ->and($product->categories()->count())->toBe(1)
        ->and($variant->product->id)->toBe($product->id)
        ->and($variant->unit)->not->toBeNull();
});
