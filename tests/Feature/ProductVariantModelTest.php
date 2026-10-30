<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    Schema::disableForeignKeyConstraints();
    ProductVariant::truncate();
    Product::truncate();
    Unit::truncate();
    Category::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    ProductVariant::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Variant can be created with explicit attributes', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'name' => '100g Bar',
        'sku' => 'SKU-DARK-100G',
        'barcode' => '8801234567890',
        'cost_price' => '45.50',
        'selling_price' => '80.00',
        'compare_at_price' => '100.00',
        'unit_quantity' => '100.000',
        'is_default' => true,
        'is_active' => true,
    ]);

    expect($variant)->toBeInstanceOf(ProductVariant::class)
        ->and($variant->id)->toBeGreaterThan(0)
        ->and($variant->name)->toBe('100g Bar')
        ->and($variant->sku)->toBe('SKU-DARK-100G')
        ->and($variant->barcode)->toBe('8801234567890')
        ->and($variant->cost_price)->toBe('45.50')
        ->and($variant->selling_price)->toBe('80.00')
        ->and($variant->compare_at_price)->toBe('100.00')
        ->and($variant->unit_quantity)->toBe('100.000')
        ->and($variant->is_default)->toBeTrue()
        ->and($variant->is_active)->toBeTrue();

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'sku' => 'SKU-DARK-100G',
    ]);
});

test('2. Product relationship works', function () {
    $product = Product::factory()->create(['name' => 'Artisanal Bar']);
    $unit = Unit::factory()->create();

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
    ]);

    expect($variant->product)->toBeInstanceOf(Product::class)
        ->and($variant->product->id)->toBe($product->id)
        ->and($variant->product->name)->toBe('Artisanal Bar');
});

test('3. Unit relationship works', function () {
    $unit = Unit::factory()->create(['name' => 'Piece', 'code' => 'pc']);
    $variant = ProductVariant::factory()->create([
        'unit_id' => $unit->id,
    ]);

    expect($variant->unit)->toBeInstanceOf(Unit::class)
        ->and($variant->unit->id)->toBe($unit->id)
        ->and($variant->unit->code)->toBe('pc');
});

test('4. Product hasMany variants and defaultVariant works', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
    ]);

    expect($product->variants)->toHaveCount(2)
        ->and($product->variants->pluck('id'))->toContain($variant1->id, $variant2->id)
        ->and($product->defaultVariant->id)->toBe($variant1->id);
});

test('5. Default values: is_default defaults to false and is_active defaults to true', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-DEFAULTS-TEST',
        'cost_price' => '20.00',
        'selling_price' => '35.00',
        'unit_quantity' => '1.000',
    ]);

    $variant->refresh();

    expect($variant->is_default)->toBeFalse()
        ->and($variant->is_active)->toBeTrue()
        ->and($variant->name)->toBeNull()
        ->and($variant->barcode)->toBeNull()
        ->and($variant->compare_at_price)->toBeNull();
});

test('6. Casts correctly cast attributes to native types', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-CASTS-TEST',
        'cost_price' => 15.5,
        'selling_price' => 29.99,
        'compare_at_price' => 39.99,
        'unit_quantity' => 2.5,
        'is_default' => 1,
        'is_active' => '1',
    ]);

    $variant->refresh();

    expect($variant->product_id)->toBeInt()
        ->and($variant->unit_id)->toBeInt()
        ->and($variant->cost_price)->toBe('15.50')
        ->and($variant->selling_price)->toBe('29.99')
        ->and($variant->compare_at_price)->toBe('39.99')
        ->and($variant->unit_quantity)->toBe('2.500')
        ->and($variant->is_default)->toBeTrue()
        ->and($variant->is_default)->toBeBool()
        ->and($variant->is_active)->toBeTrue()
        ->and($variant->is_active)->toBeBool();
});

test('7. Active state works', function () {
    $variant = ProductVariant::factory()->active()->create();
    expect($variant->is_active)->toBeTrue();
});

test('8. Inactive state works', function () {
    $variant = ProductVariant::factory()->inactive()->create();
    expect($variant->is_active)->toBeFalse();
});

