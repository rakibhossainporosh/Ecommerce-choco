<?php

use App\Models\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Brand::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
});

test('1. Brand can be created with explicit attributes', function () {
    $brand = Brand::create([
        'name' => 'Lindt & Sprüngli',
        'slug' => 'lindt-sprungli',
        'description' => 'Swiss master chocolatier since 1845.',
        'logo' => 'brands/logos/lindt.png',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    expect($brand)->toBeInstanceOf(Brand::class)
        ->and($brand->id)->toBeGreaterThan(0)
        ->and($brand->name)->toBe('Lindt & Sprüngli')
        ->and($brand->slug)->toBe('lindt-sprungli')
        ->and($brand->description)->toBe('Swiss master chocolatier since 1845.')
        ->and($brand->logo)->toBe('brands/logos/lindt.png')
        ->and($brand->is_active)->toBeTrue()
        ->and($brand->sort_order)->toBe(10);

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'slug' => 'lindt-sprungli',
    ]);
});

test('2. Brand defaults: is_active defaults to true and sort_order defaults to 0', function () {
    $brand = Brand::create([
        'name' => 'Godiva',
        'slug' => 'godiva',
    ]);

    $brand->refresh();

    expect($brand->is_active)->toBeTrue()
        ->and($brand->sort_order)->toBe(0)
        ->and($brand->description)->toBeNull()
        ->and($brand->logo)->toBeNull();
});

test('3. Slug uniqueness is enforced by the database', function () {
    Brand::create([
        'name' => 'Ferrero',
        'slug' => 'ferrero',
    ]);

    expect(function () {
        // Bypass model-level validation to prove database-level unique constraint
        DB::table('brands')->insert([
            'name' => 'Ferrero Duplicate',
            'slug' => 'ferrero',
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('4. Soft delete marks brand as deleted without removing from database', function () {
    $brand = Brand::create([
        'name' => 'Cadbury',
        'slug' => 'cadbury',
    ]);

    $brand->delete();

    expect($brand->trashed())->toBeTrue();

    $this->assertSoftDeleted('brands', [
        'id' => $brand->id,
        'slug' => 'cadbury',
    ]);
});

test('5. Trashed brand is excluded from default queries', function () {
    $brand = Brand::create([
        'name' => 'Toblerone',
        'slug' => 'toblerone',
    ]);

    $brand->delete();

    expect(Brand::find($brand->id))->toBeNull()
        ->and(Brand::where('slug', 'toblerone')->first())->toBeNull();
});

test('6. Trashed brand can be retrieved using withTrashed', function () {
    $brand = Brand::create([
        'name' => 'Ghirardelli',
        'slug' => 'ghirardelli',
    ]);

    $brand->delete();

    $retrieved = Brand::withTrashed()->find($brand->id);

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->id)->toBe($brand->id)
        ->and($retrieved->trashed())->toBeTrue();
});

test('7. Brand can be restored', function () {
    $brand = Brand::create([
        'name' => 'Nestle',
        'slug' => 'nestle',
    ]);

    $brand->delete();
    expect($brand->trashed())->toBeTrue();

    $brand->restore();

    expect($brand->fresh()->trashed())->toBeFalse();
    $this->assertNotSoftDeleted('brands', [
        'id' => $brand->id,
    ]);
});

test('8. Brand can be permanently deleted using forceDeleteQuietly', function () {
    $brand = Brand::create([
        'name' => 'Milka',
        'slug' => 'milka',
    ]);

    $brandId = $brand->id;
    $brand->forceDeleteQuietly();

    expect(Brand::withTrashed()->find($brandId))->toBeNull();
    $this->assertDatabaseMissing('brands', [
        'id' => $brandId,
    ]);
});

test('9. Casts correctly cast attributes to native types', function () {
    $brand = Brand::create([
        'name' => 'Valrhona',
        'slug' => 'valrhona',
        'is_active' => 1,
        'sort_order' => '5',
    ]);

    $brand->refresh();

    expect($brand->is_active)->toBeTrue()
        ->and($brand->is_active)->toBeBool()
        ->and($brand->sort_order)->toBe(5)
        ->and($brand->sort_order)->toBeInt();
});

test('10. BrandFactory creates valid brands', function () {
    $brand = Brand::factory()->create();

    expect($brand)->toBeInstanceOf(Brand::class)
        ->and($brand->name)->not->toBeEmpty()
        ->and($brand->slug)->not->toBeEmpty()
        ->and($brand->is_active)->toBeTrue();

    $inactiveBrand = Brand::factory()->inactive()->create();
    expect($inactiveBrand->is_active)->toBeFalse();

    $trashedBrand = Brand::factory()->trashed()->create();
    expect($trashedBrand->trashed())->toBeTrue();
});
