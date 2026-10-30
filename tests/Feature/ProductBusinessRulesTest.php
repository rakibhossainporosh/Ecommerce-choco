<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Product name is normalized and trimmed before storage', function () {
    $product = Product::create([
        'name' => '   Artisanal Truffle Collection   ',
        'slug' => 'artisanal-truffle-collection',
    ]);

    expect($product->name)->toBe('Artisanal Truffle Collection')
        ->and($product->fresh()->name)->toBe('Artisanal Truffle Collection');
});

test('2. Product slug is normalized to lowercase and trimmed', function () {
    $product = Product::create([
        'name' => 'Raw Cacao Powder',
        'slug' => '  RAW-CACAO-POWDER  ',
    ]);

    expect($product->slug)->toBe('raw-cacao-powder')
        ->and($product->fresh()->slug)->toBe('raw-cacao-powder');
});

test('3. Duplicate slug is rejected across active products', function () {
    Product::create([
        'name' => 'Ruby Chocolate Bar',
        'slug' => 'ruby-chocolate-bar',
    ]);

    expect(function () {
        Product::create([
            'name' => 'Another Ruby Bar',
            'slug' => 'ruby-chocolate-bar',
        ]);
    })->toThrow(ValidationException::class);
});

test('4. Soft-deleted product slug cannot be reused (global slug uniqueness)', function () {
    $oldProduct = Product::create([
        'name' => 'Old Gold Chocolate',
        'slug' => 'gold-chocolate',
    ]);
    $oldProduct->delete();
    expect($oldProduct->trashed())->toBeTrue();

    // Attempting to create a new product with the same slug must be rejected
    expect(function () {
        Product::create([
            'name' => 'New Gold Chocolate',
            'slug' => 'gold-chocolate',
        ]);
    })->toThrow(ValidationException::class);
});

test('5. Editing the same Product does not trigger false slug conflict', function () {
    $product = Product::create([
        'name' => 'Milk Chocolate 40%',
        'slug' => 'milk-chocolate-40',
        'short_description' => 'Original description.',
    ]);

    $product->short_description = 'Updated description.';
    $product->save();

    expect($product->fresh()->short_description)->toBe('Updated description.')
        ->and($product->fresh()->slug)->toBe('milk-chocolate-40');
});

test('6. Changing product name does not automatically change existing slug', function () {
    $product = Product::create([
        'name' => 'Original Chocolate Box',
        'slug' => 'original-chocolate-box',
    ]);

    $product->name = 'Updated Deluxe Chocolate Box';
    $product->save();

    expect($product->fresh()->name)->toBe('Updated Deluxe Chocolate Box')
        ->and($product->fresh()->slug)->toBe('original-chocolate-box');

    // Also test in Filament Edit form: name update preserves slug
    $category = Category::factory()->create(['is_active' => true]);
    $product->categories()->attach($category->id);

    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Deluxe Chocolate Hamper',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->slug)->toBe('original-chocolate-box')
        ->and($product->fresh()->name)->toBe('Deluxe Chocolate Hamper');
});

