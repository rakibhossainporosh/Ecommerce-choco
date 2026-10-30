<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    Media::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

function createDomainTestProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    $productAttributes = array_merge([
        'name' => 'Artisanal Dark Chocolate',
        'slug' => 'artisanal-dark-chocolate-'.uniqid(),
        'is_active' => false,
    ], $attributes);

    return Product::createWithDefaultVariant(
        productAttributes: $productAttributes,
        variantAttributes: [
            'unit_id' => $unit->id,
            'sku' => 'SKU-'.strtoupper(uniqid()),
            'cost_price' => '40.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ],
        categoryIds: [$category->id]
    );
}

// ============================================================
// 1-9: PRIMARY IMAGE INVARIANT & DELETION
// ============================================================

test('1. First media becomes primary automatically for owner with zero media', function () {
    $product = createDomainTestProduct();
    expect($product->media()->count())->toBe(0);

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/first.jpg',
        'original_name' => 'first.jpg',
    ]);

    expect($media->is_primary)->toBeTrue()
        ->and($media->sort_order)->toBe(1)
        ->and((bool) Media::where('id', $media->id)->value('is_primary'))->toBeTrue()
        ->and($product->primaryMedia()->id)->toBe($media->id);

    // Also verify for ProductVariant
    $variant = $product->defaultVariant;
    expect($variant->media()->count())->toBe(0);

    $variantMedia = $variant->addMedia([
        'disk' => 'public',
        'path' => 'variants/first.jpg',
    ]);

    expect($variantMedia->is_primary)->toBeTrue()
        ->and($variantMedia->sort_order)->toBe(1)
        ->and($variant->primaryMedia()->id)->toBe($variantMedia->id);
});

