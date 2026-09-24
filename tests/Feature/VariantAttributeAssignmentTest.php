<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Models\VariantAttributeValue;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
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
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    VariantAttributeValue::truncate();
    ProductVariant::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    Product::truncate();
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    VariantAttributeValue::truncate();
    ProductVariant::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    Product::truncate();
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

test('14. Variant can receive variant-scope Attribute', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attribute = Attribute::factory()->variantScope()->text()->create();

    $assignment = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attribute->id,
        'text_value' => 'Pack of 3 Bars',
    ]);

    expect($assignment)->toBeInstanceOf(VariantAttributeValue::class)
        ->and($assignment->id)->toBeGreaterThan(0)
        ->and($assignment->product_variant_id)->toBe($variant->id)
        ->and($assignment->attribute_id)->toBe($attribute->id)
        ->and($assignment->text_value)->toBe('Pack of 3 Bars');

    $this->assertDatabaseHas('variant_attribute_values', [
        'id' => $assignment->id,
        'product_variant_id' => $variant->id,
        'attribute_id' => $attribute->id,
        'text_value' => 'Pack of 3 Bars',
    ]);
});

test('15. Variant cannot receive product-scope Attribute', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $productAttr = Attribute::factory()->productScope()->text()->create();

    expect(function () use ($variant, $productAttr) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $productAttr->id,
            'text_value' => 'Attempting product scope on variant',
        ]);
    })->toThrow(ValidationException::class);
});

test('16. Variant assignment belongs to correct ProductVariant', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->text()->create();

    $assignment1 = VariantAttributeValue::create([
        'product_variant_id' => $variant1->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Variant 1 Value',
    ]);

    $assignment2 = VariantAttributeValue::create([
        'product_variant_id' => $variant2->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Variant 2 Value',
    ]);

    expect($assignment1->productVariant->id)->toBe($variant1->id)
        ->and($assignment2->productVariant->id)->toBe($variant2->id)
        ->and($variant1->variantAttributeValues->first()->text_value)->toBe('Variant 1 Value')
        ->and($variant2->variantAttributeValues->first()->text_value)->toBe('Variant 2 Value');
});

test('17. Same AttributeValue ownership rules apply to Variant assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attrA = Attribute::factory()->variantScope()->select()->create(['name' => 'Size']);
    $attrB = Attribute::factory()->variantScope()->select()->create(['name' => 'Packaging']);

    $valB = AttributeValue::factory()->create(['attribute_id' => $attrB->id]);

    expect(function () use ($variant, $attrA, $valB) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $attrA->id,
            'attribute_value_id' => $valB->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('18. Same active and soft-delete rules apply to Variant assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    // Inactive Attribute
    $inactiveAttr = Attribute::factory()->variantScope()->inactive()->text()->create();
    expect(function () use ($variant, $inactiveAttr) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $inactiveAttr->id,
            'text_value' => 'Inactive Attr Test',
        ]);
    })->toThrow(ValidationException::class);

    // Soft-deleted Attribute
    $trashedAttr = Attribute::factory()->variantScope()->text()->create();
    $trashedAttr->delete();
    expect(function () use ($variant, $trashedAttr) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $trashedAttr->id,
            'text_value' => 'Trashed Attr Test',
        ]);
    })->toThrow(ValidationException::class);

    // Inactive AttributeValue
    $attr = Attribute::factory()->variantScope()->select()->create();
    $inactiveVal = AttributeValue::factory()->inactive()->create(['attribute_id' => $attr->id]);
    expect(function () use ($variant, $attr, $inactiveVal) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $inactiveVal->id,
        ]);
    })->toThrow(ValidationException::class);

    // Soft-deleted AttributeValue
    $trashedVal = AttributeValue::factory()->create(['attribute_id' => $attr->id]);
    $trashedVal->delete();
    expect(function () use ($variant, $attr, $trashedVal) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $trashedVal->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('19. Select duplicate is rejected for Variant assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->select()->create();
    $val1 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'slug' => 'size-small']);
    $val2 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'slug' => 'size-large']);

    VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val1->id,
    ]);

    expect(function () use ($variant, $attr, $val2) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $val2->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('20. Multiselect multiple values work for Variant assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->multiselect()->create();
    $val1 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Nut Free', 'slug' => 'nut-free']);
    $val2 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Soy Free', 'slug' => 'soy-free']);

    $assign1 = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val1->id,
    ]);

    $assign2 = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val2->id,
    ]);

    expect($assign1->id)->toBeGreaterThan(0)
        ->and($assign2->id)->toBeGreaterThan(0)
        ->and(VariantAttributeValue::where('product_variant_id', $variant->id)->count())->toBe(2);
});

test('21. Multiselect duplicate value is rejected for Variant assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->multiselect()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    expect(function () use ($variant, $attr, $val) {
        VariantAttributeValue::create([
            'product_variant_id' => $variant->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $val->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('23. ProductVariant -> variantAttributeValues and attributes relationships work', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->number()->create(['name' => 'Bar Weight Grams']);

    $assign = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'number_value' => 100,
    ]);

    expect($variant->variantAttributeValues)->toHaveCount(1)
        ->and($variant->variantAttributeValues->first()->id)->toBe($assign->id)
        ->and($variant->assignedAttributes)->toHaveCount(1)
        ->and($variant->assignedAttributes->first()->name)->toBe('Bar Weight Grams');
});

test('25. Attribute -> variantAttributeValues relationship works', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->text()->create();

    VariantAttributeValue::create(['product_variant_id' => $variant1->id, 'attribute_id' => $attr->id, 'text_value' => 'Val 1']);
    VariantAttributeValue::create(['product_variant_id' => $variant2->id, 'attribute_id' => $attr->id, 'text_value' => 'Val 2']);

    expect($attr->variantAttributeValues)->toHaveCount(2);
});

test('27. AttributeValue -> variantAttributeValues relationship works', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    $assign = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'attribute_value_id' => $val->id,
    ]);

    expect($val->variantAttributeValues)->toHaveCount(1)
        ->and($val->variantAttributeValues->first()->id)->toBe($assign->id);
});

test('28. Authorization: Admin, Manager, and Staff permission boundary on attribute assignments', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    // Admin has full access via Gate::before
    expect(Gate::forUser($admin)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.delete'))->toBeTrue();

    // Manager can view and update assignments via products.update, but cannot delete products
    expect(Gate::forUser($manager)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('products.update'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('products.delete'))->toBeFalse();

    // Staff has products.view only; cannot update or delete
    expect(Gate::forUser($staff)->allows('products.view'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('products.update'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('products.delete'))->toBeFalse();
});

test('29. Attribute with variant assignments can be soft-deleted but cannot be hard-deleted', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->text()->create();

    VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Variant assignment',
    ]);

    // Soft deletion is supported
    $attr->delete();
    expect($attr->fresh()->trashed())->toBeTrue();

    // Hard deletion is blocked by model guard
    expect(function () use ($attr) {
        $attr->forceDelete();
    })->toThrow(DomainException::class, 'Cannot force-delete this attribute because it has associated product or variant assignments.');
});

test('30. AttributeValue with variant assignments can be soft-deleted but cannot be hard-deleted', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr = Attribute::factory()->variantScope()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    $assign = VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
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