test('7. Inactive Brand cannot be assigned to a product', function () {
    $inactiveBrand = Brand::factory()->inactive()->create();

    expect(function () use ($inactiveBrand) {
        Product::create([
            'name' => 'Brand Test Product',
            'slug' => 'brand-test-product',
            'brand_id' => $inactiveBrand->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('8. Soft-deleted Brand cannot be assigned to a product', function () {
    $deletedBrand = Brand::factory()->create();
    $deletedBrand->delete();

    expect(function () use ($deletedBrand) {
        Product::create([
            'name' => 'Deleted Brand Test',
            'slug' => 'deleted-brand-test',
            'brand_id' => $deletedBrand->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('9. Missing brand_id is allowed (brand is optional)', function () {
    $product = Product::create([
        'name' => 'Generic Cocoa Nibs',
        'slug' => 'generic-cocoa-nibs',
        'brand_id' => null,
    ]);

    expect($product->exists)->toBeTrue()
        ->and($product->brand_id)->toBeNull();
});

test('10. At least one Category is required by domain validation', function () {
    expect(function () {
        Product::validateCategoryAssignment([]);
    })->toThrow(ValidationException::class);
});

test('11. Inactive Category cannot be assigned to a product', function () {
    $inactiveCategory = Category::factory()->inactive()->create();

    expect(function () use ($inactiveCategory) {
        Product::validateCategoryAssignment([$inactiveCategory->id]);
    })->toThrow(ValidationException::class);
});

test('12. Soft-deleted Category cannot be assigned to a product', function () {
    $deletedCategory = Category::factory()->create();
    $deletedCategory->delete();

    expect(function () use ($deletedCategory) {
        Product::validateCategoryAssignment([$deletedCategory->id]);
    })->toThrow(ValidationException::class);
});

test('13. Duplicate Category assignment is rejected by domain validation', function () {
    $category = Category::factory()->create(['is_active' => true]);

    expect(function () use ($category) {
        Product::validateCategoryAssignment([$category->id, $category->id]);
    })->toThrow(ValidationException::class);
});

test('14. Valid multiple categories pass domain validation', function () {
    $category1 = Category::factory()->create(['is_active' => true]);
    $category2 = Category::factory()->create(['is_active' => true]);

    // Should not throw any exception
    Product::validateCategoryAssignment([$category1->id, $category2->id]);
    expect(true)->toBeTrue();
});

test('15. Product can be inactive', function () {
    $product = Product::create([
        'name' => 'Seasonal Easter Egg',
        'slug' => 'seasonal-easter-egg',
        'is_active' => false,
    ]);

    expect($product->is_active)->toBeFalse();
});

test('16. Product can be featured', function () {
    $product = Product::create([
        'name' => 'Bestseller Gold Truffles',
        'slug' => 'bestseller-gold-truffles',
        'is_featured' => true,
    ]);

    expect($product->is_featured)->toBeTrue();
});

test('17. Product deletion does not cascade to Brand or Category', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $product = Product::create([
        'name' => 'Cascade Test Chocolate',
        'slug' => 'cascade-test-chocolate',
        'brand_id' => $brand->id,
    ]);
    $product->categories()->attach($category->id);

    $product->delete();

    expect($brand->fresh())->not->toBeNull()
        ->and($brand->fresh()->trashed())->toBeFalse()
        ->and($category->fresh())->not->toBeNull()
        ->and($category->fresh()->trashed())->toBeFalse();
});

test('18. Slug format enforces alpha_dash characters', function () {
    expect(function () {
        Product::create([
            'name' => 'Invalid Slug Product',
            'slug' => 'invalid slug with spaces!',
        ]);
    })->toThrow(ValidationException::class);

    expect(function () {
        Product::create([
            'name' => 'Invalid Slug Product Two',
            'slug' => 'invalid@slug#two',
        ]);
    })->toThrow(ValidationException::class);
});

test('19. Non-existent Brand ID cannot be assigned to a product', function () {
    expect(function () {
        Product::create([
            'name' => 'Non Existent Brand Product',
            'slug' => 'non-existent-brand-product',
            'brand_id' => 999999,
        ]);
    })->toThrow(ValidationException::class);
});

test('20. Non-existent Category ID is rejected by domain validation', function () {
    expect(function () {
        Product::validateCategoryAssignment([999999]);
    })->toThrow(ValidationException::class);
});

test('21. Manual slug change to another unused valid slug is permitted', function () {
    $product = Product::create([
        'name' => 'Custom Slug Product',
        'slug' => 'original-slug',
    ]);

    $product->slug = 'manually-updated-slug';
    $product->save();

    expect($product->fresh()->slug)->toBe('manually-updated-slug');
});

test('22. Product name is required and cannot be blank', function () {
    expect(function () {
        Product::create([
            'name' => '   ',
            'slug' => 'valid-slug',
        ]);
    })->toThrow(ValidationException::class);
});

test('23. Product slug is required and cannot be blank', function () {
    expect(function () {
        Product::create([
            'name' => 'Valid Name',
            'slug' => '   ',
        ]);
    })->toThrow(ValidationException::class);
});