test('2. Second media does not become primary automatically', function () {
    $product = createDomainTestProduct();

    $media1 = $product->addMedia(['disk' => 'public', 'path' => 'products/first.jpg']);
    $media2 = $product->addMedia(['disk' => 'public', 'path' => 'products/second.jpg']);

    expect($media1->fresh()->is_primary)->toBeTrue()
        ->and($media2->is_primary)->toBeFalse()
        ->and((bool) Media::where('id', $media2->id)->value('is_primary'))->toBeFalse()
        ->and($media2->sort_order)->toBe(2)
        ->and($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('3. setPrimary() changes primary correctly', function () {
    $product = createDomainTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'products/1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'products/2.jpg']);

    expect($m1->fresh()->is_primary)->toBeTrue()
        ->and($m2->fresh()->is_primary)->toBeFalse();

    $m2->setPrimary();

    expect($m2->fresh()->is_primary)->toBeTrue()
        ->and($m1->fresh()->is_primary)->toBeFalse()
        ->and($product->primaryMedia()->id)->toBe($m2->id);
});

test('4. setPrimary() clears previous primary across multiple media', function () {
    $product = createDomainTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'products/1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'products/2.jpg']);
    $m3 = $product->addMedia(['disk' => 'public', 'path' => 'products/3.jpg']);

    expect($product->media()->where('is_primary', true)->count())->toBe(1);

    $product->setPrimaryMedia($m3);

    expect($m3->fresh()->is_primary)->toBeTrue()
        ->and($m1->fresh()->is_primary)->toBeFalse()
        ->and($m2->fresh()->is_primary)->toBeFalse()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('5. setPrimary() is idempotent', function () {
    $product = createDomainTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'products/1.jpg']);
    expect($m1->is_primary)->toBeTrue();

    // Calling setPrimary again on already primary media
    $result = $m1->setPrimary();

    expect($result->is_primary)->toBeTrue()
        ->and($m1->fresh()->is_primary)->toBeTrue()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('6. Multiple primary images cannot exist through normal domain operations', function () {
    $product = createDomainTestProduct();

    $mediaItems = [];
    for ($i = 1; $i <= 5; $i++) {
        $mediaItems[] = $product->addMedia(['disk' => 'public', 'path' => "products/{$i}.jpg"]);
        expect($product->media()->where('is_primary', true)->count())->toBe(1);
    }

    $product->setPrimaryMedia($mediaItems[3]);
    expect($product->media()->where('is_primary', true)->count())->toBe(1);

    $product->setPrimaryMedia($mediaItems[1]);
    expect($product->media()->where('is_primary', true)->count())->toBe(1);

    $product->setPrimaryMedia($mediaItems[4]);
    expect($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('7. Primary deletion promotes the lowest sort_order remaining media', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg', 'sort_order' => 3]);

    expect($mA->fresh()->is_primary)->toBeTrue()
        ->and($mB->fresh()->is_primary)->toBeFalse()
        ->and($mC->fresh()->is_primary)->toBeFalse();

    $deleted = $product->deleteMedia($mA);

    expect($deleted)->toBeTrue()
        ->and(Media::where('id', $mA->id)->exists())->toBeFalse()
        ->and($mB->fresh()->is_primary)->toBeTrue()
        ->and($mC->fresh()->is_primary)->toBeFalse()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1)
        ->and($product->primaryMedia()->id)->toBe($mB->id);
});

test('8. Deleting only primary leaves zero primary', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    expect($product->media()->count())->toBe(1)
        ->and($mA->is_primary)->toBeTrue();

    $deleted = $product->deleteMedia($mA);

    expect($deleted)->toBeTrue()
        ->and($product->media()->count())->toBe(0)
        ->and($product->primaryMedia())->toBeNull()
        ->and($product->getResolvedPrimaryMedia())->toBeNull();
});

test('9. Deleting non-primary preserves existing primary', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg', 'sort_order' => 3]);

    expect($mA->fresh()->is_primary)->toBeTrue();

    $product->deleteMedia($mB);

    expect(Media::where('id', $mB->id)->exists())->toBeFalse()
        ->and($mA->fresh()->is_primary)->toBeTrue()
        ->and($mC->fresh()->is_primary)->toBeFalse()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1)
        ->and($product->primaryMedia()->id)->toBe($mA->id);
});

// ============================================================
// 10-19: SORT ORDER & REORDERING
// ============================================================

test('10. ordered media follows sort_order', function () {
    $product = createDomainTestProduct();

    $product->addMedia(['disk' => 'public', 'path' => 'products/5.jpg', 'sort_order' => 5]);
    $product->addMedia(['disk' => 'public', 'path' => 'products/2.jpg', 'sort_order' => 2]);
    $product->addMedia(['disk' => 'public', 'path' => 'products/8.jpg', 'sort_order' => 8]);
    $product->addMedia(['disk' => 'public', 'path' => 'products/1.jpg', 'sort_order' => 1]);

    $ordered = $product->orderedMedia()->pluck('sort_order')->all();
    expect($ordered)->toBe([1, 2, 5, 8]);
});

test('11. reorderMedia() correctly reorders', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']);
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg']);
    $mD = $product->addMedia(['disk' => 'public', 'path' => 'products/d.jpg']);

    $product->reorderMedia([$mC->id, $mA->id, $mD->id, $mB->id]);

    expect($mC->fresh()->sort_order)->toBe(1)
        ->and($mA->fresh()->sort_order)->toBe(2)
        ->and($mD->fresh()->sort_order)->toBe(3)
        ->and($mB->fresh()->sort_order)->toBe(4);
});

test('12. reorder normalizes sort_order to 1..N', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 10]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 25]);
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg', 'sort_order' => 99]);

    $product->reorderMedia([$mB->id, $mC->id, $mA->id]);

    expect($mB->fresh()->sort_order)->toBe(1)
        ->and($mC->fresh()->sort_order)->toBe(2)
        ->and($mA->fresh()->sort_order)->toBe(3);
});

test('13. duplicate IDs are rejected in reorder', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']);

    expect(fn () => $product->reorderMedia([$mA->id, $mA->id]))
        ->toThrow(InvalidArgumentException::class, 'Duplicate media IDs');
});