test('9. Soft delete marks variant as deleted without removing from database', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    // Create two active variants so deleting one does not violate last active variant rule
    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => false]);

    $var2->delete();

    expect($var2->trashed())->toBeTrue();
    $this->assertSoftDeleted('product_variants', [
        'id' => $var2->id,
    ]);
});

test('10. withTrashed retrieves soft-deleted Variant', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => false]);
    $var2->delete();

    expect(ProductVariant::find($var2->id))->toBeNull()
        ->and(ProductVariant::withTrashed()->find($var2->id))->not->toBeNull();
});

test('11. Soft-deleted Variant can be restored', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'is_default' => false]);
    $var2->delete();

    $var2->restore();

    expect($var2->fresh()->trashed())->toBeFalse();
    $this->assertNotSoftDeleted('product_variants', [
        'id' => $var2->id,
    ]);
});

test('12. ProductVariantFactory creates valid variants', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant)->toBeInstanceOf(ProductVariant::class)
        ->and($variant->sku)->not->toBeEmpty()
        ->and($variant->cost_price)->not->toBeEmpty()
        ->and($variant->selling_price)->not->toBeEmpty()
        ->and($variant->unit_quantity)->not->toBeEmpty();
});

test('13. SKU uniqueness works at database level', function () {
    $variant1 = ProductVariant::factory()->create(['sku' => 'SKU-UNIQUE-001']);

    expect(function () {
        ProductVariant::factory()->create(['sku' => 'SKU-UNIQUE-001']);
    })->toThrow(Exception::class);
});

test('14. Soft-deleted SKU cannot be reused (global uniqueness)', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'sku' => 'SKU-RESERVED-1', 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'sku' => 'SKU-RESERVED-2', 'is_default' => false]);
    $var2->delete();

    expect(function () use ($unit, $product) {
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-RESERVED-2',
        ]);
    })->toThrow(Exception::class);
});

test('15. Barcode uniqueness works at database level', function () {
    $variant1 = ProductVariant::factory()->create(['barcode' => '8801122334455']);

    expect(function () {
        ProductVariant::factory()->create(['barcode' => '8801122334455']);
    })->toThrow(Exception::class);
});

test('16. Soft-deleted barcode cannot be reused', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'barcode' => '8809988776655', 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'barcode' => '8805544332211', 'is_default' => false]);
    $var2->delete();

    expect(function () use ($unit, $product) {
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'barcode' => '8805544332211',
        ]);
    })->toThrow(Exception::class);
});

test('17. Nullable barcode works and multiple variants can have null barcode', function () {
    $var1 = ProductVariant::factory()->create(['barcode' => null]);
    $var2 = ProductVariant::factory()->create(['barcode' => null]);

    expect($var1->barcode)->toBeNull()
        ->and($var2->barcode)->toBeNull();
});

test('18. Pricing fields persist correctly with 2 decimal precision', function () {
    $variant = ProductVariant::factory()->create([
        'cost_price' => '123.45',
        'selling_price' => '199.90',
        'compare_at_price' => '249.00',
    ]);

    expect($variant->fresh()->cost_price)->toBe('123.45')
        ->and($variant->fresh()->selling_price)->toBe('199.90')
        ->and($variant->fresh()->compare_at_price)->toBe('249.00');
});

test('19. unit_quantity persists decimal values with 3 decimal precision', function () {
    $variant1 = ProductVariant::factory()->create(['unit_quantity' => '0.250']);
    $variant2 = ProductVariant::factory()->create(['unit_quantity' => '1.500']);

    expect($variant1->fresh()->unit_quantity)->toBe('0.250')
        ->and($variant2->fresh()->unit_quantity)->toBe('1.500');
});

test('20. product_id FK cascadeOnDelete works on physical product deletion', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
    ]);

    $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);

    $product->forceDelete();

    $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
});

test('21. unit_id FK restrictOnDelete prevents physical unit deletion when variants exist', function () {
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create([
        'unit_id' => $unit->id,
    ]);

    expect(function () use ($unit) {
        $unit->forceDeleteQuietly();
    })->toThrow(QueryException::class);
});
