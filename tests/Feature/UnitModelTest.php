<?php

use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Unit::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
});

test('1. Unit can be created with explicit attributes', function () {
    $unit = Unit::create([
        'name' => 'Kilogram',
        'code' => 'kg',
        'description' => 'Base metric unit of mass.',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    expect($unit)->toBeInstanceOf(Unit::class)
        ->and($unit->id)->toBeGreaterThan(0)
        ->and($unit->name)->toBe('Kilogram')
        ->and($unit->code)->toBe('kg')
        ->and($unit->description)->toBe('Base metric unit of mass.')
        ->and($unit->is_active)->toBeTrue()
        ->and($unit->sort_order)->toBe(10);

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'code' => 'kg',
    ]);
});

test('2. Unit defaults: is_active defaults to true and sort_order defaults to 0', function () {
    $unit = Unit::create([
        'name' => 'Gram',
        'code' => 'g',
    ]);

    $unit->refresh();

    expect($unit->is_active)->toBeTrue()
        ->and($unit->sort_order)->toBe(0)
        ->and($unit->description)->toBeNull();
});

test('3. Code database uniqueness is enforced by the database', function () {
    Unit::create([
        'name' => 'Piece',
        'code' => 'pcs',
    ]);

    expect(function () {
        // Bypass model-level validation to prove database-level unique constraint
        DB::table('units')->insert([
            'name' => 'Pieces Duplicate',
            'code' => 'pcs',
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('4. Soft delete marks unit as deleted without removing from database', function () {
    $unit = Unit::create([
        'name' => 'Liter',
        'code' => 'l',
    ]);

    $unit->delete();

    expect($unit->trashed())->toBeTrue();

    $this->assertSoftDeleted('units', [
        'id' => $unit->id,
        'code' => 'l',
    ]);
});

test('5. Trashed unit is excluded from default queries', function () {
    $unit = Unit::create([
        'name' => 'Milliliter',
        'code' => 'ml',
    ]);

    $unit->delete();

    expect(Unit::find($unit->id))->toBeNull()
        ->and(Unit::where('code', 'ml')->first())->toBeNull();
});

test('6. Trashed unit can be retrieved using withTrashed', function () {
    $unit = Unit::create([
        'name' => 'Box',
        'code' => 'box',
    ]);

    $unit->delete();

    $retrieved = Unit::withTrashed()->find($unit->id);

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->id)->toBe($unit->id)
        ->and($retrieved->trashed())->toBeTrue();
});

test('7. Unit can be restored', function () {
    $unit = Unit::create([
        'name' => 'Pack',
        'code' => 'pk',
    ]);

    $unit->delete();
    expect($unit->trashed())->toBeTrue();

    $unit->restore();

    expect($unit->fresh()->trashed())->toBeFalse();
    $this->assertNotSoftDeleted('units', [
        'id' => $unit->id,
    ]);
});

test('8. Unit can be permanently deleted using forceDeleteQuietly', function () {
    $unit = Unit::create([
        'name' => 'Bottle',
        'code' => 'btl',
    ]);

    $unitId = $unit->id;
    $unit->forceDeleteQuietly();

    expect(Unit::withTrashed()->find($unitId))->toBeNull();
    $this->assertDatabaseMissing('units', [
        'id' => $unitId,
    ]);
});

test('9. Casts correctly cast attributes to native types', function () {
    $unit = Unit::create([
        'name' => 'Dozen',
        'code' => 'doz',
        'is_active' => 1,
        'sort_order' => '5',
    ]);

    $unit->refresh();

    expect($unit->is_active)->toBeTrue()
        ->and($unit->is_active)->toBeBool()
        ->and($unit->sort_order)->toBe(5)
        ->and($unit->sort_order)->toBeInt();
});

test('10. UnitFactory creates valid units with states', function () {
    $unit = Unit::factory()->create();

    expect($unit)->toBeInstanceOf(Unit::class)
        ->and($unit->name)->not->toBeEmpty()
        ->and($unit->code)->not->toBeEmpty()
        ->and($unit->is_active)->toBeTrue();

    $inactiveUnit = Unit::factory()->inactive()->create();
    expect($inactiveUnit->is_active)->toBeFalse();

    $trashedUnit = Unit::factory()->trashed()->create();
    expect($trashedUnit->trashed())->toBeTrue();
});