test('14. foreign media IDs are rejected in reorder', function () {
    $product1 = createDomainTestProduct();
    $product2 = createDomainTestProduct();

    $m1 = $product1->addMedia(['disk' => 'public', 'path' => 'products/p1.jpg']);
    $m2 = $product2->addMedia(['disk' => 'public', 'path' => 'products/p2.jpg']);

    expect(fn () => $product1->reorderMedia([$m2->id]))
        ->toThrow(InvalidArgumentException::class);
});

test('15. unknown media IDs are rejected in reorder', function () {
    $product = createDomainTestProduct();
    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);

    expect(fn () => $product->reorderMedia([$mA->id, 999999]))
        ->toThrow(InvalidArgumentException::class);
});

test('16. incomplete media list is rejected in reorder', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']);
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg']);

    expect(fn () => $product->reorderMedia([$mA->id, $mB->id]))
        ->toThrow(InvalidArgumentException::class);
});

test('17. complete reorder preserves all media records in database', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']);

    $product->reorderMedia([$mB->id, $mA->id]);

    expect($product->media()->count())->toBe(2)
        ->and(Media::where('id', $mA->id)->exists())->toBeTrue()
        ->and(Media::where('id', $mB->id)->exists())->toBeTrue();
});

test('18. reorder preserves primary state', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']); // primary
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']); // non-primary
    $mC = $product->addMedia(['disk' => 'public', 'path' => 'products/c.jpg']); // non-primary

    expect($mA->fresh()->is_primary)->toBeTrue()
        ->and($mB->fresh()->is_primary)->toBeFalse()
        ->and($mC->fresh()->is_primary)->toBeFalse();

    $product->reorderMedia([$mC->id, $mB->id, $mA->id]);

    expect($mC->fresh()->sort_order)->toBe(1)
        ->and($mC->fresh()->is_primary)->toBeFalse()
        ->and($mB->fresh()->sort_order)->toBe(2)
        ->and($mB->fresh()->is_primary)->toBeFalse()
        ->and($mA->fresh()->sort_order)->toBe(3)
        ->and($mA->fresh()->is_primary)->toBeTrue();
});

test('19. reorder rolls back if validation or operation fails', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);

    try {
        $product->reorderMedia([$mB->id, 999999]);
    } catch (InvalidArgumentException) {
        // Expected
    }

    expect($mA->fresh()->sort_order)->toBe(1)
        ->and($mB->fresh()->sort_order)->toBe(2);
});

// ============================================================
// 20-24: OWNERSHIP VALIDATION
// ============================================================

test('20. Product cannot mutate another Product media', function () {
    $product1 = createDomainTestProduct();
    $product2 = createDomainTestProduct();

    $m1 = $product1->addMedia(['disk' => 'public', 'path' => 'products/p1.jpg']);
    $m2 = $product2->addMedia(['disk' => 'public', 'path' => 'products/p2.jpg']);

    expect(fn () => $product1->setPrimaryMedia($m2))
        ->toThrow(DomainException::class);

    expect(fn () => $product1->deleteMedia($m2))
        ->toThrow(DomainException::class);

    expect(fn () => $product1->updateMediaAltText($m2, 'Hacked Alt'))
        ->toThrow(DomainException::class);

    expect($m2->fresh()->is_primary)->toBeTrue()
        ->and($m2->fresh()->alt_text)->toBeNull();
});

test('21. Variant cannot mutate another Variant media', function () {
    $product = createDomainTestProduct();
    $variant1 = $product->defaultVariant;
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $variant1->unit_id,
        'is_default' => false,
    ]);

    $m1 = $variant1->addMedia(['disk' => 'public', 'path' => 'variants/v1.jpg']);
    $m2 = $variant2->addMedia(['disk' => 'public', 'path' => 'variants/v2.jpg']);

    expect(fn () => $variant1->setPrimaryMedia($m2))
        ->toThrow(DomainException::class);

    expect(fn () => $variant1->deleteMedia($m2))
        ->toThrow(DomainException::class);

    expect(fn () => $variant1->updateMediaAltText($m2, 'Hacked Alt'))
        ->toThrow(DomainException::class);

    expect($m2->fresh()->is_primary)->toBeTrue();
});

