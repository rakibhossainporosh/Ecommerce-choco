<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

test('22. Missing SKU is rejected by validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => '',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('23. Duplicate SKU is rejected by validation', function () {
    ProductVariant::factory()->create(['sku' => 'SKU-DUPLICATE-CHECK']);

    expect(function () {
        ProductVariant::factory()->create(['sku' => 'SKU-DUPLICATE-CHECK']);
    })->toThrow(ValidationException::class);
});

test('24. Invalid SKU format with spaces or special symbols is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU WITH SPACES',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU@BAD#SYMBOL',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('25. Duplicate barcode is rejected by validation', function () {
    ProductVariant::factory()->create(['barcode' => '8801234987654']);

    expect(function () {
        ProductVariant::factory()->create(['barcode' => '8801234987654']);
    })->toThrow(ValidationException::class);
});

test('26. Soft-deleted barcode cannot be reused in validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'barcode' => '8809988771122', 'is_default' => true]);
    $var2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'barcode' => '8803344556677', 'is_default' => false]);
    $var2->delete();

    expect(function () use ($unit, $product) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEW-DIFF',
            'barcode' => '8803344556677',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('27. Negative cost_price is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEG-COST',
            'cost_price' => '-10.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('28. Negative selling_price is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEG-SELL',
            'cost_price' => '50.00',
            'selling_price' => '-5.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('29. Negative compare_at_price is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEG-COMPARE',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'compare_at_price' => '-1.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('30. compare_at_price below selling_price is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-INVALID-COMPARE',
            'cost_price' => '50.00',
            'selling_price' => '100.00',
            'compare_at_price' => '75.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('31. Zero unit_quantity is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-ZERO-QTY',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '0',
        ]);
    })->toThrow(ValidationException::class);
});

test('32. Negative unit_quantity is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEG-QTY',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '-1.500',
        ]);
    })->toThrow(ValidationException::class);
});

test('33. Inactive Unit cannot be assigned to a variant', function () {
    $inactiveUnit = Unit::factory()->inactive()->create();
    $product = Product::factory()->create();

    expect(function () use ($product, $inactiveUnit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $inactiveUnit->id,
            'sku' => 'SKU-INACTIVE-UNIT',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('34. Soft-deleted Unit cannot be assigned to a variant', function () {
    $trashedUnit = Unit::factory()->create();
    $trashedUnit->delete();
    $product = Product::factory()->create();

    expect(function () use ($product, $trashedUnit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $trashedUnit->id,
            'sku' => 'SKU-TRASHED-UNIT',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('35. Non-existent Unit cannot be assigned to a variant', function () {
    $product = Product::factory()->create();

    expect(function () use ($product) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => 999999,
            'sku' => 'SKU-NONEXIST-UNIT',
            'cost_price' => '50.00',
            'selling_price' => '80.00',
            'unit_quantity' => '1.000',
        ]);
    })->toThrow(ValidationException::class);
});

test('36. More than one default Variant per Product is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-DEF-1',
        'cost_price' => '50.00',
        'selling_price' => '80.00',
        'unit_quantity' => '1.000',
        'is_default' => true,
    ]);

    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-DEF-2',
            'cost_price' => '60.00',
            'selling_price' => '90.00',
            'unit_quantity' => '1.000',
            'is_default' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('37. Removing default status without another designated default is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var = ProductVariant::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'sku' => 'SKU-SOLE-DEFAULT',
        'cost_price' => '50.00',
        'selling_price' => '80.00',
        'unit_quantity' => '1.000',
        'is_default' => true,
    ]);

    expect(function () use ($var) {
        $var->is_default = false;
        $var->save();
    })->toThrow(ValidationException::class);
});

test('38. Last remaining active Variant cannot be deleted', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    expect(function () use ($variant) {
        $variant->delete();
    })->toThrow(DomainException::class);

    expect($variant->fresh()->trashed())->toBeFalse();
});

test('39. Default Variant cannot be deleted while another Variant is not already designated as default', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $defaultVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $nonDefaultVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    expect(function () use ($defaultVar) {
        $defaultVar->delete();
    })->toThrow(DomainException::class);

    expect($defaultVar->fresh()->trashed())->toBeFalse();
});

test('40. Valid non-default Variant can be deleted when another valid Variant remains', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $defaultVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $nonDefaultVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $nonDefaultVar->delete();

    expect($nonDefaultVar->trashed())->toBeTrue();
});

test('41. Inactive Variant does not count toward the last remaining active Variant guard', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $activeVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $inactiveVar = ProductVariant::factory()->inactive()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
    ]);

    // Deleting the only active variant is rejected even if an inactive variant exists
    expect(function () use ($activeVar) {
        $activeVar->delete();
    })->toThrow(DomainException::class);
});

