<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\RelationManagers\MediaRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
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

// Helper functions for test setup
function createFilamentTestProduct(): Product
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'brand_id' => $brand->id,
    ]);
    $product->categories()->attach($category->id);

    return $product;
}

function createFilamentTestVariant(Product $product): ProductVariant
{
    $unit = Unit::factory()->create();

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-'.uniqid(),
    ]);
}

// =========================================================================
// Requirement 1 & 2: Authorization & Viewing UI
// =========================================================================

test('1. authorized user can view product media UI', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $product = createFilamentTestProduct();

    // Admin has products.view
    $this->actingAs($admin);
    expect(MediaRelationManager::canViewForRecord($product, EditProduct::class))->toBeTrue();

    // Manager has products.view
    $this->actingAs($manager);
    expect(MediaRelationManager::canViewForRecord($product, EditProduct::class))->toBeTrue();

    // Staff has products.view
    $this->actingAs($staff);
    expect(MediaRelationManager::canViewForRecord($product, EditProduct::class))->toBeTrue();

    // Livewire component mounts successfully
    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])->assertSuccessful();
});

test('2. unauthorized user cannot access media management', function () {
    $product = createFilamentTestProduct();

    // Guest
    auth()->logout();
    expect(MediaRelationManager::canViewForRecord($product, EditProduct::class))->toBeFalse();

    // User without products.view
    $noPermUser = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($noPermUser);
    expect(MediaRelationManager::canViewForRecord($product, EditProduct::class))->toBeFalse();

    // Staff has products.view but NOT products.update
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $component = Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ]);

    // Mutation actions must be hidden from staff
    $component->assertTableActionHidden('upload');

    // Attempting to reorder without products.update aborts 403
    $component->call('reorderTable', [1])
        ->assertForbidden();
});

// =========================================================================
// Requirement 3: Valid Image Upload Creates Media via Domain API
// =========================================================================

test('3. valid product image upload creates Media via domain API', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();
    $file = UploadedFile::fake()->image('choco-bar.jpg', 600, 400)->size(1024);

    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('upload', data: [
            'images' => [$file],
        ])
        ->assertHasNoTableActionErrors();

    expect($product->media()->count())->toBe(1);

    $media = $product->media()->first();
    expect($media->mediable_type)->toBe($product->getMorphClass())
        ->and($media->mediable_id)->toBe($product->id)
        ->and($media->disk)->toBe('public')
        ->and($media->original_name)->toBe('choco-bar.jpg')
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->size)->toBeGreaterThan(0)
        ->and($media->width)->toBe(600)
        ->and($media->height)->toBe(400)
        ->and($media->is_primary)->toBeTrue();
});

// =========================================================================
// Requirement 4: Invalid File Type Rejected
// =========================================================================

test('4. invalid file type rejected in upload validation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();
    $variant = createFilamentTestVariant($product);

    // Test in VariantMediaManager Livewire component
    $invalidPdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$invalidPdf])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    expect($variant->media()->count())->toBe(0);

    // Test text file
    $invalidText = UploadedFile::fake()->create('script.txt', 100, 'text/plain');

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$invalidText])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    expect($variant->media()->count())->toBe(0);
});

// =========================================================================
// Requirement 5: Image Above 5 MB Rejected
// =========================================================================

test('5. image above 5 MB is rejected', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();
    $variant = createFilamentTestVariant($product);

    // 6 MB image (6144 KB > 5120 KB limit)
    $oversizedFile = UploadedFile::fake()->image('huge.png')->size(6144);

    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$oversizedFile])
        ->call('upload')
        ->assertHasErrors(['uploads.0']);

    expect($variant->media()->count())->toBe(0);
});

// =========================================================================
// Requirement 6: First Uploaded Image Automatically Becomes Primary
// =========================================================================

test('6. first uploaded image becomes primary through domain API', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $file1 = UploadedFile::fake()->image('first.jpg', 400, 300);
    $file2 = UploadedFile::fake()->image('second.png', 400, 300);

    $manager = Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ]);

    // Upload first image
    $manager->callTableAction('upload', data: ['images' => [$file1]])
        ->assertHasNoTableActionErrors();
    expect($product->media()->count())->toBe(1);
    expect($product->media()->first()->is_primary)->toBeTrue();

    // Upload second image
    $manager->callTableAction('upload', data: ['images' => [$file2]])
        ->assertHasNoTableActionErrors();
    expect($product->media()->count())->toBe(2);

    $secondMedia = $product->media()->where('original_name', 'second.png')->first();
    expect($secondMedia->is_primary)->toBeFalse();

    // First remains primary
    $firstMedia = $product->media()->where('original_name', 'first.jpg')->first();
    expect($firstMedia->is_primary)->toBeTrue();
});

// =========================================================================
// Requirement 7: Set As Primary Uses Domain API
// =========================================================================