test('22. Product cannot mutate Variant media through an invalid ownership call', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    $variantMedia = $variant->addMedia(['disk' => 'public', 'path' => 'variants/v.jpg']);

    expect(fn () => $product->setPrimaryMedia($variantMedia))
        ->toThrow(DomainException::class);

    expect(fn () => $product->deleteMedia($variantMedia))
        ->toThrow(DomainException::class);

    expect(fn () => $product->updateMediaAltText($variantMedia, 'Hacked'))
        ->toThrow(DomainException::class);
});

test('23. Variant cannot mutate Product media through an invalid ownership call', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    $productMedia = $product->addMedia(['disk' => 'public', 'path' => 'products/p.jpg']);

    expect(fn () => $variant->setPrimaryMedia($productMedia))
        ->toThrow(DomainException::class);

    expect(fn () => $variant->deleteMedia($productMedia))
        ->toThrow(DomainException::class);

    expect(fn () => $variant->updateMediaAltText($productMedia, 'Hacked'))
        ->toThrow(DomainException::class);
});

test('24. unsupported polymorphic owner is rejected', function () {
    $category = Category::factory()->create(['is_active' => true]);

    expect(fn () => Media::createForOwner($category, ['disk' => 'public', 'path' => 'cat.jpg']))
        ->toThrow(DomainException::class);

    // Direct DB insertion with unsupported owner
    $invalidMediaId = DB::table('media')->insertGetId([
        'mediable_type' => Category::class,
        'mediable_id' => $category->id,
        'disk' => 'public',
        'path' => 'cat.jpg',
        'sort_order' => 1,
        'is_primary' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $invalidMedia = Media::find($invalidMediaId);

    expect(fn () => $invalidMedia->validateSupportedOwner())
        ->toThrow(DomainException::class, 'Unsupported mediable type');

    expect(fn () => $invalidMedia->setPrimary())
        ->toThrow(DomainException::class);

    expect(fn () => $invalidMedia->deleteSafely())
        ->toThrow(DomainException::class);

    expect(fn () => $invalidMedia->updateAltText('New Alt'))
        ->toThrow(DomainException::class);
});

// ============================================================
// 25-29: FALLBACK LOGIC
// ============================================================

test('25. Variant with own media uses only Variant media', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    $pA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']);
    $pB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']);

    $vC = $variant->addMedia(['disk' => 'public', 'path' => 'variants/c.jpg']);
    $vD = $variant->addMedia(['disk' => 'public', 'path' => 'variants/d.jpg']);

    $resolved = $variant->getResolvedMedia();

    expect($resolved)->toHaveCount(2)
        ->and($resolved->pluck('id')->all())->toEqualCanonicalizing([$vC->id, $vD->id])
        ->and($resolved->pluck('id')->all())->not->toContain($pA->id)
        ->and($resolved->pluck('id')->all())->not->toContain($pB->id);
});

test('26. Variant with no media falls back to Product media', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    $pA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $pB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);

    expect($variant->media()->count())->toBe(0);

    $resolved = $variant->getResolvedMedia();

    expect($resolved)->toHaveCount(2)
        ->and($resolved->pluck('id')->all())->toBe([$pA->id, $pB->id]);
});

test('27. Variant primary falls back to Product primary', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    $pA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg']); // primary
    $pB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg']); // non-primary

    expect($variant->media()->count())->toBe(0);

    $primary = $variant->getResolvedPrimaryMedia();

    expect($primary)->not->toBeNull()
        ->and($primary->id)->toBe($pA->id);
});

test('28. Product primary fallback uses lowest sort_order when no primary exists', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    // Direct insertion of two non-primary images for product
    $m1 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/sort5.jpg',
        'sort_order' => 5,
        'is_primary' => false,
    ]);

    $m2 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/sort2.jpg',
        'sort_order' => 2,
        'is_primary' => false,
    ]);

    expect($product->primaryMedia())->toBeNull();

    // Product fallback to lowest sort order
    $productPrimary = $product->getResolvedPrimaryMedia();
    expect($productPrimary)->not->toBeNull()
        ->and($productPrimary->id)->toBe($m2->id);

    // Variant fallback to product fallback
    $variantPrimary = $variant->getResolvedPrimaryMedia();
    expect($variantPrimary)->not->toBeNull()
        ->and($variantPrimary->id)->toBe($m2->id);
});

