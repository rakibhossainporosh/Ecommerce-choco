<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
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

test('1. Attribute can be created with explicit attributes', function () {
    $attribute = Attribute::create([
        'name' => 'Cocoa Percentage',
        'slug' => 'cocoa-percentage',
        'type' => Attribute::ATTRIBUTE_TYPE_NUMBER,
        'scope' => Attribute::SCOPE_PRODUCT,
        'description' => 'The percentage of cocoa solids.',
        'is_required' => true,
        'is_active' => true,
        'sort_order' => 5,
    ]);

    expect($attribute)->toBeInstanceOf(Attribute::class)
        ->and($attribute->id)->toBeGreaterThan(0)
        ->and($attribute->name)->toBe('Cocoa Percentage')
        ->and($attribute->slug)->toBe('cocoa-percentage')
        ->and($attribute->type)->toBe('number')
        ->and($attribute->scope)->toBe('product')
        ->and($attribute->description)->toBe('The percentage of cocoa solids.')
        ->and($attribute->is_required)->toBeTrue()
        ->and($attribute->is_active)->toBeTrue()
        ->and($attribute->sort_order)->toBe(5);

    $this->assertDatabaseHas('attributes', [
        'id' => $attribute->id,
        'slug' => 'cocoa-percentage',
    ]);
});

test('2. Required fields are enforced by validation', function () {
    // Missing name
    expect(function () {
        Attribute::create([
            'name' => '',
            'slug' => 'valid-slug',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);

    // Missing slug
    expect(function () {
        Attribute::create([
            'name' => 'Valid Name',
            'slug' => '',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);

    // Missing type
    expect(function () {
        Attribute::create([
            'name' => 'Valid Name',
            'slug' => 'valid-slug',
            'type' => '',
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);

    // Missing scope
    expect(function () {
        Attribute::create([
            'name' => 'Valid Name',
            'slug' => 'valid-slug',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => '',
        ]);
    })->toThrow(ValidationException::class);
});

test('3. Name is trimmed', function () {
    $attribute = Attribute::create([
        'name' => '   Flavor Notes   ',
        'slug' => 'flavor-notes',
        'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
        'scope' => Attribute::SCOPE_PRODUCT,
    ]);

    expect($attribute->name)->toBe('Flavor Notes');
});

test('4. Slug is normalized to lowercase and trimmed', function () {
    $attribute = Attribute::create([
        'name' => 'Bean Origin',
        'slug' => '  BEAN_ORIGIN  ',
        'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
        'scope' => Attribute::SCOPE_PRODUCT,
    ]);

    expect($attribute->slug)->toBe('bean_origin');
});

test('5. Slug must be alpha_dash compatible', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Invalid Slug',
            'slug' => 'invalid slug with spaces',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);

    expect(function () {
        Attribute::create([
            'name' => 'Invalid Slug Symbols',
            'slug' => 'invalid@slug!symbols',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);
});

test('6. Slug is globally unique among active attributes', function () {
    Attribute::factory()->create(['slug' => 'sweetness-level']);

    expect(function () {
        Attribute::factory()->create([
            'name' => 'Another Attribute',
            'slug' => 'sweetness-level',
        ]);
    })->toThrow(ValidationException::class);
});

test('7. Soft-deleted slug remains reserved', function () {
    $attr = Attribute::factory()->create(['slug' => 'reserved-slug']);
    $attr->delete();

    expect(function () {
        Attribute::factory()->create([
            'name' => 'Reusing Slug',
            'slug' => 'reserved-slug',
        ]);
    })->toThrow(ValidationException::class);
});

test('8. Duplicate non-deleted name is rejected', function () {
    Attribute::factory()->create(['name' => 'Roast Profile', 'slug' => 'roast-profile-1']);

    expect(function () {
        Attribute::factory()->create([
            'name' => 'Roast Profile',
            'slug' => 'roast-profile-2',
        ]);
    })->toThrow(ValidationException::class);
});

test('9. Soft-deleted name can be recreated with a new unique slug', function () {
    $attr = Attribute::factory()->create([
        'name' => 'Dietary Certification',
        'slug' => 'dietary-cert-old',
    ]);
    $attr->delete();

    $newAttr = Attribute::factory()->create([
        'name' => 'Dietary Certification',
        'slug' => 'dietary-cert-new',
    ]);

    expect($newAttr)->toBeInstanceOf(Attribute::class)
        ->and($newAttr->name)->toBe('Dietary Certification')
        ->and($newAttr->slug)->toBe('dietary-cert-new');
});

test('10. Type must be one of supported types', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Invalid Type Attr',
            'slug' => 'invalid-type-attr',
            'type' => 'unsupported_type',
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);
});

test('11. Scope must be product or variant', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Invalid Scope Attr',
            'slug' => 'invalid-scope-attr',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => 'order',
        ]);
    })->toThrow(ValidationException::class);
});

test('12. is_required casts correctly to boolean', function () {
    $attr = Attribute::factory()->create(['is_required' => 1]);
    expect($attr->is_required)->toBeTrue();

    $attr2 = Attribute::factory()->create(['is_required' => 0]);
    expect($attr2->is_required)->toBeFalse();
});

test('13. is_active casts correctly to boolean', function () {
    $attr = Attribute::factory()->create(['is_active' => 1]);
    expect($attr->is_active)->toBeTrue();

    $attr2 = Attribute::factory()->create(['is_active' => 0]);
    expect($attr2->is_active)->toBeFalse();
});

test('14. sort_order cannot be negative', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Negative Sort',
            'slug' => 'negative-sort',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
            'sort_order' => -1,
        ]);
    })->toThrow(ValidationException::class);
});

