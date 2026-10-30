<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
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
    AttributeValue::truncate();
    Attribute::truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    AttributeValue::truncate();
    Attribute::truncate();
    Schema::enableForeignKeyConstraints();
});

test('16. AttributeValue can be created with explicit attributes', function () {
    $attribute = Attribute::factory()->select()->create();

    $value = AttributeValue::create([
        'attribute_id' => $attribute->id,
        'name' => 'Dark Chocolate (70%)',
        'slug' => 'dark-chocolate-70',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($value)->toBeInstanceOf(AttributeValue::class)
        ->and($value->id)->toBeGreaterThan(0)
        ->and($value->attribute_id)->toBe($attribute->id)
        ->and($value->name)->toBe('Dark Chocolate (70%)')
        ->and($value->slug)->toBe('dark-chocolate-70')
        ->and($value->is_active)->toBeTrue()
        ->and($value->sort_order)->toBe(1);

    $this->assertDatabaseHas('attribute_values', [
        'id' => $value->id,
        'slug' => 'dark-chocolate-70',
    ]);
});

test('17. Attribute is required when creating AttributeValue', function () {
    // Missing attribute_id
    expect(function () {
        AttributeValue::create([
            'name' => 'Single Origin',
            'slug' => 'single-origin',
        ]);
    })->toThrow(ValidationException::class);

    // Non-existent attribute_id
    expect(function () {
        AttributeValue::create([
            'attribute_id' => 999999,
            'name' => 'Single Origin',
            'slug' => 'single-origin',
        ]);
    })->toThrow(ValidationException::class);
});

test('18. Soft-deleted Attribute cannot accept new values or updates', function () {
    $attribute = Attribute::factory()->select()->create();
    $attribute->delete();

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'New Value On Deleted Attr',
            'slug' => 'new-val-deleted-attr',
        ]);
    })->toThrow(ValidationException::class);
});

test('19. Name is trimmed on AttributeValue', function () {
    $attribute = Attribute::factory()->select()->create();

    $value = AttributeValue::create([
        'attribute_id' => $attribute->id,
        'name' => '   Single Origin Beans   ',
        'slug' => 'single-origin-beans',
    ]);

    expect($value->name)->toBe('Single Origin Beans');
});

test('20. Slug is normalized to lowercase and trimmed on AttributeValue', function () {
    $attribute = Attribute::factory()->select()->create();

    $value = AttributeValue::create([
        'attribute_id' => $attribute->id,
        'name' => 'Criollo',
        'slug' => '  CRIOLLO_BEAN  ',
    ]);

    expect($value->slug)->toBe('criollo_bean');
});

test('21. Slug must be alpha_dash compatible on AttributeValue', function () {
    $attribute = Attribute::factory()->select()->create();

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'Invalid Slug Value',
            'slug' => 'invalid slug with spaces',
        ]);
    })->toThrow(ValidationException::class);

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'Invalid Slug Value',
            'slug' => 'invalid@slug!symbols',
        ]);
    })->toThrow(ValidationException::class);
});

test('22. Same slug within same Attribute is rejected', function () {
    $attribute = Attribute::factory()->select()->create();

    AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'name' => 'Dry',
        'slug' => 'dry',
    ]);

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'Dry Variant',
            'slug' => 'dry',
        ]);
    })->toThrow(ValidationException::class);
});

test('23. Same slug across different Attributes is allowed', function () {
    $attribute1 = Attribute::factory()->select()->create(['name' => 'Hair Type', 'slug' => 'hair-type']);
    $attribute2 = Attribute::factory()->select()->create(['name' => 'Skin Type', 'slug' => 'skin-type']);

    $val1 = AttributeValue::create([
        'attribute_id' => $attribute1->id,
        'name' => 'Dry',
        'slug' => 'dry',
    ]);

    $val2 = AttributeValue::create([
        'attribute_id' => $attribute2->id,
        'name' => 'Dry',
        'slug' => 'dry',
    ]);

    expect($val1->id)->toBeGreaterThan(0)
        ->and($val2->id)->toBeGreaterThan(0)
        ->and($val1->slug)->toBe('dry')
        ->and($val2->slug)->toBe('dry')
        ->and($val1->attribute_id)->not->toBe($val2->attribute_id);
});

test('24. Soft-deleted value slug remains reserved within same Attribute', function () {
    $attribute = Attribute::factory()->select()->create();

    $val = AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'slug' => 'reserved-value-slug',
    ]);
    $val->delete();

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'Reusing Value Slug',
            'slug' => 'reserved-value-slug',
        ]);
    })->toThrow(ValidationException::class);
});

test('25. Inactive AttributeValue can exist but is not considered selectable', function () {
    $attribute = Attribute::factory()->select()->create();

    $inactiveVal = AttributeValue::factory()->inactive()->create([
        'attribute_id' => $attribute->id,
        'name' => 'Discontinued Option',
        'slug' => 'discontinued-option',
    ]);

    expect($inactiveVal->is_active)->toBeFalse()
        ->and($inactiveVal->isSelectable())->toBeFalse();

    $activeVal = AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'name' => 'Active Option',
        'slug' => 'active-option',
    ]);

    expect($activeVal->is_active)->toBeTrue()
        ->and($activeVal->isSelectable())->toBeTrue();
});

test('26. Attribute relationship works on AttributeValue', function () {
    $attribute = Attribute::factory()->select()->create(['name' => 'Roast Degree']);
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

    expect($value->attribute)->toBeInstanceOf(Attribute::class)
        ->and($value->attribute->id)->toBe($attribute->id)
        ->and($value->attribute->name)->toBe('Roast Degree');
});

test('27. sort_order cannot be negative on AttributeValue', function () {
    $attribute = Attribute::factory()->select()->create();

    expect(function () use ($attribute) {
        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'name' => 'Negative Sort Value',
            'slug' => 'negative-sort-value',
            'sort_order' => -5,
        ]);
    })->toThrow(ValidationException::class);
});

test('34. AttributeValue soft delete works', function () {
    $attribute = Attribute::factory()->select()->create();
    $val = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

    $val->delete();

    expect($val->fresh()->trashed())->toBeTrue()
        ->and(AttributeValue::count())->toBe(0)
        ->and(AttributeValue::withTrashed()->count())->toBe(1);

    $val->restore();
    expect($val->fresh()->trashed())->toBeFalse()
        ->and(AttributeValue::count())->toBe(1);
});

test('35. Soft-deleted attribute values are excluded from normal queries', function () {
    $attribute = Attribute::factory()->select()->create();

    $activeVal = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'slug' => 'active-val']);
    $deletedVal = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'slug' => 'deleted-val']);
    $deletedVal->delete();

    $results = AttributeValue::where('attribute_id', $attribute->id)->get();

    expect($results->pluck('id'))->toContain($activeVal->id)
        ->and($results->pluck('id'))->not->toContain($deletedVal->id);
});