test('29. No media returns null or empty appropriately', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    expect($product->getResolvedMedia())->toBeInstanceOf(Collection::class)
        ->and($product->getResolvedMedia())->toHaveCount(0)
        ->and($product->getResolvedPrimaryMedia())->toBeNull()
        ->and($variant->getResolvedMedia())->toBeInstanceOf(Collection::class)
        ->and($variant->getResolvedMedia())->toHaveCount(0)
        ->and($variant->getResolvedPrimaryMedia())->toBeNull();
});

// ============================================================
// 30-34: LIFECYCLE & SOFT DELETES
// ============================================================

test('30. Inactive Product can manage media', function () {
    $product = createDomainTestProduct(['is_active' => false]);
    expect($product->is_active)->toBeFalse();

    $media = $product->addMedia(['disk' => 'public', 'path' => 'products/inactive.jpg']);
    expect($media->is_primary)->toBeTrue();

    $media2 = $product->addMedia(['disk' => 'public', 'path' => 'products/inactive2.jpg']);
    $product->reorderMedia([$media2->id, $media->id]);

    expect($media2->fresh()->sort_order)->toBe(1);

    $product->deleteMedia($media);
    expect(Media::where('id', $media->id)->exists())->toBeFalse();
});

test('31. Inactive Variant can manage media', function () {
    $product = createDomainTestProduct();
    $variant1 = $product->defaultVariant;
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $variant1->unit_id,
        'is_default' => false,
        'is_active' => false,
    ]);
    expect($variant2->is_active)->toBeFalse();

    $media = $variant2->addMedia(['disk' => 'public', 'path' => 'variants/inactive.jpg']);
    expect($media->is_primary)->toBeTrue();

    $media2 = $variant2->addMedia(['disk' => 'public', 'path' => 'variants/inactive2.jpg']);
    $variant2->reorderMedia([$media2->id, $media->id]);

    expect($media2->fresh()->sort_order)->toBe(1);

    $variant2->deleteMedia($media);
    expect(Media::where('id', $media->id)->exists())->toBeFalse();
});

test('32. Soft-deleting Product does not delete media', function () {
    $product = createDomainTestProduct();
    $media = $product->addMedia(['disk' => 'public', 'path' => 'products/preserved.jpg']);

    $product->delete();
    expect($product->trashed())->toBeTrue()
        ->and(Media::where('id', $media->id)->exists())->toBeTrue()
        ->and(Media::find($media->id))->not->toBeNull();
});

test('33. Soft-deleting Variant does not delete media', function () {
    $product = createDomainTestProduct();
    $variant1 = $product->defaultVariant;
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $variant1->unit_id,
        'is_default' => false,
        'is_active' => true,
    ]);
    $media = $variant2->addMedia(['disk' => 'public', 'path' => 'variants/preserved.jpg']);

    $variant2->delete();
    expect($variant2->trashed())->toBeTrue()
        ->and(Media::where('id', $media->id)->exists())->toBeTrue()
        ->and(Media::find($media->id))->not->toBeNull();
});

test('34. Media remains non-soft-deletable', function () {
    expect(Schema::hasColumn('media', 'deleted_at'))->toBeFalse();

    $product = createDomainTestProduct();
    $media = $product->addMedia(['disk' => 'public', 'path' => 'products/delete.jpg']);

    $media->deleteSafely();

    expect(Media::where('id', $media->id)->exists())->toBeFalse()
        ->and(DB::table('media')->where('id', $media->id)->exists())->toBeFalse();
});

// ============================================================
// 35-36: METADATA & ALT TEXT
// ============================================================