test('7. set-primary action uses domain behavior', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $media1 = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/p1.jpg',
        'original_name' => 'p1.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    $media2 = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/p2.jpg',
        'original_name' => 'p2.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    expect($media1->fresh()->is_primary)->toBeTrue()
        ->and($media2->fresh()->is_primary)->toBeFalse();

    // Trigger setPrimary action on media2
    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('setPrimary', $media2)
        ->assertHasNoTableActionErrors();

    expect($media1->fresh()->is_primary)->toBeFalse()
        ->and($media2->fresh()->is_primary)->toBeTrue();
});

// =========================================================================
// Requirement 8: Reorder Uses Complete Media ID List and CAT-7B Invariants
// =========================================================================

test('8. reorder uses complete media ID list and validates invariants', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']);
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']);
    $m3 = $product->addMedia(['disk' => 'public', 'path' => 'p3.jpg', 'original_name' => 'p3.jpg']);

    expect($m1->fresh()->sort_order)->toBe(1)
        ->and($m2->fresh()->sort_order)->toBe(2)
        ->and($m3->fresh()->sort_order)->toBe(3);

    $component = Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ]);

    // Reorder completely: reverse the order [m3, m2, m1]
    $component->call('reorderTable', [$m3->id, $m2->id, $m1->id]);

    expect($m3->fresh()->sort_order)->toBe(1)
        ->and($m2->fresh()->sort_order)->toBe(2)
        ->and($m1->fresh()->sort_order)->toBe(3);

    // Incomplete reorder list throws InvalidArgumentException through CAT-7B
    expect(fn () => $component->call('reorderTable', [$m3->id, $m2->id]))
        ->toThrow(InvalidArgumentException::class);
});

// =========================================================================
// Requirement 9: Alt Text Editing via Domain API
// =========================================================================

test('9. alt text update works through domain API without modifying system fields', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/choco.png',
        'original_name' => 'choco.png',
        'mime_type' => 'image/png',
        'size' => 2048,
        'width' => 800,
        'height' => 600,
        'alt_text' => 'Initial alt text',
    ]);

    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('editAltText', $media, [
            'alt_text' => 'Premium Dark Chocolate Bar 100g',
        ])
        ->assertHasNoTableActionErrors();

    $fresh = $media->fresh();
    expect($fresh->alt_text)->toBe('Premium Dark Chocolate Bar 100g')
        ->and($fresh->disk)->toBe('public')
        ->and($fresh->path)->toBe('products/choco.png')
        ->and($fresh->original_name)->toBe('choco.png')
        ->and($fresh->mime_type)->toBe('image/png')
        ->and($fresh->size)->toBe(2048)
        ->and($fresh->width)->toBe(800)
        ->and($fresh->height)->toBe(600);
});

// =========================================================================
// Requirement 10: Delete Works Through Domain API
// =========================================================================

test('10. delete works through domain API', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $media = $product->addMedia([
        'disk' => 'public',
        'path' => 'products/to-delete.jpg',
        'original_name' => 'to-delete.jpg',
    ]);

    expect($product->media()->count())->toBe(1);

    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('delete', $media)
        ->assertHasNoTableActionErrors();

    expect($product->media()->count())->toBe(0)
        ->and(Media::find($media->id))->toBeNull();
});

// =========================================================================
// Requirement 11: Primary Deletion Auto-Promotes Next Media
// =========================================================================

