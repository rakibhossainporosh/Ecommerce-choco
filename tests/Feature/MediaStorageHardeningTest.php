<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\MediaRelationManager;
use App\Livewire\VariantMediaManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Storage::fake('public');

    Schema::disableForeignKeyConstraints();
    DB::table('variant_attribute_values')->truncate();
    DB::table('product_attribute_values')->truncate();
    DB::table('attribute_values')->truncate();
    DB::table('attributes')->truncate();
    DB::table('media')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

function createStorageTestProduct(): Product
{
    $brand = Brand::factory()->create(['is_active' => true]);
    $category = Category::factory()->create(['is_active' => true]);

    $product = Product::factory()->create([
        'brand_id' => $brand->id,
    ]);
    $product->categories()->attach($category->id);

    return $product;
}

function createStorageTestVariant(Product $product): ProductVariant
{
    $unit = Unit::factory()->create(['is_active' => true]);

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-STOR-'.uniqid(),
    ]);
}

// =========================================================================
// 1. Media URL resolves through its configured disk
// =========================================================================

test('1. Media URL resolves through its configured disk', function () {
    $product = createStorageTestProduct();

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => "products/{$product->id}/sample.jpg",
        'original_name' => 'sample.jpg',
    ]);

    $expectedUrl = Storage::disk('public')->url("products/{$product->id}/sample.jpg");

    expect($media->url())->toBe($expectedUrl)
        ->and($media->url)->toBe($expectedUrl);
});

// =========================================================================
// 2. Media URL does not hard-code /storage/
// =========================================================================

test('2. Media URL does not hard-code /storage/ and respects custom disk base URL', function () {
    Storage::forgetDisk('custom-media');
    Config::set('filesystems.disks.custom-media', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/custom-media'),
        'url' => 'https://media.choco.test/assets',
    ]);

    $product = createStorageTestProduct();

    $media = $product->addMedia([
        'disk' => 'custom-media',
        'path' => 'catalog/hero.webp',
        'original_name' => 'hero.webp',
    ]);

    $resolvedUrl = $media->url();

    expect($resolvedUrl)->toBe('https://media.choco.test/assets/catalog/hero.webp')
        ->and($resolvedUrl)->not->toContain('/storage/catalog/hero.webp');
});

// =========================================================================
// 3. Media URL respects the Media record\'s disk
// =========================================================================

test('3. Media URL respects the Media record disk configuration', function () {
    Storage::forgetDisk('cloud-storage');
    Config::set('filesystems.disks.cloud-storage', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/cloud-storage'),
        'url' => 'https://cloud-storage.choco.test',
    ]);

    $product = createStorageTestProduct();

    $mediaCloud = $product->addMedia([
        'disk' => 'cloud-storage',
        'path' => 'products/cloud-image.jpg',
        'original_name' => 'cloud-image.jpg',
    ]);

    $expectedUrl = Storage::disk('cloud-storage')->url('products/cloud-image.jpg');

    expect($mediaCloud->url())->toBe($expectedUrl)
        ->and($mediaCloud->url())->toContain('cloud-storage.choco.test');
});

// =========================================================================
// 4. Safe/generated storage paths are used for newly uploaded media
// =========================================================================

test('4. Safe/generated storage paths are used for newly uploaded media', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    $file = UploadedFile::fake()->image('raw-user-name.jpg', 400, 400);

    // Test in Product media relation manager
    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('upload', data: [
            'images' => [$file],
        ])
        ->assertHasNoTableActionErrors();

    $productMedia = $product->media()->first();
    expect($productMedia->path)->toStartWith("products/{$product->id}/")
        ->and($productMedia->path)->not->toBe('raw-user-name.jpg')
        ->and($productMedia->path)->toMatch('/\.(jpg|jpeg)$/i');

    // Test in Variant media Livewire component
    $vFile = UploadedFile::fake()->image('variant-user-photo.png', 400, 400);

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$vFile])
        ->call('upload')
        ->assertHasNoErrors();

    $variantMedia = $variant->media()->first();
    expect($variantMedia->path)->toStartWith("variants/{$variant->id}/")
        ->and($variantMedia->path)->not->toBe('variant-user-photo.png')
        ->and($variantMedia->path)->toEndWith('.png');
});