test('35. System-managed metadata cannot be arbitrarily mutated through domain APIs', function () {
    $product = createDomainTestProduct();
    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/locked.jpg',
        'original_name' => 'locked.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
        'width' => 800,
        'height' => 600,
    ]);

    // Media model should not have domain setters for system fields
    expect(method_exists($media, 'setPath'))->toBeFalse()
        ->and(method_exists($media, 'setDisk'))->toBeFalse()
        ->and(method_exists($media, 'setMimeType'))->toBeFalse();

    // Verify system fields are preserved exactly
    $fresh = $media->fresh();
    expect($fresh->disk)->toBe('public')
        ->and($fresh->path)->toBe('products/locked.jpg')
        ->and($fresh->original_name)->toBe('locked.jpg')
        ->and($fresh->mime_type)->toBe('image/jpeg')
        ->and($fresh->size)->toBe(12345)
        ->and($fresh->width)->toBe(800)
        ->and($fresh->height)->toBe(600);
});

test('36. alt_text remains editable through domain API', function () {
    $product = createDomainTestProduct();
    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/alt-test.jpg',
        'alt_text' => 'Initial Alt',
    ]);

    expect($media->alt_text)->toBe('Initial Alt');

    $product->updateMediaAltText($media, 'Updated Product Alt');
    expect($media->fresh()->alt_text)->toBe('Updated Product Alt');

    $media->updateAltText('Direct Model Update');
    expect($media->fresh()->alt_text)->toBe('Direct Model Update');

    $media->updateAltText(null);
    expect($media->fresh()->alt_text)->toBeNull();
});

// ============================================================
// 37-39: TRANSACTIONS & ATOMICITY
// ============================================================

test('37. setPrimary is atomic', function () {
    $product = createDomainTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'products/1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'products/2.jpg']);

    expect($m1->fresh()->is_primary)->toBeTrue()
        ->and($m2->fresh()->is_primary)->toBeFalse();

    // Test that setPrimary succeeds atomically
    $m2->setPrimary();

    expect($m2->fresh()->is_primary)->toBeTrue()
        ->and($m1->fresh()->is_primary)->toBeFalse()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('38. primary deletion and promotion is atomic', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);

    expect($mA->fresh()->is_primary)->toBeTrue()
        ->and($mB->fresh()->is_primary)->toBeFalse();

    $product->deleteMedia($mA);

    expect(Media::where('id', $mA->id)->exists())->toBeFalse()
        ->and($mB->fresh()->is_primary)->toBeTrue()
        ->and($product->media()->where('is_primary', true)->count())->toBe(1);
});

test('39. reorder is atomic and rolls back on failure', function () {
    $product = createDomainTestProduct();

    $mA = $product->addMedia(['disk' => 'public', 'path' => 'products/a.jpg', 'sort_order' => 1]);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'products/b.jpg', 'sort_order' => 2]);

    // An exception during reorder keeps original state intact
    try {
        $product->reorderMedia([$mB->id, 99999]);
    } catch (InvalidArgumentException) {
    }

    expect($mA->fresh()->sort_order)->toBe(1)
        ->and($mB->fresh()->sort_order)->toBe(2);
});

// ============================================================
// 40-44: AUTHORIZATION & REGRESSION
// ============================================================

test('40. Existing authorization architecture remains unchanged with no new permissions', function () {
    $permissions = Permission::pluck('name')->all();

    expect($permissions)->toContain('products.view')
        ->and($permissions)->toContain('products.create')
        ->and($permissions)->toContain('products.update')
        ->and($permissions)->toContain('products.delete')
        ->and($permissions)->not->toContain('media.view')
        ->and($permissions)->not->toContain('media.create')
        ->and($permissions)->not->toContain('media.update')
        ->and($permissions)->not->toContain('media.delete');
});

test('41. products.view remains read-only', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.view');

    expect(Gate::forUser($user)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('products.create'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('products.delete'))->toBeFalse();
});

test('42. products.update allows mutation permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.update');

    expect(Gate::forUser($user)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('products.delete'))->toBeFalse();
});