test('15. AttributeValue relationship works', function () {
    $attr = Attribute::factory()->select()->create();
    $val1 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Dark', 'slug' => 'dark']);
    $val2 = AttributeValue::factory()->create(['attribute_id' => $attr->id, 'name' => 'Milk', 'slug' => 'milk']);

    expect($attr->values)->toHaveCount(2)
        ->and($attr->values->pluck('slug')->all())->toContain('dark', 'milk');
});

test('16. Attribute deletion is rejected when attribute values exist', function () {
    $attr = Attribute::factory()->select()->create();
    AttributeValue::factory()->create(['attribute_id' => $attr->id]);

    expect(function () use ($attr) {
        $attr->delete();
    })->toThrow(DomainException::class);

    expect($attr->fresh()->trashed())->toBeFalse();
});

test('17. Attribute deletion succeeds when no attribute values exist', function () {
    $attr = Attribute::factory()->create();
    $attr->delete();

    expect($attr->trashed())->toBeTrue();
});

test('28. All six attribute types are accepted', function () {
    $types = [
        Attribute::ATTRIBUTE_TYPE_TEXT,
        Attribute::ATTRIBUTE_TYPE_TEXTAREA,
        Attribute::ATTRIBUTE_TYPE_NUMBER,
        Attribute::ATTRIBUTE_TYPE_BOOLEAN,
        Attribute::ATTRIBUTE_TYPE_SELECT,
        Attribute::ATTRIBUTE_TYPE_MULTISELECT,
    ];

    foreach ($types as $index => $type) {
        $attr = Attribute::create([
            'name' => 'Type Test '.$index,
            'slug' => 'type-test-'.$index,
            'type' => $type,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);

        expect($attr->type)->toBe($type);
    }
});

test('29. Invalid type is rejected', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Bad Type',
            'slug' => 'bad-type',
            'type' => 'json_object',
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);
});

test('30. Product scope is accepted', function () {
    $attr = Attribute::factory()->productScope()->create();
    expect($attr->scope)->toBe(Attribute::SCOPE_PRODUCT);
});

test('31. Variant scope is accepted', function () {
    $attr = Attribute::factory()->variantScope()->create();
    expect($attr->scope)->toBe(Attribute::SCOPE_VARIANT);
});

test('32. Invalid scope is rejected', function () {
    expect(function () {
        Attribute::create([
            'name' => 'Bad Scope',
            'slug' => 'bad-scope',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => 'cart',
        ]);
    })->toThrow(ValidationException::class);
});

test('33. Attribute soft delete works', function () {
    $attr = Attribute::factory()->create();
    $attr->delete();

    expect($attr->fresh()->trashed())->toBeTrue()
        ->and(Attribute::count())->toBe(0)
        ->and(Attribute::withTrashed()->count())->toBe(1);

    $attr->restore();
    expect($attr->fresh()->trashed())->toBeFalse()
        ->and(Attribute::count())->toBe(1);
});

test('35. Soft-deleted attributes are excluded from normal queries', function () {
    $active = Attribute::factory()->create(['name' => 'Active Attr', 'slug' => 'active-attr']);
    $deleted = Attribute::factory()->create(['name' => 'Deleted Attr', 'slug' => 'deleted-attr']);
    $deleted->delete();

    $results = Attribute::all();

    expect($results->pluck('id'))->toContain($active->id)
        ->and($results->pluck('id'))->not->toContain($deleted->id);
});

test('36. Slug uniqueness still considers soft-deleted records', function () {
    $attr = Attribute::factory()->create(['slug' => 'unique-slug-test']);
    $attr->delete();

    expect(function () {
        Attribute::create([
            'name' => 'New Name Same Slug',
            'slug' => 'unique-slug-test',
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    })->toThrow(ValidationException::class);
});
