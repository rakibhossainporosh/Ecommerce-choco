<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Models\VariantAttributeValue;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    VariantAttributeValue::truncate();
    ProductAttributeValue::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

// Helper to create a fully valid active product structure
function createValidActiveProductStructure(): array
{
    $unit = Unit::factory()->create(['is_active' => true]);
    $brand = Brand::factory()->create(['is_active' => true]);
    $category = Category::factory()->create(['is_active' => true]);

    $product = Product::create([
        'name' => 'Valid Chocolate Bar',
        'slug' => 'valid-chocolate-bar',
        'brand_id' => $brand->id,
        'is_active' => false,
        'is_featured' => false,
    ]);

    $product->categories()->attach($category->id);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'name' => 'Default Variant',
        'sku' => 'SKU-VAL-001',
        'cost_price' => '50.00',
        'selling_price' => '100.00',
        'unit_quantity' => '1.000',
        'is_default' => true,
        'is_active' => true,
    ]);

    return compact('product', 'variant', 'unit', 'brand', 'category');
}

/*
|--------------------------------------------------------------------------
| Group 1: Product Activation Tests
|--------------------------------------------------------------------------
*/

test('1.1. Activating product fails when it has zero categories', function () {
    extract(createValidActiveProductStructure());
    $product->categories()->detach();

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.2. Activating product fails when it has only inactive categories', function () {
    extract(createValidActiveProductStructure());
    $inactiveCat = Category::factory()->inactive()->create();
    $product->categories()->sync([$inactiveCat->id]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.3. Activating product fails when it has only soft-deleted categories', function () {
    extract(createValidActiveProductStructure());
    $category->delete();

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.4. Activating product fails when it has zero active variants', function () {
    extract(createValidActiveProductStructure());
    // Directly update variant in DB to avoid triggering deactivation guards for testing activation validation
    DB::table('product_variants')->where('id', $variant->id)->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.5. Activating product fails when it has zero default variants', function () {
    extract(createValidActiveProductStructure());
    DB::table('product_variants')->where('id', $variant->id)->update(['is_default' => false]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.6. Activating product fails when it has multiple active default variants', function () {
    extract(createValidActiveProductStructure());
    // Create second default directly in DB
    $secondVariantId = DB::table('product_variants')->insertGetId([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-VAL-002',
        'cost_price' => '60.00',
        'selling_price' => '120.00',
        'unit_quantity' => '1.000',
        'is_default' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.7. Activating product fails when assigned brand is inactive', function () {
    extract(createValidActiveProductStructure());
    $brand->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.8. Activating product fails when assigned brand is soft-deleted', function () {
    extract(createValidActiveProductStructure());
    $brand->delete();

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.9. Activating product fails when an active variant has an inactive unit', function () {
    extract(createValidActiveProductStructure());
    $unit->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.10. Activating product fails when an active variant has a soft-deleted unit', function () {
    extract(createValidActiveProductStructure());
    $unit->delete();

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.11. Activating product fails when required product-scope attribute is missing', function () {
    extract(createValidActiveProductStructure());
    Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Cocoa Origin',
    ]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.12. Activating product fails when active variant is missing required variant-scope attribute', function () {
    extract(createValidActiveProductStructure());
    Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Packaging Material',
    ]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('1.13. Activating product succeeds when all active invariants are satisfied', function () {
    extract(createValidActiveProductStructure());

    $product->activate();

    expect($product->fresh()->is_active)->toBeTrue();
});

test('1.14. Activating product succeeds with brand_id = null (brand is optional)', function () {
    extract(createValidActiveProductStructure());
    $product->brand_id = null;
    $product->save();

    $product->activate();

    expect($product->fresh()->is_active)->toBeTrue()
        ->and($product->fresh()->brand_id)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Group 2: Product Deactivation Tests
|--------------------------------------------------------------------------
*/

test('2.1. Active product can be deactivated via deactivate()', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    expect($product->fresh()->is_active)->toBeTrue();

    $product->deactivate();

    expect($product->fresh()->is_active)->toBeFalse();
});

test('2.2. Deactivating product automatically resets is_featured to false', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->is_featured = true;
    $product->save();
    expect($product->fresh()->is_featured)->toBeTrue();

    $product->deactivate();

    expect($product->fresh()->is_active)->toBeFalse()
        ->and($product->fresh()->is_featured)->toBeFalse();
});

test('2.3. Deactivating product does NOT delete or modify variants', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    $product->deactivate();

    $freshVariant = $variant->fresh();
    expect($freshVariant)->not->toBeNull()
        ->and($freshVariant->trashed())->toBeFalse()
        ->and($freshVariant->is_active)->toBeTrue()
        ->and($freshVariant->is_default)->toBeTrue();
});

test('2.4. Deactivating product does NOT delete or detach categories', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    $product->deactivate();

    expect($product->fresh()->categories()->count())->toBe(1)
        ->and($product->fresh()->categories->first()->id)->toBe($category->id);
});

test('2.5. Deactivating product does NOT delete dynamic attribute assignments', function () {
    extract(createValidActiveProductStructure());
    $attr = Attribute::factory()->productScope()->text()->create(['name' => 'Texture']);
    $product->syncAttributes([$attr->id => 'Silky']);
    $product->activate();

    $product->deactivate();

    expect($product->fresh()->productAttributeValues()->count())->toBe(1)
        ->and($product->fresh()->productAttributeValues->first()->text_value)->toBe('Silky');
});

test('2.6. Inactive product can exist with zero categories', function () {
    $product = Product::create([
        'name' => 'Draft Inactive Chocolate',
        'slug' => 'draft-inactive-chocolate',
        'is_active' => false,
    ]);

    expect($product->fresh()->categories()->count())->toBe(0)
        ->and($product->fresh()->is_active)->toBeFalse();
});

test('2.7. Inactive product can exist with zero active variants', function () {
    $product = Product::create([
        'name' => 'Draft Incomplete Product',
        'slug' => 'draft-incomplete-product',
        'is_active' => false,
    ]);

    expect($product->fresh()->variants()->count())->toBe(0)
        ->and($product->fresh()->is_active)->toBeFalse();
});

test('2.8. Inactive product can exist with missing required attributes', function () {
    Attribute::factory()->productScope()->text()->required()->create(['name' => 'Strict Origin']);

    $product = Product::create([
        'name' => 'Draft Missing Attr Product',
        'slug' => 'draft-missing-attr-product',
        'is_active' => false,
    ]);

    expect($product->fresh()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Group 3: Category Lifecycle Tests
|--------------------------------------------------------------------------
*/

test('3.1. syncCategories() with valid active category IDs succeeds on product', function () {
    extract(createValidActiveProductStructure());
    $newCategory = Category::factory()->create(['is_active' => true]);

    $product->syncCategories([$category->id, $newCategory->id]);

    expect($product->fresh()->categories()->count())->toBe(2)
        ->and($product->fresh()->categories->pluck('id')->all())->toEqualCanonicalizing([$category->id, $newCategory->id]);
});

test('3.2. syncCategories() rejects empty categories array for an ACTIVE product', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    expect(function () use ($product) {
        $product->syncCategories([]);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.3. syncCategories([]) succeeds for an INACTIVE product', function () {
    extract(createValidActiveProductStructure());
    expect($product->is_active)->toBeFalse();

    $product->syncCategories([]);

    expect($product->fresh()->categories()->count())->toBe(0);
});

test('3.4. syncCategories() rejects inactive category IDs', function () {
    extract(createValidActiveProductStructure());
    $inactiveCat = Category::factory()->inactive()->create();

    expect(function () use ($product, $category, $inactiveCat) {
        $product->syncCategories([$category->id, $inactiveCat->id]);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.5. syncCategories() rejects soft-deleted category IDs', function () {
    extract(createValidActiveProductStructure());
    $deletedCat = Category::factory()->create();
    $deletedCat->delete();

    expect(function () use ($product, $category, $deletedCat) {
        $product->syncCategories([$category->id, $deletedCat->id]);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.6. syncCategories() rejects duplicate category IDs', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($product, $category) {
        $product->syncCategories([$category->id, $category->id]);
    })->toThrow(ValidationException::class);
});

test('3.7. attachCategory() adds a valid category to product', function () {
    extract(createValidActiveProductStructure());
    $newCat = Category::factory()->create(['is_active' => true]);

    $product->attachCategory($newCat);

    expect($product->fresh()->categories()->count())->toBe(2)
        ->and($product->fresh()->categories->pluck('id'))->toContain($newCat->id);
});

test('3.8. attachCategory() rejects an already-attached category', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($product, $category) {
        $product->attachCategory($category);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.9. attachCategory() rejects inactive category', function () {
    extract(createValidActiveProductStructure());
    $inactiveCat = Category::factory()->inactive()->create();

    expect(function () use ($product, $inactiveCat) {
        $product->attachCategory($inactiveCat);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.10. attachCategory() rejects soft-deleted category', function () {
    extract(createValidActiveProductStructure());
    $deletedCat = Category::factory()->create();
    $deletedCat->delete();

    expect(function () use ($product, $deletedCat) {
        $product->attachCategory($deletedCat);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.11. detachCategory() removes category when other categories remain', function () {
    extract(createValidActiveProductStructure());
    $secondCat = Category::factory()->create(['is_active' => true]);
    $product->categories()->attach($secondCat->id);
    expect($product->fresh()->categories()->count())->toBe(2);

    $product->detachCategory($secondCat);

    expect($product->fresh()->categories()->count())->toBe(1)
        ->and($product->fresh()->categories->first()->id)->toBe($category->id);
});

test('3.12. detachCategory() rejects removing the final category from an ACTIVE product', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    expect(function () use ($product, $category) {
        $product->detachCategory($category);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->categories()->count())->toBe(1);
});

test('3.13. detachCategory() allows removing the final category from an INACTIVE product', function () {
    extract(createValidActiveProductStructure());
    expect($product->is_active)->toBeFalse();

    $product->detachCategory($category);

    expect($product->fresh()->categories()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Group 4: Brand Lifecycle Tests
|--------------------------------------------------------------------------
*/

test('4.1. Product can be created and saved with brand_id = null', function () {
    $product = Product::create([
        'name' => 'Brandless Chocolate',
        'slug' => 'brandless-chocolate',
        'brand_id' => null,
    ]);

    expect($product->fresh()->brand_id)->toBeNull();
});

test('4.2. Product can be created and saved with an active brand', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $product = Product::create([
        'name' => 'Branded Chocolate',
        'slug' => 'branded-chocolate',
        'brand_id' => $brand->id,
    ]);

    expect($product->fresh()->brand_id)->toBe($brand->id);
});

test('4.3. New product assignment rejects inactive brand', function () {
    $inactiveBrand = Brand::factory()->inactive()->create();

    expect(function () use ($inactiveBrand) {
        Product::create([
            'name' => 'Invalid Brand Product',
            'slug' => 'invalid-brand-product',
            'brand_id' => $inactiveBrand->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('4.4. New product assignment rejects soft-deleted brand', function () {
    $deletedBrand = Brand::factory()->create();
    $deletedBrand->delete();

    expect(function () use ($deletedBrand) {
        Product::create([
            'name' => 'Deleted Brand Product',
            'slug' => 'deleted-brand-product',
            'brand_id' => $deletedBrand->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('4.5. Updating an existing product with a new inactive brand is rejected', function () {
    extract(createValidActiveProductStructure());
    $inactiveBrand = Brand::factory()->inactive()->create();

    expect(function () use ($product, $inactiveBrand) {
        $product->brand_id = $inactiveBrand->id;
        $product->save();
    })->toThrow(ValidationException::class);
});

test('4.6. If an existing product assigned brand becomes inactive, the product is NOT automatically deactivated', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    expect($product->fresh()->is_active)->toBeTrue();

    // Brand becomes inactive
    $brand->update(['is_active' => false]);

    // Product state is NOT mutated
    expect($product->fresh()->is_active)->toBeTrue()
        ->and($product->fresh()->brand_id)->toBe($brand->id);
});

test('4.7. If an existing product assigned brand becomes inactive, updating unrelated fields does NOT fail', function () {
    extract(createValidActiveProductStructure());
    $brand->update(['is_active' => false]);

    $product->short_description = 'Updated description while brand is inactive.';
    $product->save();

    expect($product->fresh()->short_description)->toBe('Updated description while brand is inactive.');
});

/*
|--------------------------------------------------------------------------
| Group 5: Variant Lifecycle Regression Tests
|--------------------------------------------------------------------------
*/

test('5.1. Deleting the last active variant is rejected', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($variant) {
        $variant->delete();
    })->toThrow(DomainException::class);

    expect($variant->fresh()->trashed())->toBeFalse();
});

test('5.2. Deleting the default variant is rejected unless another active default exists', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($variant) {
        $variant->delete();
    })->toThrow(DomainException::class);
});

test('5.3. Deactivating the last active variant is rejected', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($variant) {
        $variant->deactivate();
    })->toThrow(ValidationException::class);

    expect($variant->fresh()->is_active)->toBeTrue();
});

test('5.4. Deactivating the default variant is rejected', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($variant) {
        $variant->deactivate();
    })->toThrow(ValidationException::class);

    expect($variant->fresh()->is_default)->toBeTrue();
});

test('5.5. Creating a second default variant on the same product is rejected', function () {
    extract(createValidActiveProductStructure());

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-SECOND-DEF',
            'cost_price' => '40.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
            'is_default' => true,
            'is_active' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('5.6. Variant with an inactive unit cannot be activated', function () {
    extract(createValidActiveProductStructure());
    $inactiveUnit = Unit::factory()->inactive()->create();
    $secondVariant = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-SEC-VAR',
        'cost_price' => '45.00',
        'selling_price' => '90.00',
        'unit_quantity' => '1.000',
        'is_default' => false,
        'is_active' => false,
    ]);

    // Update unit to inactive unit in DB
    DB::table('product_variants')->where('id', $secondVariant->id)->update(['unit_id' => $inactiveUnit->id]);

    expect(function () use ($secondVariant) {
        $secondVariant->fresh()->activate();
    })->toThrow(ValidationException::class);

    expect($secondVariant->fresh()->is_active)->toBeFalse();
});

test('5.7. If an existing variant unit becomes inactive, the variant is not automatically deactivated and updating price succeeds', function () {
    extract(createValidActiveProductStructure());

    $unit->update(['is_active' => false]);

    // Updating variant price succeeds because unit_id was not modified
    $variant->selling_price = '115.00';
    $variant->save();

    expect($variant->fresh()->selling_price)->toBe('115.00')
        ->and($variant->fresh()->is_active)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Group 6: Product Restore Tests
|--------------------------------------------------------------------------
*/

test('6.1. Soft-deleted product preserves its variants, categories, and attributes', function () {
    extract(createValidActiveProductStructure());
    $attr = Attribute::factory()->productScope()->text()->create(['name' => 'Sweetness']);
    $product->syncAttributes([$attr->id => 'Medium']);

    $product->delete();

    expect($product->trashed())->toBeTrue()
        ->and($variant->fresh()->trashed())->toBeFalse()
        ->and($product->categories()->count())->toBe(1)
        ->and($product->productAttributeValues()->count())->toBe(1);
});

test('6.2. Restoring an inactive product succeeds and keeps is_active = false', function () {
    extract(createValidActiveProductStructure());
    expect($product->is_active)->toBeFalse();

    $product->delete();
    expect($product->trashed())->toBeTrue();

    $product->restore();

    expect($product->fresh()->trashed())->toBeFalse()
        ->and($product->fresh()->is_active)->toBeFalse();
});

test('6.3. Restoring an active product whose requirements are still met succeeds and keeps is_active = true', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    expect($product->is_active)->toBeTrue();

    $product->delete();
    expect($product->trashed())->toBeTrue();

    $product->restore();

    expect($product->fresh()->trashed())->toBeFalse()
        ->and($product->fresh()->is_active)->toBeTrue();
});

test('6.4. Restoring an active product that lacks an active variant is rejected', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->delete();

    // While deleted, variant becomes inactive in DB
    DB::table('product_variants')->where('id', $variant->id)->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->restore();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->trashed())->toBeTrue();
});

test('6.5. Restoring an active product that lacks an active category is rejected', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->delete();

    // While deleted, category is deleted
    $category->delete();

    expect(function () use ($product) {
        $product->restore();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->trashed())->toBeTrue();
});

test('6.6. Restoring an active product whose brand became inactive while deleted is rejected', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->delete();

    // While deleted, brand becomes inactive
    $brand->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->restore();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->trashed())->toBeTrue();
});

test('6.7. Restoring an active product whose variant unit became inactive while deleted is rejected', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->delete();

    // While deleted, unit becomes inactive
    $unit->update(['is_active' => false]);

    expect(function () use ($product) {
        $product->restore();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->trashed())->toBeTrue();
});

test('6.8. Restoring does NOT silently mutate is_active', function () {
    extract(createValidActiveProductStructure());
    expect($product->is_active)->toBeFalse();

    $product->delete();
    $product->restore();

    // Did NOT mutate to active
    expect($product->fresh()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Group 7: Featured-State Tests
|--------------------------------------------------------------------------
*/

test('7.1. Active product can be set to is_featured = true', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    $product->is_featured = true;
    $product->save();

    expect($product->fresh()->is_featured)->toBeTrue()
        ->and($product->fresh()->is_active)->toBeTrue();
});

test('7.2. Creating an inactive product with is_featured = true is rejected', function () {
    expect(function () {
        Product::create([
            'name' => 'Inactive Featured Product',
            'slug' => 'inactive-featured-product',
            'is_active' => false,
            'is_featured' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('7.3. Setting is_featured = true on an existing inactive product is rejected', function () {
    extract(createValidActiveProductStructure());
    expect($product->is_active)->toBeFalse();

    expect(function () use ($product) {
        $product->is_featured = true;
        $product->save();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_featured)->toBeFalse();
});

test('7.4. Setting is_active = false on an already-featured product via save without un-featuring is rejected', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->is_featured = true;
    $product->save();
    expect($product->fresh()->is_featured)->toBeTrue();

    expect(function () use ($product) {
        $product->is_active = false;
        $product->save();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeTrue()
        ->and($product->fresh()->is_featured)->toBeTrue();
});

test('7.5. Product::deactivate() cleanly un-features the product', function () {
    extract(createValidActiveProductStructure());
    $product->activate();
    $product->is_featured = true;
    $product->save();

    $product->deactivate();

    expect($product->fresh()->is_active)->toBeFalse()
        ->and($product->fresh()->is_featured)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Group 8: Attribute Integration Regression Tests
|--------------------------------------------------------------------------
*/

test('8.1. Active product with required product attribute assigned can be activated', function () {
    extract(createValidActiveProductStructure());
    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Cocoa Mass Percentage',
    ]);
    $product->syncAttributes([$reqAttr->id => '70%']);

    $product->activate();

    expect($product->fresh()->is_active)->toBeTrue();
});

test('8.2. Active product missing required product attribute is rejected during activation', function () {
    extract(createValidActiveProductStructure());
    Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Mandatory Origin',
    ]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);
});

test('8.3. Active product whose active variant is missing required variant attribute is rejected during activation', function () {
    extract(createValidActiveProductStructure());
    Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Allergen Warning',
    ]);

    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);
});

test('8.4. Inactive product can have missing required attributes without failure', function () {
    Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Strict Origin',
    ]);

    $product = Product::create([
        'name' => 'Draft Product',
        'slug' => 'draft-product',
        'is_active' => false,
    ]);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('8.5. syncAttributes() on active product validates required attributes', function () {
    extract(createValidActiveProductStructure());
    $product->activate();

    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Mandatory Field',
    ]);

    expect(function () use ($product) {
        $product->syncAttributes([]);
    })->toThrow(ValidationException::class);
});

/*
|--------------------------------------------------------------------------
| Group 9: Authorization Regression Tests
|--------------------------------------------------------------------------
*/

test('9.1. Staff user cannot create, update, or delete products', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff'); // has products.view only

    expect(Gate::forUser($staff)->allows('create', Product::class))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('update', new Product))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('delete', new Product))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('view', new Product))->toBeTrue();
});

test('9.2. Manager user can view, create, and update products, but cannot delete', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect(Gate::forUser($manager)->allows('view', new Product))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', new Product))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', new Product))->toBeFalse();
});

test('9.3. Admin user has full bypass access via Gate::before()', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect(Gate::forUser($admin)->allows('view', new Product))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', new Product))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', new Product))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Group 10: Transaction and Rollback Tests
|--------------------------------------------------------------------------
*/

test('10.1. activate() rollback: if variant unit is invalid, product remains inactive in DB', function () {
    extract(createValidActiveProductStructure());
    $unit->update(['is_active' => false]);

    try {
        $product->activate();
    } catch (ValidationException $e) {
        // Expected exception
    }

    expect($product->fresh()->is_active)->toBeFalse();
});

test('10.2. syncCategories() rollback: if an invalid category ID is in batch, no changes persist', function () {
    extract(createValidActiveProductStructure());
    $originalIds = $product->categories()->pluck('categories.id')->all();

    $newValid = Category::factory()->create(['is_active' => true]);

    try {
        $product->syncCategories([$newValid->id, 999999]);
    } catch (ValidationException $e) {
        // Expected exception
    }

    expect($product->fresh()->categories()->pluck('categories.id')->all())->toBe($originalIds);
});

test('10.3. createWithDefaultVariant() rollback: invalid variant rolls back both product and pivot records', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    $initialProducts = Product::count();
    $initialVariants = ProductVariant::count();
    $initialPivot = DB::table('category_product')->count();

    try {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Rollback Chocolate',
                'slug' => 'rollback-chocolate',
            ],
            variantAttributes: [
                'unit_id' => $unit->id,
                'sku' => '', // Triggers validation error
                'cost_price' => '10.00',
                'selling_price' => '20.00',
                'unit_quantity' => '1.000',
            ],
            categoryIds: [$category->id]
        );
    } catch (ValidationException $e) {
        // Expected exception
    }

    expect(Product::count())->toBe($initialProducts)
        ->and(ProductVariant::count())->toBe($initialVariants)
        ->and(DB::table('category_product')->count())->toBe($initialPivot);
});

test('10.4. syncAttributes() rollback: if any attribute is invalid, existing attributes remain intact', function () {
    extract(createValidActiveProductStructure());
    $attr1 = Attribute::factory()->productScope()->text()->create(['name' => 'Aroma']);
    $product->syncAttributes([$attr1->id => 'Fruity']);
    expect($product->productAttributeValues()->count())->toBe(1);

    try {
        $product->syncAttributes([
            $attr1->id => 'Woody',
            999999 => 'Invalid Attr',
        ]);
    } catch (ValidationException $e) {
        // Expected exception
    }

    expect($product->fresh()->productAttributeValues()->count())->toBe(1)
        ->and($product->fresh()->productAttributeValues->first()->text_value)->toBe('Fruity');
});