test('11. primary deletion follows CAT-7B auto-promotion behavior', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();

    $m1 = $product->addMedia(['disk' => 'public', 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']); // primary
    $m2 = $product->addMedia(['disk' => 'public', 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']); // sort_order 2
    $m3 = $product->addMedia(['disk' => 'public', 'path' => 'p3.jpg', 'original_name' => 'p3.jpg']); // sort_order 3

    expect($m1->fresh()->is_primary)->toBeTrue()
        ->and($m2->fresh()->is_primary)->toBeFalse()
        ->and($m3->fresh()->is_primary)->toBeFalse();

    // Delete primary via UI action
    Livewire::test(MediaRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('delete', $m1)
        ->assertHasNoTableActionErrors();

    expect($product->media()->count())->toBe(2);

    // CAT-7B promotes m2 (lowest sort_order remaining)
    expect($m2->fresh()->is_primary)->toBeTrue()
        ->and($m3->fresh()->is_primary)->toBeFalse();
});

// =========================================================================
// Requirement 12: Variant Media Can Be Independently Managed
// =========================================================================

test('12. variant media can be independently managed via Livewire component and actions', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();
    $variant = createFilamentTestVariant($product);

    $file1 = UploadedFile::fake()->image('v1.jpg', 300, 300);
    $file2 = UploadedFile::fake()->image('v2.png', 300, 300);

    // Upload to variant
    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('uploads', [$file1, $file2])
        ->call('upload')
        ->assertHasNoErrors();

    expect($variant->media()->count())->toBe(2);

    $vMedia1 = $variant->orderedMedia()->first();
    $vMedia2 = $variant->orderedMedia()->skip(1)->first();

    expect($vMedia1->is_primary)->toBeTrue()
        ->and($vMedia2->is_primary)->toBeFalse();

    // Switch primary on variant
    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->call('setPrimary', $vMedia2->id);

    expect($vMedia2->fresh()->is_primary)->toBeTrue()
        ->and($vMedia1->fresh()->is_primary)->toBeFalse();

    // Edit alt text on variant
    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->set('editingAltText', 'Variant Special Edition')
        ->call('saveAltText', $vMedia2->id);

    expect($vMedia2->fresh()->alt_text)->toBe('Variant Special Edition');

    // Reorder variant media
    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->call('reorder', [$vMedia1->id, $vMedia2->id]);

    expect($vMedia1->fresh()->sort_order)->toBe(1)
        ->and($vMedia2->fresh()->sort_order)->toBe(2);

    // Delete variant media
    Livewire::test(VariantMediaManager::class, ['variant' => $variant])
        ->call('deleteMedia', $vMedia1->id);

    expect($variant->media()->count())->toBe(1)
        ->and($variant->media()->first()->id)->toBe($vMedia2->id)
        ->and($variant->media()->first()->is_primary)->toBeTrue();
});

// =========================================================================
// Requirement 13: Variant Media Does Not Merge with Product Media
// =========================================================================

test('13. variant media does not merge with product media', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createFilamentTestProduct();
    $variant = createFilamentTestVariant($product);

    // Product has 2 media items: P1, P2
    $p1 = $product->addMedia(['disk' => 'public', 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']);
    $p2 = $product->addMedia(['disk' => 'public', 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']);

    // Variant has 1 media item: V1
    $v1 = $variant->addMedia(['disk' => 'public', 'path' => 'v1.jpg', 'original_name' => 'v1.jpg']);

    $component = Livewire::test(VariantMediaManager::class, ['variant' => $variant]);

    // Own media in variant view must only contain V1
    $ownMedia = $component->viewData('ownMedia');
    $fallbackMedia = $component->viewData('fallbackMedia');

    expect($ownMedia->pluck('id')->all())->toBe([$v1->id])
        ->and($fallbackMedia)->toBeEmpty();

    // Verified that Product media is NOT merged into ownMedia
    expect($ownMedia->contains('id', $p1->id))->toBeFalse()
        ->and($ownMedia->contains('id', $p2->id))->toBeFalse();

    // Now test when variant has 0 media: fallback displays product media in a dedicated read-only fallback section
    $variant->deleteMedia($v1);
    expect($variant->media()->count())->toBe(0);

    $componentFallback = Livewire::test(VariantMediaManager::class, ['variant' => $variant]);
    $ownMediaFallback = $componentFallback->viewData('ownMedia');
    $fallbackCollection = $componentFallback->viewData('fallbackMedia');

    expect($ownMediaFallback)->toBeEmpty()
        ->and($fallbackCollection->pluck('id')->all())->toBe([$p1->id, $p2->id]);
});

// =========================================================================
// Requirement 14: Existing ProductResource Functionality Remains Intact
// =========================================================================

test('14. existing ProductResource functionality remains intact', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $brand = Brand::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    // ProductResource relations include MediaRelationManager and VariantsRelationManager
    $relations = ProductResource::getRelations();
    expect($relations)->toContain(MediaRelationManager::class)
        ->and($relations)->toContain(VariantsRelationManager::class);

    // ListProducts page operates normally
    $this->get(ProductResource::getUrl('index'))
        ->assertOk();

    // CreateProduct page operates normally
    $this->get(ProductResource::getUrl('create'))
        ->assertOk();

    // Product creation works normally
    $newProduct = Product::factory()->create([
        'name' => 'Test Choco Bar',
        'slug' => 'test-choco-bar',
        'brand_id' => $brand->id,
    ]);
    $newProduct->categories()->attach($category->id);

    // EditProduct page operates normally
    $this->get(ProductResource::getUrl('edit', ['record' => $newProduct]))
        ->assertOk();

    // VariantsRelationManager functions properly
    $variant = ProductVariant::factory()->create([
        'product_id' => $newProduct->id,
        'unit_id' => $unit->id,
        'sku' => 'TEST-VAR-1',
        'cost_price' => 50,
        'selling_price' => 100,
        'unit_quantity' => 1,
        'is_active' => true,
    ]);

    expect($newProduct->variants()->count())->toBe(1);

    // Media status displays properly on variant
    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $newProduct,
        'pageClass' => EditProduct::class,
    ])->assertSuccessful();
});