test('42. Soft-deleted Variant does not count toward the active Variant guard', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $activeVar1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $activeVar2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $activeVar2->delete();

    // Now activeVar1 is the sole remaining non-deleted variant, so deleting it must fail
    expect(function () use ($activeVar1) {
        $activeVar1->delete();
    })->toThrow(DomainException::class);
});

test('43. Product must have at least one active, non-deleted Variant via createWithDefaultVariant', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $category = Category::factory()->create(['is_active' => true]);

    $product = Product::createWithDefaultVariant(
        productAttributes: [
            'name' => 'Atomic Bar Product',
            'slug' => 'atomic-bar-product',
        ],
        variantAttributes: [
            'unit_id' => $unit->id,
            'sku' => 'SKU-ATOMIC-001',
            'cost_price' => '40.00',
            'selling_price' => '70.00',
            'unit_quantity' => '1.000',
        ],
        categoryIds: [$category->id]
    );

    expect($product->exists)->toBeTrue()
        ->and($product->variants()->count())->toBe(1)
        ->and($product->defaultVariant)->not->toBeNull()
        ->and($product->defaultVariant->is_default)->toBeTrue()
        ->and($product->defaultVariant->is_active)->toBeTrue()
        ->and($product->categories->pluck('id'))->toContain($category->id);
});

test('44. Product creation and first Variant creation is atomic inside a transaction', function () {
    $unit = Unit::factory()->create(['is_active' => true]);

    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    Product::createWithDefaultVariant(
        productAttributes: [
            'name' => 'Atomic Chocolate Box',
            'slug' => 'atomic-chocolate-box',
        ],
        variantAttributes: [
            'unit_id' => $unit->id,
            'sku' => 'SKU-ATOMIC-BOX',
            'cost_price' => '100.00',
            'selling_price' => '160.00',
            'unit_quantity' => '1.000',
        ]
    );

    expect(Product::count())->toBe($initialProductsCount + 1)
        ->and(ProductVariant::count())->toBe($initialVariantsCount + 1);
});

test('45. Variant creation failure rolls back Product creation completely', function () {
    $unit = Unit::factory()->create(['is_active' => true]);

    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    expect(function () use ($unit) {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Failed Transaction Product',
                'slug' => 'failed-transaction-product',
            ],
            variantAttributes: [
                'unit_id' => $unit->id,
                'sku' => '', // Intentionally invalid SKU to trigger rollback
                'cost_price' => '50.00',
                'selling_price' => '80.00',
                'unit_quantity' => '1.000',
            ]
        );
    })->toThrow(ValidationException::class);

    // Verify Product creation was completely rolled back
    expect(Product::count())->toBe($initialProductsCount)
        ->and(ProductVariant::count())->toBe($initialVariantsCount)
        ->and(Product::where('slug', 'failed-transaction-product')->exists())->toBeFalse();
});

test('46. Restoring a variant that would cause a default conflict is rejected', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();

    $var1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
    ]);

    $var2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
    ]);

    $var2->delete();

    // Now artificially mark trashed var2 as default directly in DB
    DB::table('product_variants')->where('id', $var2->id)->update(['is_default' => true]);
    $var2->refresh();

    // Restoring var2 should be rejected because var1 is already default
    expect(function () use ($var2) {
        $var2->restore();
    })->toThrow(ValidationException::class);
});

test('47. Manual SKU remains unchanged when unrelated fields change', function () {
    $variant = ProductVariant::factory()->create([
        'sku' => 'SKU-MANUAL-PRESERVED',
        'selling_price' => '50.00',
    ]);

    $variant->selling_price = '75.00';
    $variant->save();

    expect($variant->fresh()->sku)->toBe('SKU-MANUAL-PRESERVED')
        ->and($variant->fresh()->selling_price)->toBe('75.00');
});

test('48. Unit physical deletion is restricted at database level when assigned to variants', function () {
    $unit = Unit::factory()->create();
    ProductVariant::factory()->create(['unit_id' => $unit->id]);

    expect(function () use ($unit) {
        $unit->forceDeleteQuietly();
    })->toThrow(QueryException::class);
});

test('49. Scenario A: Cannot deactivate default variant even when another active non-default variant exists', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $varA = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $varB = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    expect(function () use ($varA) {
        $varA->is_active = false;
        $varA->save();
    })->toThrow(ValidationException::class);

    expect($varA->fresh()->is_active)->toBeTrue();
});

test('50. Scenario B: Cannot deactivate default variant when other variants are inactive', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $varA = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $varB = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    expect(function () use ($varA) {
        $varA->is_active = false;
        $varA->save();
    })->toThrow(ValidationException::class);

    expect($varA->fresh()->is_active)->toBeTrue();
});

test('51. Scenario C: Cannot unset default variant when no other default variant exists', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $varA = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $varB = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    expect(function () use ($varA) {
        $varA->is_default = false;
        $varA->save();
    })->toThrow(ValidationException::class);

    expect($varA->fresh()->is_default)->toBeTrue();
});

