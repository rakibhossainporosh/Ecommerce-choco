<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\VariantAttributeValue;
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
    VariantAttributeValue::truncate();
    ProductAttributeValue::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    VariantAttributeValue::truncate();
    ProductAttributeValue::truncate();
    AttributeValue::truncate();
    Attribute::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

// =========================================================================
// 1. REQUIRED ATTRIBUTE ENFORCEMENT & ACTIVE-STATE VALIDATION
// =========================================================================

test('1. Required Product Attribute missing causes validation failure on activation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Storage Instructions',
    ]);

    expect(function () use ($product) {
        $product->update(['is_active' => true]);
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('2. Required Product Attribute present allows successful product activation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Storage Instructions',
    ]);

    ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $reqAttr->id,
        'text_value' => 'Store in a cool dry place',
    ]);

    $product->update(['is_active' => true]);

    expect($product->fresh()->is_active)->toBeTrue();
});

test('3. Required Variant Attribute missing causes validation failure on activation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->active()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    $reqAttr = Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Serving Size',
    ]);

    expect(function () use ($variant) {
        $variant->update(['is_active' => true]);
    })->toThrow(ValidationException::class);

    expect($variant->fresh()->is_active)->toBeFalse();
});

test('4. Required Variant Attribute present allows successful variant activation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->active()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    $reqAttr = Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Serving Size',
    ]);

    VariantAttributeValue::create([
        'product_variant_id' => $variant->id,
        'attribute_id' => $reqAttr->id,
        'text_value' => '30g',
    ]);

    $variant->update(['is_active' => true]);

    expect($variant->fresh()->is_active)->toBeTrue();
});

test('5. Inactive required Attribute is ignored during required attribute validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    // Required but INACTIVE
    Attribute::factory()->productScope()->text()->required()->inactive()->create([
        'name' => 'Inactive Required Attribute',
    ]);

    $product->update(['is_active' => true]);

    expect($product->fresh()->is_active)->toBeTrue();
});

test('6. Soft-deleted required Attribute is ignored during required attribute validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    // Required but TRASHED
    Attribute::factory()->productScope()->text()->required()->trashed()->create([
        'name' => 'Trashed Required Attribute',
    ]);

    $product->update(['is_active' => true]);

    expect($product->fresh()->is_active)->toBeTrue();
});

// =========================================================================
// 2. ATTRIBUTE STATE TRANSITIONS
// =========================================================================

test('7. Optional to required transition enforces required attribute on active product validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $attr = Attribute::factory()->productScope()->text()->create([
        'name' => 'Country of Cacao Origin',
        'is_required' => false,
    ]);

    // Initially optional: active state validation passes
    $product->validateActiveState();

    // Transition optional -> required: Attribute update succeeds without touching existing products
    $attr->update(['is_required' => true]);
    expect($attr->fresh()->is_required)->toBeTrue();

    // Now explicit active validation on the product requires the assignment
    expect(function () use ($product) {
        $product->validateActiveState();
    })->toThrow(ValidationException::class);

    // Assigning the attribute satisfies the requirement
    ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'text_value' => 'Madagascar',
    ]);

    $product->validateActiveState();
});

test('8. Required to optional transition removes requirement without deleting existing assignments', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $attr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Certification Code',
    ]);

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'text_value' => 'CERT-9988',
    ]);

    // Transition required -> optional
    $attr->update(['is_required' => false]);
    expect($attr->fresh()->is_required)->toBeFalse();

    // Existing assignment remains intact
    expect($assign->fresh()->text_value)->toBe('CERT-9988');

    // Syncing without this attribute now succeeds
    $product->syncAttributes([]);
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('9. Active to inactive transition preserves existing assignments and ignores required validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $attr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Seasonal Batch ID',
    ]);

    $assign = ProductAttributeValue::create([
        'product_id' => $product->id,
        'attribute_id' => $attr->id,
        'text_value' => 'BATCH-AUTUMN-2026',
    ]);

    // Transition active -> inactive
    $attr->update(['is_active' => false]);
    expect($attr->fresh()->is_active)->toBeFalse();

    // Existing assignment is preserved
    expect($assign->fresh()->text_value)->toBe('BATCH-AUTUMN-2026');

    // Required validation ignores inactive attribute
    $product->productAttributeValues()->delete();
    $product->validateActiveState();
});