// =========================================================================
// 5. Original filename is metadata, not trusted as storage path
// =========================================================================

test('5. Original filename is metadata and not trusted as physical storage path', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createStorageTestProduct();

    $clientName = 'my-custom-product-photo.jpg';
    $file = UploadedFile::fake()->image($clientName, 300, 300);

    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('upload', data: [
            'images' => [$file],
        ])
        ->assertHasNoTableActionErrors();

    $media = $product->media()->first();

    // Original name is recorded as client metadata
    expect($media->original_name)->toBe($clientName)
        // Physical path is strictly isolated under products/{owner-id}/ and does not use client filename
        ->and($media->path)->toStartWith("products/{$product->id}/")
        ->and($media->path)->not->toBe($clientName)
        ->and($media->path)->not->toContain('my-custom-product-photo');
});

// =========================================================================
// 6. Allowed image types remain: JPEG, JPG, PNG, WebP
// =========================================================================

test('6. Allowed image types JPEG, JPG, PNG, WebP are accepted', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    $jpg = UploadedFile::fake()->image('test.jpg');
    $png = UploadedFile::fake()->image('test.png');
    $webp = UploadedFile::fake()->image('test.webp');

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$jpg, $png, $webp])
        ->call('upload')
        ->assertHasNoErrors();

    expect($variant->media()->count())->toBe(3);
});

// =========================================================================
// 7. Files above 5 MB remain rejected
// =========================================================================

test('7. Files above 5 MB remain strictly rejected', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    // 5.5 MB file (5632 KB > 5120 KB limit)
    $oversizedFile = UploadedFile::fake()->image('oversized.jpg')->size(5632);

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$oversizedFile])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    expect($variant->media()->count())->toBe(0);
});

// =========================================================================
// 8. Invalid/non-image uploads remain rejected
// =========================================================================

test('8. Invalid non-image uploads like PDF, TXT, PHP are rejected', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    $pdf = UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf');
    $php = UploadedFile::fake()->create('shell.php', 50, 'application/x-php');
    $svg = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$pdf])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$php])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$svg])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    expect($variant->media()->count())->toBe(0);
});

// =========================================================================
// 9. A physical file must not be deleted if another Media record references the same disk/path
// =========================================================================

test('9. Shared file protection: physical file is not deleted when another Media record references it', function () {
    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    $sharedPath = "shared/products/{$product->id}/chocolate-bar.jpg";
    Storage::disk('public')->put($sharedPath, 'image-content-binary');

    // Two distinct media records share the same physical file (e.g. shared asset scenario)
    $media1 = $product->addMedia([
        'disk' => 'public',
        'path' => $sharedPath,
        'original_name' => 'chocolate-bar.jpg',
    ]);

    $media2 = $variant->addMedia([
        'disk' => 'public',
        'path' => $sharedPath,
        'original_name' => 'chocolate-bar-variant.jpg',
    ]);

    expect($media1->hasOtherReferences())->toBeTrue()
        ->and($media1->isCleanupCandidate())->toBeFalse()
        ->and(Media::isPathReferenced('public', $sharedPath))->toBeTrue();

    // Delete media1 from product
    $product->deleteMedia($media1);

    // Media 1 record was deleted from DB
    expect(Media::find($media1->id))->toBeNull()
        // Media 2 still exists in DB
        ->and(Media::find($media2->id))->not->toBeNull();

    // Physical cleanup check: Path is still referenced by media2!
    $purged = Media::deletePhysicalFileIfUnreferenced('public', $sharedPath);

    expect($purged)->toBeFalse()
        // The physical file on disk MUST NOT be deleted because media2 still uses it
        ->and(Storage::disk('public')->exists($sharedPath))->toBeTrue();
});

// =========================================================================
// 10. A physical file with no remaining Media reference can become a cleanup candidate
// =========================================================================

