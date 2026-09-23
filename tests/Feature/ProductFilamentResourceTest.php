<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. ProductResource exists and binds to Product model', function () {
    expect(ProductResource::getModel())->toBe(Product::class);
});

test('2. Admin can access ProductResource index page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(ProductResource::getUrl('index'))
        ->assertOk();
});

test('3. Manager can access ProductResource index page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(ProductResource::getUrl('index'))
        ->assertOk();
});

test('4. Staff has read-only view access to ProductResource index page (Option A)', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->get(ProductResource::getUrl('index'))
        ->assertOk();

    expect(ProductResource::canAccess())->toBeTrue()
        ->and(ProductResource::canViewAny())->toBeTrue();
});

test('5. Staff cannot create products', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->get(ProductResource::getUrl('create'))
        ->assertForbidden();

    expect(ProductResource::canCreate())->toBeFalse();
});

test('6. Staff cannot edit products', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $product = Product::factory()->create();

    $this->actingAs($staff)
        ->get(ProductResource::getUrl('edit', ['record' => $product]))
        ->assertForbidden();

    expect(ProductResource::canEdit($product))->toBeFalse();
});

test('7. Staff cannot delete products', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $product = Product::factory()->create();

    expect(ProductResource::canDelete($product))->toBeFalse()
        ->and(ProductResource::canDeleteAny())->toBeFalse();
});

test('8. Unauthenticated guest cannot access ProductResource and is redirected to login', function () {
    $this->get(ProductResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('9. User with can_access_admin_panel set to false is forbidden from panel', function () {
    $userWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $userWithoutPanel->assignRole('Admin');

    $this->actingAs($userWithoutPanel)
        ->get(ProductResource::getUrl('index'))
        ->assertForbidden();
});

test('10. Admin can create product with category and first variant via CreateProduct page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $brand = Brand::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Belgian Dark Chocolate Sea Salt',
            'slug' => 'belgian-dark-chocolate-sea-salt',
            'brand_id' => $brand->id,
            'categories' => [$category->id],
            'short_description' => 'Gourmet dark chocolate with sea salt crystals.',
            'is_active' => true,
            'is_featured' => true,
            'unit_id' => $unit->id,
            'sku' => 'SKU-BELGIAN-SALT-01',
            'cost_price' => '60.00',
            'selling_price' => '95.00',
            'unit_quantity' => '1.000',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Belgian Dark Chocolate Sea Salt',
        'slug' => 'belgian-dark-chocolate-sea-salt',
        'brand_id' => $brand->id,
        'is_active' => true,
        'is_featured' => true,
    ]);

    $createdProduct = Product::where('slug', 'belgian-dark-chocolate-sea-salt')->first();
    expect($createdProduct->categories->pluck('id'))->toContain($category->id)
        ->and($createdProduct->variants)->toHaveCount(1)
        ->and($createdProduct->defaultVariant->sku)->toBe('SKU-BELGIAN-SALT-01');
});

test('11. Manager can create product via CreateProduct page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Manager Artisan Bar',
            'slug' => 'manager-artisan-bar',
            'categories' => [$category->id],
            'is_active' => true,
            'unit_id' => $unit->id,
            'sku' => 'SKU-MGR-BAR-01',
            'cost_price' => '40.00',
            'selling_price' => '70.00',
            'unit_quantity' => '1.000',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Manager Artisan Bar',
        'slug' => 'manager-artisan-bar',
    ]);

    $createdProduct = Product::where('slug', 'manager-artisan-bar')->first();
    expect($createdProduct->defaultVariant)->not->toBeNull()
        ->and($createdProduct->defaultVariant->sku)->toBe('SKU-MGR-BAR-01');
});

test('12. Admin can update product via EditProduct page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'name' => 'Original Product Name',
        'slug' => 'original-product-name',
    ]);
    $product->categories()->attach($category->id);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Product Name',
            'slug' => 'updated-product-name',
            'categories' => [$category->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product Name',
        'slug' => 'updated-product-name',
    ]);
});

test('13. Manager can update product via EditProduct page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'name' => 'Manager Edit Product',
        'slug' => 'manager-edit-product',
    ]);
    $product->categories()->attach($category->id);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Manager Edit Product Renamed',
            'categories' => [$category->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Manager Edit Product Renamed',
    ]);
});

test('14. Manager cannot delete product', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect(ProductResource::canDelete($product))->toBeFalse()
        ->and(ProductResource::canDeleteAny())->toBeFalse();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionHidden('delete');
});

test('15. Admin can soft-delete product from EditProduct page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('delete');

    expect($product->fresh()->trashed())->toBeTrue();
});

