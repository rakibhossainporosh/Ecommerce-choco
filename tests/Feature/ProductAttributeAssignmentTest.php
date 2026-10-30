<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    Schema::disableForeignKeyConstraints();
    ProductAttributeValue::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    Product::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    ProductAttributeValue::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    Product::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Product can receive product-scope Attribute', function () {
    $product = Product::factory()->create();
    $attribute = Attribute::factory()->productScope()->text()->create();

    $assignment = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attribute->id,
        'text_value' => 'Single Origin 85%',
    ]);

    expect($assignment)->toBeInstanceOf(ProductAttributeValue::class)
        ->and($assignment->id)->toBeGreaterThan(0)
        ->and($assignment->product_id)->toBe($product->id)
        ->and($assignment->attribute_id)->toBe($attribute->id)
        ->and($assignment->text_value)->toBe('Single Origin 85%');

    $this->assertDatabaseHas('product_attribute_values', [
        'id' => $assignment->id,
        'product_id' => $product->id,
        'attribute_id' => $attribute->id,
        'text_value' => 'Single Origin 85%',
    ]);
});

test('2. Product cannot receive variant-scope Attribute', function () {
    $product = Product::factory()->create();
    $variantAttr = Attribute::factory()->variantScope()->text()->create();

    expect(function () use ($product, $variantAttr) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $variantAttr->id,
            'text_value' => 'Attempting variant scope on product',
        ]);
    })->toThrow(ValidationException::class);
});

test('3. Required storage column is used correctly for text, number, boolean, select, and multiselect', function () {
    $product = Product::factory()->create();

    // Text
    $textAttr = Attribute::factory()->productScope()->text()->create();
    $textAssign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $textAttr->id,
        'text_value' => 'Madagascar',
    ]);
    expect($textAssign->text_value)->toBe('Madagascar');

    // Number
    $numberAttr = Attribute::factory()->productScope()->number()->create();
    $numberAssign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $numberAttr->id,
        'number_value' => 85.5,
    ]);
    expect((float) $numberAssign->number_value)->toBe(85.5);

    // Boolean
    $boolAttr = Attribute::factory()->productScope()->boolean()->create();
    $boolAssign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $boolAttr->id,
        'boolean_value' => true,
    ]);
    expect($boolAssign->boolean_value)->toBeTrue();

    // Select
    $selectAttr = Attribute::factory()->productScope()->select()->create();
    $selectVal = AttributeValue::factory()->create(['attribute_id' => $selectAttr->id]);
    $selectAssign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $selectAttr->id,
        'attribute_value_id' => $selectVal->id,
    ]);
    expect($selectAssign->attribute_value_id)->toBe($selectVal->id);

    // Multiselect
    $multiAttr = Attribute::factory()->productScope()->multiselect()->create();
    $multiVal = AttributeValue::factory()->create(['attribute_id' => $multiAttr->id]);
    $multiAssign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $multiAttr->id,
        'attribute_value_id' => $multiVal->id,
    ]);
    expect($multiAssign->attribute_value_id)->toBe($multiVal->id);
});

test('4. Invalid storage combinations are rejected', function () {
    $product = Product::factory()->create();

    // Text attribute with number_value set
    $textAttr = Attribute::factory()->productScope()->text()->create();
    expect(function () use ($product, $textAttr) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $textAttr->id,
            'text_value' => 'Valid Text',
            'number_value' => 123.45,
        ]);
    })->toThrow(ValidationException::class);

    // Number attribute with text_value set
    $numAttr = Attribute::factory()->productScope()->number()->create();
    expect(function () use ($product, $numAttr) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $numAttr->id,
            'number_value' => 50,
            'text_value' => 'Extra Text',
        ]);
    })->toThrow(ValidationException::class);

    // Select attribute with boolean_value set
    $selectAttr = Attribute::factory()->productScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $selectAttr->id]);
    expect(function () use ($product, $selectAttr, $val) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $selectAttr->id,
            'attribute_value_id' => $val->id,
            'boolean_value' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('5. AttributeValue must belong to Attribute', function () {
    $product = Product::factory()->create();
    $attrA = Attribute::factory()->productScope()->select()->create(['name' => 'Origin']);
    $attrB = Attribute::factory()->productScope()->select()->create(['name' => 'Roast']);

    $valB = AttributeValue::factory()->create(['attribute_id' => $attrB->id]);

    expect(function () use ($product, $attrA, $valB) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $attrA->id,
            'attribute_value_id' => $valB->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('6. Inactive Attribute is rejected for new assignment', function () {
    $product = Product::factory()->create();
    $inactiveAttr = Attribute::factory()->productScope()->inactive()->text()->create();

    expect(function () use ($product, $inactiveAttr) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $inactiveAttr->id,
            'text_value' => 'Testing inactive attribute',
        ]);
    })->toThrow(ValidationException::class);
});

test('7. Soft-deleted Attribute is rejected for new assignment', function () {
    $product = Product::factory()->create();
    $trashedAttr = Attribute::factory()->productScope()->text()->create();
    $trashedAttr->delete();

    expect(function () use ($product, $trashedAttr) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $trashedAttr->id,
            'text_value' => 'Testing trashed attribute',
        ]);
    })->toThrow(ValidationException::class);
});