test('10. Inactive to active transition re-enables assignment selection and requirement', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $attr = Attribute::factory()->productScope()->text()->required()->inactive()->create([
        'name' => 'Fair Trade ID',
    ]);

    // Transition inactive -> active
    $attr->update(['is_active' => true]);
    expect($attr->fresh()->is_active)->toBeTrue();

    // Now required on active product
    expect(function () use ($product) {
        $product->validateActiveState();
    })->toThrow(ValidationException::class);
});

// =========================================================================
// 3. SYNCHRONIZATION TESTS
// =========================================================================

test('11. Scalar assignment replacement correctly updates existing assignment', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->text()->create();

    // Initial assignment
    $product->syncAttributes([
        ['attribute_id' => $attr->id, 'text_value' => 'Initial Tasting Notes'],
    ]);

    expect($product->productAttributeValues)->toHaveCount(1)
        ->and($product->productAttributeValues->first()->text_value)->toBe('Initial Tasting Notes');

    // Replace
    $product->syncAttributes([
        ['attribute_id' => $attr->id, 'text_value' => 'Updated Tasting Notes: Berry finish'],
    ]);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(1)
        ->and($product->productAttributeValues->first()->text_value)->toBe('Updated Tasting Notes: Berry finish');
});

test('12. Multiselect replacement correctly replaces old values with new values', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->multiselect()->create();
    $valCotton = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Cotton', 'slug' => 'cotton']);
    $valSilk = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Silk', 'slug' => 'silk']);
    $valWool = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Wool', 'slug' => 'wool']);

    // Initial: Cotton + Silk
    $product->syncAttributes([
        ['attribute_id' => $attr->id, 'attribute_value_id' => $valCotton->id],
        ['attribute_id' => $attr->id, 'attribute_value_id' => $valSilk->id],
    ]);

    expect($product->productAttributeValues)->toHaveCount(2)
        ->and($product->productAttributeValues->pluck('attribute_value_id')->all())
        ->toEqualCanonicalizing([$valCotton->id, $valSilk->id]);

    // Replace with: Cotton + Wool (Silk removed)
    $product->syncAttributes([
        ['attribute_id' => $attr->id, 'attribute_value_id' => $valCotton->id],
        ['attribute_id' => $attr->id, 'attribute_value_id' => $valWool->id],
    ]);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(2)
        ->and($product->productAttributeValues->pluck('attribute_value_id')->all())
        ->toEqualCanonicalizing([$valCotton->id, $valWool->id]);
});

test('13. Optional assignment removal succeeds and cleans up assignment rows', function () {
    $product = Product::factory()->create();
    $attr = Attribute::factory()->productScope()->text()->create(['is_required' => false]);

    $product->syncAttributes([
        ['attribute_id' => $attr->id, 'text_value' => 'Temporary Note'],
    ]);

    expect($product->productAttributeValues)->toHaveCount(1);

    // Sync empty list
    $product->syncAttributes([]);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('14. Required assignment removal fails and transaction rolls back', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Mandatory Storage',
    ]);
    $optAttr = Attribute::factory()->productScope()->text()->create([
        'name' => 'Optional Pairing',
        'is_required' => false,
    ]);

    // Initial valid state
    $product->syncAttributes([
        ['attribute_id' => $reqAttr->id, 'text_value' => 'Keep Frozen'],
        ['attribute_id' => $optAttr->id, 'text_value' => 'Pairs with Espresso'],
    ]);

    expect($product->productAttributeValues)->toHaveCount(2);

    // Attempt to sync omitting the required attribute
    expect(function () use ($product, $optAttr) {
        $product->syncAttributes([
            ['attribute_id' => $optAttr->id, 'text_value' => 'Pairs with Wine'],
        ]);
    })->toThrow(ValidationException::class);

    // Database state must be completely rolled back / unchanged
    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(2)
        ->and($product->productAttributeValues->where('attribute_id', $reqAttr->id)->first()->text_value)->toBe('Keep Frozen')
        ->and($product->productAttributeValues->where('attribute_id', $optAttr->id)->first()->text_value)->toBe('Pairs with Espresso');
});