test('16. Form requires at least one category', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'No Category Product',
            'slug' => 'no-category-product',
            'categories' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['categories']);
});

test('17. Form rejects duplicate slug on creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->create(['slug' => 'existing-slug-unique']);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Duplicate Slug Product',
            'slug' => 'existing-slug-unique',
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

test('18. Form permits self slug on edit', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['slug' => 'self-slug-edit']);
    $product->categories()->attach($category->id);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Self Edit Name Change',
            'slug' => 'self-slug-edit',
            'categories' => [$category->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('19. Table renders records, searches, and filters by active and trashed', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $activeProduct = Product::factory()->create(['name' => 'Almond Cluster Bar', 'slug' => 'almond-cluster-bar', 'is_active' => true]);
    $inactiveProduct = Product::factory()->create(['name' => 'Winter Spiced Cocoa', 'slug' => 'winter-spiced-cocoa', 'is_active' => false]);
    $deletedProduct = Product::factory()->create(['name' => 'Discarded Truffle', 'slug' => 'discarded-truffle']);
    $deletedProduct->delete();

    // Default view shows active and inactive, hides deleted
    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$activeProduct, $inactiveProduct])
        ->assertCanNotSeeTableRecords([$deletedProduct]);

    // Active status filter
    Livewire::test(ListProducts::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeProduct])
        ->assertCanNotSeeTableRecords([$inactiveProduct]);

    // Trashed filter
    Livewire::test(ListProducts::class)
        ->filterTable('trashed', 'true')
        ->assertCanSeeTableRecords([$deletedProduct]);

    // Search table
    Livewire::test(ListProducts::class)
        ->searchTable('Almond')
        ->assertCanSeeTableRecords([$activeProduct])
        ->assertCanNotSeeTableRecords([$inactiveProduct]);
});

test('20. Soft-deleted product can be resolved through resource route binding', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['slug' => 'soft-del-res']);
    $product->categories()->attach($category->id);
    $product->delete();

    $this->get(ProductResource::getUrl('edit', ['record' => $product]))
        ->assertOk();
});

test('21. User with panel access but zero permissions is denied access to ProductResource index', function () {
    $userWithoutPermissions = User::factory()->create(['can_access_admin_panel' => true]);

    $this->actingAs($userWithoutPermissions)
        ->get(ProductResource::getUrl('index'))
        ->assertForbidden();

    expect(ProductResource::canAccess())->toBeFalse();
});

test('22. Manager cannot call or see delete action on EditProduct', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect(ProductResource::canDelete($product))->toBeFalse()
        ->and(ProductResource::canDeleteAny())->toBeFalse();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionHidden('delete');
});

test('23. Staff cannot see or call delete action or bulk delete on ListProducts table', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect(ProductResource::canDelete($product))->toBeFalse()
        ->and(ProductResource::canDeleteAny())->toBeFalse();

    Livewire::test(ListProducts::class)
        ->assertTableActionHidden('delete', $product)
        ->assertTableBulkActionHidden('delete');
});

test('24. Form rejects inactive category on product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $inactiveCategory = Category::factory()->inactive()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Inactive Category Product',
            'slug' => 'inactive-category-product',
            'categories' => [$inactiveCategory->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['categories']);
});

test('25. Form rejects soft-deleted category on product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $trashedCategory = Category::factory()->create();
    $trashedCategory->delete();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Trashed Category Product',
            'slug' => 'trashed-category-product',
            'categories' => [$trashedCategory->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['categories']);
});

test('26. Form rejects non-existent category on product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Non Existent Category Product',
            'slug' => 'non-existent-category-product',
            'categories' => [999999],
        ])
        ->call('create')
        ->assertHasFormErrors(['categories']);
});

test('27. Form rejects inactive brand on product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $inactiveBrand = Brand::factory()->inactive()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Inactive Brand Product',
            'slug' => 'inactive-brand-product',
            'brand_id' => $inactiveBrand->id,
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['brand_id']);
});

test('28. Form rejects soft-deleted brand on product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $trashedBrand = Brand::factory()->create();
    $trashedBrand->delete();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Trashed Brand Product',
            'slug' => 'trashed-brand-product',
            'brand_id' => $trashedBrand->id,
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['brand_id']);
});

test('29. Form rejects soft-deleted product slug on creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $trashedProduct = Product::factory()->create(['slug' => 'trashed-slug-reserve']);
    $trashedProduct->categories()->attach($category->id);
    $trashedProduct->delete();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Reused Slug Product',
            'slug' => 'trashed-slug-reserve',
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});
