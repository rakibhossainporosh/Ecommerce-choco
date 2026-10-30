<?php

use App\Filament\Resources\Units\Pages\EditUnit;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Unit name is normalized and trimmed before storage', function () {
    $unit = Unit::create([
        'name' => '   Kilogram   ',
        'code' => 'kg',
    ]);

    expect($unit->name)->toBe('Kilogram')
        ->and($unit->fresh()->name)->toBe('Kilogram');
});

test('2. Duplicate active/non-deleted name is rejected', function () {
    Unit::create([
        'name' => 'Gram',
        'code' => 'g',
    ]);

    expect(function () {
        Unit::create([
            'name' => 'Gram',
            'code' => 'gm',
        ]);
    })->toThrow(ValidationException::class);
});

test('3. Duplicate name with whitespace normalization is rejected', function () {
    Unit::create([
        'name' => 'Liter',
        'code' => 'l',
    ]);

    expect(function () {
        Unit::create([
            'name' => '   Liter   ',
            'code' => 'ltr',
        ]);
    })->toThrow(ValidationException::class);
});

test('4. Soft-deleted unit name can be reused by a new active unit', function () {
    $oldUnit = Unit::create([
        'name' => 'Milliliter',
        'code' => 'ml-old',
    ]);

    $oldUnit->delete();
    expect($oldUnit->trashed())->toBeTrue();

    // Re-creating the unit with the same name should succeed because the previous one is soft-deleted
    $newUnit = Unit::create([
        'name' => 'Milliliter',
        'code' => 'ml-new',
    ]);

    expect($newUnit->exists)->toBeTrue()
        ->and($newUnit->name)->toBe('Milliliter')
        ->and($newUnit->trashed())->toBeFalse();
});

test('5. Unit code is normalized to lowercase and trimmed', function () {
    $unit = Unit::create([
        'name' => 'Pound',
        'code' => '  LB  ',
    ]);

    expect($unit->code)->toBe('lb')
        ->and($unit->fresh()->code)->toBe('lb');
});

test('6. Code uniqueness includes soft-deleted records', function () {
    $unit = Unit::create([
        'name' => 'Ounce',
        'code' => 'oz',
    ]);

    // Active duplicate code is rejected
    expect(function () {
        Unit::create([
            'name' => 'Fluid Ounce',
            'code' => 'oz',
        ]);
    })->toThrow(ValidationException::class);

    // Soft-delete unit
    $unit->delete();
    expect($unit->trashed())->toBeTrue();

    // Trying to create a new unit with the same code is STILL rejected (global code uniqueness)
    expect(function () {
        Unit::create([
            'name' => 'Fluid Ounce',
            'code' => 'oz',
        ]);
    })->toThrow(ValidationException::class);
});

test('7. Code format rejects invalid values with symbols and spaces', function () {
    expect(function () {
        Unit::create([
            'name' => 'Invalid Code One',
            'code' => 'kg / hr',
        ]);
    })->toThrow(ValidationException::class);

    expect(function () {
        Unit::create([
            'name' => 'Invalid Code Two',
            'code' => 'kg@weight#',
        ]);
    })->toThrow(ValidationException::class);
});

test('8. Updating the same Unit does not trigger a false duplicate error', function () {
    $unit = Unit::create([
        'name' => 'Piece',
        'code' => 'pcs',
        'description' => 'Single item.',
    ]);

    $unit->description = 'Updated single item description.';
    $unit->save();

    expect($unit->fresh()->description)->toBe('Updated single item description.')
        ->and($unit->fresh()->name)->toBe('Piece')
        ->and($unit->fresh()->code)->toBe('pcs');

    // Also test in Filament Edit form
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'name' => 'Piece',
            'code' => 'pcs',
            'description' => 'Saved via Livewire.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($unit->fresh()->description)->toBe('Saved via Livewire.');
});

test('9. Changing name does not automatically change code', function () {
    $unit = Unit::create([
        'name' => 'Box Container',
        'code' => 'bx',
    ]);

    $unit->name = 'Cardboard Box';
    $unit->save();

    expect($unit->fresh()->name)->toBe('Cardboard Box')
        ->and($unit->fresh()->code)->toBe('bx');
});

test('10. Inactive Unit remains manageable and editable', function () {
    $unit = Unit::create([
        'name' => 'Dormant Unit',
        'code' => 'drm',
        'is_active' => false,
        'sort_order' => 5,
    ]);

    $unit->sort_order = 10;
    $unit->save();

    expect($unit->fresh()->is_active)->toBeFalse()
        ->and($unit->fresh()->sort_order)->toBe(10);
});

test('11. canBeDeleted allows deletion and deleting hook executes domain guard', function () {
    $unit = Unit::create([
        'name' => 'Deletable Unit',
        'code' => 'del',
    ]);

    expect($unit->canBeDeleted())->toBeTrue();

    $unit->delete();

    expect($unit->fresh()->trashed())->toBeTrue();
});

test('12. No product dependency logic is assumed yet for Unit canBeDeleted', function () {
    $unit = Unit::create([
        'name' => 'Standalone Unit',
        'code' => 'std',
    ]);

    // Future Product-dependency check will be added here; currently returns true
    expect($unit->canBeDeleted())->toBeTrue();
});