test('10. Physical file with no remaining Media references can safely be purged', function () {
    $product = createStorageTestProduct();

    $uniquePath = "products/{$product->id}/unique-to-purge.jpg";
    Storage::disk('public')->put($uniquePath, 'unique-content');

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => $uniquePath,
        'original_name' => 'unique-to-purge.jpg',
    ]);

    expect($media->hasOtherReferences())->toBeFalse()
        ->and($media->isCleanupCandidate())->toBeTrue();

    // Delete from DB
    $product->deleteMedia($media);

    expect(Media::find($media->id))->toBeNull()
        ->and(Media::isPathReferenced('public', $uniquePath))->toBeFalse();

    // Safe cleanup candidate: no remaining DB records reference it
    $purged = Media::deletePhysicalFileIfUnreferenced('public', $uniquePath);

    expect($purged)->toBeTrue()
        ->and(Storage::disk('public')->exists($uniquePath))->toBeFalse();
});

// =========================================================================
// 11. Existing CAT-7B primary behavior remains intact
// =========================================================================

test('11. Existing CAT-7B primary behavior remains fully intact', function () {
    $product = createStorageTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']);
    $m3 = $product->addMedia(['disk' => 'public', 'path' => 'p3.jpg', 'original_name' => 'p3.jpg']);

    expect($m1->fresh()->is_primary)->toBeTrue()
        ->and($m2->fresh()->is_primary)->toBeFalse()
        ->and($m3->fresh()->is_primary)->toBeFalse();

    // Switch primary to m3
    $product->setPrimaryMedia($m3);

    expect($m1->fresh()->is_primary)->toBeFalse()
        ->and($m3->fresh()->is_primary)->toBeTrue();

    // Deleting primary (m3) auto-promotes lowest sort order (m1)
    $product->deleteMedia($m3);

    expect($m1->fresh()->is_primary)->toBeTrue()
        ->and($product->media()->count())->toBe(2);
});

// =========================================================================
// 12. Existing CAT-7B reorder behavior remains intact
// =========================================================================

test('12. Existing CAT-7B reorder behavior remains fully intact', function () {
    $product = createStorageTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']);

    expect($m1->fresh()->sort_order)->toBe(1)
        ->and($m2->fresh()->sort_order)->toBe(2);

    $product->reorderMedia([$m2->id, $m1->id]);

    expect($m2->fresh()->sort_order)->toBe(1)
        ->and($m1->fresh()->sort_order)->toBe(2);

    // Incomplete list throws exception
    expect(fn () => $product->reorderMedia([$m2->id]))
        ->toThrow(InvalidArgumentException::class);
});

// =========================================================================
// 13. Existing Product media behavior remains intact
// =========================================================================

test('13. Existing Product media behavior remains fully intact', function () {
    $product = createStorageTestProduct();

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'p1.jpg',
        'original_name' => 'p1.jpg',
        'alt_text' => 'Initial',
    ]);

    expect($product->orderedMedia()->count())->toBe(1)
        ->and($product->primaryMedia()->id)->toBe($media->id);

    $product->updateMediaAltText($media, 'Updated Description');
    expect($media->fresh()->alt_text)->toBe('Updated Description');
});

// =========================================================================
// 14. Existing Variant media fallback behavior remains intact
// =========================================================================

test('14. Existing Variant media fallback behavior remains fully intact', function () {
    $product = createStorageTestProduct();
    $variant = createStorageTestVariant($product);

    $pMedia = $product->addMedia(['disk' => 'public', 'path' => 'prod.jpg', 'original_name' => 'prod.jpg']);

    // Variant has 0 media -> fallback to product media
    expect($variant->media()->count())->toBe(0)
        ->and($variant->getResolvedPrimaryMedia()?->id)->toBe($pMedia->id)
        ->and($variant->getResolvedMedia()->pluck('id')->all())->toBe([$pMedia->id]);

    // Variant uploads own media -> variant media takes precedence and does NOT merge
    $vMedia = $variant->addMedia(['disk' => 'public', 'path' => 'var.jpg', 'original_name' => 'var.jpg']);

    expect($variant->media()->count())->toBe(1)
        ->and($variant->getResolvedPrimaryMedia()?->id)->toBe($vMedia->id)
        ->and($variant->getResolvedMedia()->pluck('id')->all())->toBe([$vMedia->id])
        ->and($variant->getResolvedMedia()->contains('id', $pMedia->id))->toBeFalse();
});