test('52. Scenario E & F: Inactive variant cannot be created as default or updated to default', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    // Scenario F: Create an inactive variant with is_default = true
    expect(function () use ($product, $unit) {
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-INACTIVE-DEFAULT',
            'cost_price' => '30.00',
            'selling_price' => '60.00',
            'unit_quantity' => '1.000',
            'is_default' => true,
            'is_active' => false,
        ]);
    })->toThrow(ValidationException::class);

    // Scenario E: Update an inactive variant to is_default = true
    $inactiveVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    expect(function () use ($inactiveVar) {
        $inactiveVar->is_default = true;
        $inactiveVar->save();
    })->toThrow(ValidationException::class);
});

test('53. Cannot deactivate the last remaining active variant of a product', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $activeVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    expect(function () use ($activeVar) {
        $activeVar->is_active = false;
        $activeVar->save();
    })->toThrow(ValidationException::class);

    expect($activeVar->fresh()->is_active)->toBeTrue();
});

test('54. Non-default variant can be deactivated when an active default variant remains', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $varA = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $varB = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $varB->is_active = false;
    $varB->save();

    expect($varB->fresh()->is_active)->toBeFalse()
        ->and($varA->fresh()->is_active)->toBeTrue()
        ->and($varA->fresh()->is_default)->toBeTrue();
});

test('55. Restoring an inactive default variant is rejected', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();

    $defaultVar = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $var = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $var->delete();

    // Mark as inactive and default in DB directly while soft-deleted
    DB::table('product_variants')->where('id', $var->id)->update([
        'is_default' => true,
        'is_active' => false,
    ]);
    $var->refresh();

    expect(function () use ($var) {
        $var->restore();
    })->toThrow(ValidationException::class);
});

test('56. Product::createWithDefaultVariant rolls back on invalid Unit ID', function () {
    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    expect(function () {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Invalid Unit Box',
                'slug' => 'invalid-unit-box',
            ],
            variantAttributes: [
                'unit_id' => 999999,
                'sku' => 'SKU-ROLLBACK-UNIT',
                'cost_price' => '50.00',
                'selling_price' => '80.00',
                'unit_quantity' => '1.000',
            ]
        );
    })->toThrow(ValidationException::class);

    expect(Product::count())->toBe($initialProductsCount)
        ->and(ProductVariant::count())->toBe($initialVariantsCount)
        ->and(Product::where('slug', 'invalid-unit-box')->exists())->toBeFalse();
});

test('57. Product::createWithDefaultVariant rolls back on negative cost or selling price', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    expect(function () use ($unit) {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Negative Price Product',
                'slug' => 'negative-price-product',
            ],
            variantAttributes: [
                'unit_id' => $unit->id,
                'sku' => 'SKU-ROLLBACK-NEG-PRICE',
                'cost_price' => '-10.00',
                'selling_price' => '80.00',
                'unit_quantity' => '1.000',
            ]
        );
    })->toThrow(ValidationException::class);

    expect(Product::count())->toBe($initialProductsCount)
        ->and(ProductVariant::count())->toBe($initialVariantsCount);
});

test('58. Product::createWithDefaultVariant rolls back on zero or negative unit_quantity', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    expect(function () use ($unit) {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Zero Quantity Product',
                'slug' => 'zero-quantity-product',
            ],
            variantAttributes: [
                'unit_id' => $unit->id,
                'sku' => 'SKU-ROLLBACK-ZERO-QTY',
                'cost_price' => '50.00',
                'selling_price' => '80.00',
                'unit_quantity' => '0',
            ]
        );
    })->toThrow(ValidationException::class);

    expect(Product::count())->toBe($initialProductsCount)
        ->and(ProductVariant::count())->toBe($initialVariantsCount);
});

test('59. Product::createWithDefaultVariant rolls back when compare_at_price is lower than selling_price', function () {
    $unit = Unit::factory()->create(['is_active' => true]);
    $initialProductsCount = Product::count();
    $initialVariantsCount = ProductVariant::count();

    expect(function () use ($unit) {
        Product::createWithDefaultVariant(
            productAttributes: [
                'name' => 'Compare Price Low Product',
                'slug' => 'compare-price-low-product',
            ],
            variantAttributes: [
                'unit_id' => $unit->id,
                'sku' => 'SKU-ROLLBACK-COMP-PRICE',
                'cost_price' => '50.00',
                'selling_price' => '100.00',
                'compare_at_price' => '80.00',
                'unit_quantity' => '1.000',
            ]
        );
    })->toThrow(ValidationException::class);

    expect(Product::count())->toBe($initialProductsCount)
        ->and(ProductVariant::count())->toBe($initialVariantsCount);
});