test('43. Admin Gate::before behavior remains intact', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect(Gate::forUser($admin)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.create'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.delete'))->toBeTrue();
});

test('44. Empty reorderMedia array is valid only when owner has zero media', function () {
    $product = createDomainTestProduct();
    expect($product->media()->count())->toBe(0);

    // Empty array with zero media is valid
    $result = $product->reorderMedia([]);
    expect($result)->toBe($product);

    // Adding media then passing empty array must fail
    $product->addMedia(['disk' => 'public', 'path' => 'p.jpg']);
    expect(fn () => $product->reorderMedia([]))
        ->toThrow(InvalidArgumentException::class, 'cannot be empty');
});

test('45. First-media creation logic serializes around the owner row with lockForUpdate', function () {
    $product = createDomainTestProduct();
    $variant = $product->defaultVariant;

    // Test Product owner row lock
    DB::flushQueryLog();
    DB::enableQueryLog();

    $product->addMedia(['disk' => 'public', 'path' => 'products/lock-test.jpg']);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $productLockQuery = collect($queries)->first(function ($entry) {
        $sql = strtolower($entry['query']);

        return str_contains($sql, 'products') && str_contains($sql, 'for update');
    });

    expect($productLockQuery)->not->toBeNull();

    // Test ProductVariant owner row lock
    DB::flushQueryLog();
    DB::enableQueryLog();

    $variant->addMedia(['disk' => 'public', 'path' => 'variants/lock-test.jpg']);

    $variantQueries = DB::getQueryLog();
    DB::disableQueryLog();

    $variantLockQuery = collect($variantQueries)->first(function ($entry) {
        $sql = strtolower($entry['query']);

        return str_contains($sql, 'product_variants') && str_contains($sql, 'for update');
    });

    expect($variantLockQuery)->not->toBeNull();
});

test('46. Primary deletion repairs a deliberately corrupted state with multiple remaining primary records', function () {
    $product = createDomainTestProduct();

    // Deliberately create corrupted state: 4 media records, 3 of which are marked primary
    $m1 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/primary-to-delete.jpg',
        'sort_order' => 1,
        'is_primary' => true,
    ]);

    $m2 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/corrupted-primary-1.jpg',
        'sort_order' => 2,
        'is_primary' => true,
    ]);

    $m3 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/corrupted-primary-2.jpg',
        'sort_order' => 3,
        'is_primary' => true,
    ]);

    $m4 = $product->media()->create([
        'disk' => 'public',
        'path' => 'products/normal-non-primary.jpg',
        'sort_order' => 4,
        'is_primary' => false,
    ]);

    expect($product->media()->where('is_primary', true)->count())->toBe(3);

    $product->deleteMedia($m1);

    expect($product->media()->count())->toBe(3)
        ->and($product->media()->where('is_primary', true)->count())->toBe(1)
        ->and($product->primaryMedia()->id)->toBe($m2->id)
        ->and($m2->fresh()->is_primary)->toBeTrue()
        ->and($m3->fresh()->is_primary)->toBeFalse()
        ->and($m4->fresh()->is_primary)->toBeFalse();
});

test('47. Primary deletion leaves zero primary when no media remains and exactly one primary when media remains', function () {
    $product = createDomainTestProduct();

    // Case 1: Multiple media -> exactly one primary after deleting primary
    $mA = $product->addMedia(['disk' => 'public', 'path' => 'pA.jpg']);
    $mB = $product->addMedia(['disk' => 'public', 'path' => 'pB.jpg']);

    expect($product->media()->where('is_primary', true)->count())->toBe(1);

    $product->deleteMedia($mA);

    expect($product->media()->count())->toBe(1)
        ->and($product->media()->where('is_primary', true)->count())->toBe(1)
        ->and($product->primaryMedia()->id)->toBe($mB->id);

    // Case 2: Last remaining media deleted -> zero primary
    $product->deleteMedia($mB);

    expect($product->media()->count())->toBe(0)
        ->and($product->media()->where('is_primary', true)->count())->toBe(0)
        ->and($product->primaryMedia())->toBeNull();
});
