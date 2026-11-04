<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductDuplicator;
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
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    DB::table('inventory_movements')->truncate();
    Inventory::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

function createPolishProduct(): Product
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'name' => 'Audit Shirt',
        'slug' => 'audit-shirt',
        'is_active' => true,
        'is_featured' => true,
    ]);
    $product->categories()->attach($category->id);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'AUDIT-SKU-1',
        'is_default' => true,
        'is_active' => true,
        'cost_price' => 300,
        'selling_price' => 500,
        'compare_at_price' => 600,
    ]);

    Inventory::createForVariant($variant, openingQuantity: 12, lowStockThreshold: 3);

    return $product->fresh(['variants.inventory', 'categories']);
}

test('view product page is registered and reachable for admin', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $product = createPolishProduct();

    $this->actingAs($admin)
        ->get(ProductResource::getUrl('view', ['record' => $product]))
        ->assertOk();

    Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Audit Shirt')
        ->assertSee('AUDIT-SKU-1')
        ->assertSeeHtml('product-detail-view')
        ->assertSeeHtml('pdv-hero')
        ->assertSeeHtml('pdv-kpis')
        ->assertSeeHtml('pdv-footer-meta')
        ->assertSee('AUDIT-SKU-1');
});

test('product duplicator creates inactive draft with unique sku and zero stock', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $product = createPolishProduct();
    $copy = app(ProductDuplicator::class)->duplicate($product);

    expect($copy->id)->not->toBe($product->id)
        ->and($copy->is_active)->toBeFalse()
        ->and($copy->is_featured)->toBeFalse()
        ->and($copy->slug)->not->toBe($product->slug)
        ->and($copy->variants)->toHaveCount(1)
        ->and($copy->variants->first()->sku)->not->toBe('AUDIT-SKU-1')
        ->and($copy->variants->first()->inventory?->quantity)->toBe(0)
        ->and($copy->variants->first()->inventory?->low_stock_threshold)->toBe(3)
        ->and($copy->categories->pluck('id')->all())->toEqual($product->categories->pluck('id')->all());
});

test('list products exposes publish and export bulk actions', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    createPolishProduct();

    Livewire::test(ListProducts::class)
        ->assertSuccessful()
        ->assertTableBulkActionExists('activateSelected')
        ->assertTableBulkActionExists('deactivateSelected')
        ->assertTableBulkActionExists('exportSelected')
        ->assertActionExists('exportAll');
});

test('edit product header includes storefront and view details actions', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $product = createPolishProduct();
    $this->actingAs($admin);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSuccessful()
        ->assertActionExists('viewOnStore')
        ->assertActionExists('duplicate')
        ->assertActionExists('view');
});