test('15. Invalid AttributeValue rolls back the entire sync transaction', function () {
    $product = Product::factory()->create();
    $validAttr = Attribute::factory()->productScope()->text()->create();
    $selectAttr = Attribute::factory()->productScope()->select()->create();
    $otherAttr = Attribute::factory()->productScope()->select()->create();

    // Value belonging to a DIFFERENT attribute
    $foreignVal = AttributeValue::factory()->create(['attribute_id' => $otherAttr->id]);

    expect(function () use ($product, $validAttr, $selectAttr, $foreignVal) {
        $product->syncAttributes([
            ['attribute_id' => $validAttr->id, 'text_value' => 'Valid Value'],
            ['attribute_id' => $selectAttr->id, 'attribute_value_id' => $foreignVal->id],
        ]);
    })->toThrow(ValidationException::class);

    // Assert that Valid Value was not saved
    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('16. Wrong scope rolls back the entire sync transaction', function () {
    $product = Product::factory()->create();
    $productAttr = Attribute::factory()->productScope()->text()->create();
    $variantAttr = Attribute::factory()->variantScope()->text()->create();

    expect(function () use ($product, $productAttr, $variantAttr) {
        $product->syncAttributes([
            ['attribute_id' => $productAttr->id, 'text_value' => 'Valid Product Text'],
            ['attribute_id' => $variantAttr->id, 'text_value' => 'Invalid Variant Text on Product'],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('17. Duplicate multiselect value in payload fails and rolls back', function () {
    $product = Product::factory()->create();
    $multiAttr = Attribute::factory()->productScope()->multiselect()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $multiAttr->id]);

    expect(function () use ($product, $multiAttr, $val) {
        $product->syncAttributes([
            ['attribute_id' => $multiAttr->id, 'attribute_value_id' => $val->id],
            ['attribute_id' => $multiAttr->id, 'attribute_value_id' => $val->id],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('18. Inactive Attribute is rejected for new assignment and rolls back', function () {
    $product = Product::factory()->create();
    $inactiveAttr = Attribute::factory()->productScope()->text()->inactive()->create();

    expect(function () use ($product, $inactiveAttr) {
        $product->syncAttributes([
            ['attribute_id' => $inactiveAttr->id, 'text_value' => 'Cannot assign inactive'],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('19. Soft-deleted Attribute is rejected for new assignment and rolls back', function () {
    $product = Product::factory()->create();
    $trashedAttr = Attribute::factory()->productScope()->text()->trashed()->create();

    expect(function () use ($product, $trashedAttr) {
        $product->syncAttributes([
            ['attribute_id' => $trashedAttr->id, 'text_value' => 'Cannot assign deleted'],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('20. Inactive AttributeValue is rejected for new assignment and rolls back', function () {
    $product = Product::factory()->create();
    $selectAttr = Attribute::factory()->productScope()->select()->create();
    $inactiveVal = AttributeValue::factory()->inactive()->create(['attribute_id' => $selectAttr->id]);

    expect(function () use ($product, $selectAttr, $inactiveVal) {
        $product->syncAttributes([
            ['attribute_id' => $selectAttr->id, 'attribute_value_id' => $inactiveVal->id],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

test('21. Soft-deleted AttributeValue is rejected for new assignment and rolls back', function () {
    $product = Product::factory()->create();
    $selectAttr = Attribute::factory()->productScope()->select()->create();
    $trashedVal = AttributeValue::factory()->trashed()->create(['attribute_id' => $selectAttr->id]);

    expect(function () use ($product, $selectAttr, $trashedVal) {
        $product->syncAttributes([
            ['attribute_id' => $selectAttr->id, 'attribute_value_id' => $trashedVal->id],
        ]);
    })->toThrow(ValidationException::class);

    $product->refresh();
    expect($product->productAttributeValues)->toHaveCount(0);
});

// =========================================================================
// 4. ATOMICITY & VARIANT SYNC TESTS
// =========================================================================

test('22. Atomicity test: Valid assignment A and Invalid assignment B results in zero persisted records', function () {
    $product = Product::factory()->create();
    $validAttr = Attribute::factory()->productScope()->text()->create();

    expect(function () use ($product, $validAttr) {
        $product->syncAttributes([
            ['attribute_id' => $validAttr->id, 'text_value' => 'Valid Persist Candidate'],
            ['attribute_id' => 999999, 'text_value' => 'Non-existent attribute'],
        ]);
    })->toThrow(ValidationException::class);

    // Verify persisted database state: NO rows created
    $this->assertDatabaseMissing('product_attribute_values', [
        'product_id' => $product->id,
        'attribute_id' => $validAttr->id,
    ]);

    expect($product->fresh()->productAttributeValues)->toHaveCount(0);
});

test('23. Variant syncAttributes works atomically and validates variant scope', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
    ]);

    $variantAttr = Attribute::factory()->variantScope()->number()->create();

    $variant->syncAttributes([
        ['attribute_id' => $variantAttr->id, 'number_value' => 250],
    ]);

    expect($variant->variantAttributeValues)->toHaveCount(1)
        ->and((float) $variant->variantAttributeValues->first()->number_value)->toBe(250.0);

    // Reject product-scoped attribute on variant
    $productAttr = Attribute::factory()->productScope()->text()->create();
    expect(function () use ($variant, $productAttr) {
        $variant->syncAttributes([
            ['attribute_id' => $productAttr->id, 'text_value' => 'Product scope on variant'],
        ]);
    })->toThrow(ValidationException::class);

    // Original number_value preserved
    $variant->refresh();
    expect($variant->variantAttributeValues)->toHaveCount(1)
        ->and((float) $variant->variantAttributeValues->first()->number_value)->toBe(250.0);
});

test('24. Inactive Product allows saving incomplete/draft attribute state', function () {
    $product = Product::factory()->inactive()->create();
    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Draft Required Attr',
    ]);

    // Inactive product can sync without required attribute
    $product->syncAttributes([]);

    expect($product->fresh()->productAttributeValues)->toHaveCount(0);
});

test('25. Product activate() helper enforces active state validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqAttr = Attribute::factory()->productScope()->text()->required()->create([
        'name' => 'Storage Mandatory',
    ]);

    // Fails because required attribute is missing
    expect(function () use ($product) {
        $product->activate();
    })->toThrow(ValidationException::class);

    expect($product->fresh()->is_active)->toBeFalse();

    // Assign required attribute and activate
    $product->syncAttributes([
        ['attribute_id' => $reqAttr->id, 'text_value' => 'Refrigerate after opening'],
    ]);

    $product->activate();
    expect($product->fresh()->is_active)->toBeTrue();

    // Deactivate
    $product->deactivate();
    expect($product->fresh()->is_active)->toBeFalse();
});

test('26. Variant activate() helper enforces active state validation', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->active()->create();
    $defaultVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    $reqAttr = Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Roast Profile',
    ]);

    // Fails because required attribute is missing
    expect(function () use ($variant) {
        $variant->activate();
    })->toThrow(ValidationException::class);

    expect($variant->fresh()->is_active)->toBeFalse();

    // Assign required attribute and activate
    $variant->syncAttributes([
        ['attribute_id' => $reqAttr->id, 'text_value' => 'Medium Dark'],
    ]);

    $variant->activate();
    expect($variant->fresh()->is_active)->toBeTrue();

    // Deactivate
    $variant->deactivate();
    expect($variant->fresh()->is_active)->toBeFalse();
});

test('27. Product active state validation requires at least one active variant', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();
    // No variants at all
    expect(function () use ($product) {
        $product->update(['is_active' => true]);
    })->toThrow(ValidationException::class);

    // Inactive variant only
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    expect(function () use ($product) {
        $product->update(['is_active' => true]);
    })->toThrow(ValidationException::class);
});

test('28. Product active state validation requires exactly one active default variant', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();

    // Active variant but non-default
    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    expect(function () use ($product) {
        $product->update(['is_active' => true]);
    })->toThrow(ValidationException::class);
});

test('29. Product activation fails if any active variant has a missing required variant attribute', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->inactive()->create();

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqVarAttr = Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Bean Roast Level',
    ]);

    // Product activation must fail because active variant is missing its required attribute
    expect(function () use ($product) {
        $product->update(['is_active' => true]);
    })->toThrow(ValidationException::class);

    // Assigning the required attribute to the variant satisfies the requirement
    $variant->syncAttributes([
        ['attribute_id' => $reqVarAttr->id, 'text_value' => 'Dark Roast 85%'],
    ]);

    $product->update(['is_active' => true]);
    expect($product->fresh()->is_active)->toBeTrue();
});

test('30. Inactive Variant allows saving incomplete attribute state', function () {
    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    Attribute::factory()->variantScope()->text()->required()->create([
        'name' => 'Draft Variant Attr',
    ]);

    // Inactive variant can sync without required attribute
    $variant->syncAttributes([]);

    expect($variant->fresh()->variantAttributeValues)->toHaveCount(0);
});