test('8. Inactive AttributeValue is rejected', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->select()->create();
    $inactiveVal = AttributeValue::factory()->inactive()->create(['attribute_id' => $attr->id]);

    expect(function () use ($product, $attr, $inactiveVal) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $inactiveVal->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('9. Soft-deleted AttributeValue is rejected', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->select()->create();
    $trashedVal = AttributeValue::factory()->create(['attribute_id' => $attr->id]);
    $trashedVal->delete();

    expect(function () use ($product, $attr, $trashedVal) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $trashedVal->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('10. Select supports exactly one value per product and attribute', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    expect($assign->id)->toBeGreaterThan(0);
});

test('11. Duplicate select assignment is rejected', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->select()->create();
    $val1 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'slug' => 'val-1']);
    $val2 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'slug' => 'val-2']);

    ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val1->id,
    ]);

    expect(function () use ($product, $attr, $val2) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $val2->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('12. Multiselect supports multiple different values', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->multiselect()->create();
    $val1 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Organic', 'slug' => 'organic']);
    $val2 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Fair Trade', 'slug' => 'fair-trade']);

    $assign1 = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val1->id,
    ]);

    $assign2 = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val2->id,
    ]);

    expect($assign1->id)->toBeGreaterThan(0)
        ->and($assign2->id)->toBeGreaterThan(0)
        ->and(ProductAttributeValue::where('product_id', $product->id)->count())->toBe(2);
});

test('13. Duplicate multiselect value is rejected', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->multiselect()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    expect(function () use ($product, $attr, $val) {
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $val->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('22. Product -> productAttributeValues and attributes relationships work', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->text()->create(['name' => 'Tasting Notes']);

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Fruity with honey finish',
    ]);

    expect($product->productAttributeValues)->toHaveCount(1)
        ->and($product->productAttributeValues->first()->id)->toBe($assign->id)
        ->and($product->assignedAttributes)->toHaveCount(1)
        ->and($product->assignedAttributes->first()->name)->toBe('Tasting Notes');
});

test('24. Attribute -> productAttributeValues relationship works', function () {
    $attr = Attribute::factory()->productScope()->text()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    ProductAttributeValue::create(['product_id' => $product1->id, 'attribute_id' => $attr->id, 'text_value' => 'Value 1']);
    ProductAttributeValue::create(['product_id' => $product2->id, 'attribute_id' => $attr->id, 'text_value' => 'Value 2']);

    expect($attr->productAttributeValues)->toHaveCount(2);
});

test('26. AttributeValue -> productAttributeValues relationship works', function () {
    $attr = Attribute::factory()->productScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);
    $product = Product::factory()->create();

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    expect($val->productAttributeValues)->toHaveCount(1)
        ->and($val->productAttributeValues->first()->id)->toBe($assign->id);
});

test('27. Attribute with assignments can be soft-deleted but cannot be hard-deleted', function () {
    $attr = Attribute::factory()->productScope()->text()->create();
    $product = Product::factory()->create();

    ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Direct assignment',
    ]);

    // Soft deletion is supported
    $attr->delete();
    expect($attr->fresh()->trashed())->toBeTrue();

    // Hard deletion is blocked by model guard
    expect(function () use ($attr) {
        $attr->forceDelete();
    })->toThrow(DomainException::class, 'Cannot force-delete this attribute because it has associated product or variant assignments.');
});

test('28. AttributeValue with assignments can be soft-deleted but cannot be hard-deleted', function () {
    $attr = Attribute::factory()->productScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);
    $product = Product::factory()->create();

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    // Soft deletion is supported and preserves assignment with non-null attribute_value_id
    $val->delete();
    expect($val->fresh()->trashed())->toBeTrue()
        ->and($assign->fresh()->attribute_value_id)->toBe($val->id);

    // Hard deletion is blocked by model guard to prevent nullOnDelete corruption
    expect(function () use ($val) {
        $val->forceDelete();
    })->toThrow(DomainException::class, 'Cannot force-delete this attribute value because it has associated product or variant assignments.');
});
