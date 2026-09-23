<?php

use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Models\Brand;
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
    Brand::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Brand can be created', function () {
    $brand = Brand::create([
        'name' => 'Patchi',
        'slug' => 'patchi',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($brand->exists)->toBeTrue()
        ->and($brand->name)->toBe('Patchi')
        ->and($brand->slug)->toBe('patchi');
});

test('2. Default is_active is true', function () {
    $brand = Brand::create([
        'name' => 'Leonidas',
        'slug' => 'leonidas',
    ]);

    $brand->refresh();

    expect($brand->is_active)->toBeTrue();
});

test('3. Default sort_order is 0', function () {
    $brand = Brand::create([
        'name' => 'Neuhaus',
        'slug' => 'neuhaus',
    ]);

    $brand->refresh();

    expect($brand->sort_order)->toBe(0);
});

test('4. Name is globally unique among non-deleted brands', function () {
    Brand::create([
        'name' => 'Godiva',
        'slug' => 'godiva',
    ]);

    expect(function () {
        Brand::create([
            'name' => 'Godiva',
            'slug' => 'godiva-different-slug',
        ]);
    })->toThrow(ValidationException::class);
});

test('5. Duplicate name with whitespace normalization is rejected', function () {
    Brand::create([
        'name' => 'Teuscher',
        'slug' => 'teuscher',
    ]);

    expect(function () {
        Brand::create([
            'name' => '   Teuscher   ',
            'slug' => 'teuscher-2',
        ]);
    })->toThrow(ValidationException::class);
});

test('6. Same name is allowed after previous Brand is soft deleted', function () {
    $oldBrand = Brand::create([
        'name' => 'Sprüngli',
        'slug' => 'sprungli-old',
    ]);

    $oldBrand->delete();
    expect($oldBrand->trashed())->toBeTrue();

    // Re-creating the brand with the same name should succeed because the old one is soft-deleted
    $newBrand = Brand::create([
        'name' => 'Sprüngli',
        'slug' => 'sprungli-new',
    ]);

    expect($newBrand->exists)->toBeTrue()
        ->and($newBrand->name)->toBe('Sprüngli')
        ->and($newBrand->trashed())->toBeFalse();
});

test('7. Slug is globally unique across all records including soft-deleted', function () {
    $brand1 = Brand::create([
        'name' => 'Guylian Premium',
        'slug' => 'guylian',
    ]);

    // Active duplicate slug
    expect(function () {
        Brand::create([
            'name' => 'Guylian Belgian',
            'slug' => 'guylian',
        ]);
    })->toThrow(ValidationException::class);

    // Soft-deleted brand's slug remains reserved
    $brand1->delete();
    expect($brand1->trashed())->toBeTrue();

    expect(function () {
        Brand::create([
            'name' => 'Guylian Belgian',
            'slug' => 'guylian',
        ]);
    })->toThrow(ValidationException::class);
});

test('8. Slug remains unchanged when name changes', function () {
    $brand = Brand::create([
        'name' => 'Original Brand',
        'slug' => 'original-brand',
    ]);

    $brand->name = 'Updated Brand Name';
    $brand->save();

    expect($brand->fresh()->slug)->toBe('original-brand');

    // In Filament Edit Form, changing name does not automatically change slug if slug exists
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm(['name' => 'Renamed in Admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($brand->fresh()->slug)->toBe('original-brand')
        ->and($brand->fresh()->name)->toBe('Renamed in Admin');
});

test('9. Explicit slug change works', function () {
    $brand = Brand::create([
        'name' => 'Lindt',
        'slug' => 'lindt-old',
    ]);

    $brand->slug = 'lindt-official';
    $brand->save();

    expect($brand->fresh()->slug)->toBe('lindt-official');
});

test('10. Invalid slug format is rejected', function () {
    expect(function () {
        Brand::create([
            'name' => 'Cadbury Milk',
            'slug' => 'invalid slug with spaces!',
        ]);
    })->toThrow(ValidationException::class);

    expect(function () {
        Brand::create([
            'name' => 'Cadbury Dark',
            'slug' => 'cadbury@chocolate#',
        ]);
    })->toThrow(ValidationException::class);
});

test('11. Description is nullable', function () {
    $brand = Brand::create([
        'name' => 'Valrhona',
        'slug' => 'valrhona',
        'description' => null,
    ]);

    expect($brand->description)->toBeNull();
});

test('12. Logo is nullable', function () {
    $brand = Brand::create([
        'name' => 'Callebaut',
        'slug' => 'callebaut',
        'logo' => null,
    ]);

    expect($brand->logo)->toBeNull();
});

test('13. Inactive Brand can exist', function () {
    $brand = Brand::create([
        'name' => 'Inactive Chocolate',
        'slug' => 'inactive-chocolate',
        'is_active' => false,
    ]);

    expect($brand->is_active)->toBeFalse();
});

test('14. Inactive Brand remains editable', function () {
    $brand = Brand::create([
        'name' => 'Dormant Chocolatier',
        'slug' => 'dormant-chocolatier',
        'is_active' => false,
        'sort_order' => 5,
    ]);

    $brand->name = 'Revived Chocolatier';
    $brand->sort_order = 10;
    $brand->save();

    $fresh = $brand->fresh();
    expect($fresh->name)->toBe('Revived Chocolatier')
        ->and($fresh->sort_order)->toBe(10)
        ->and($fresh->is_active)->toBeFalse();
});

test('15. Brand without Product dependency can be soft deleted', function () {
    $brand = Brand::create([
        'name' => 'Venchi',
        'slug' => 'venchi',
    ]);

    expect($brand->canBeDeleted())->toBeTrue();

    $brand->delete();

    expect($brand->fresh()->trashed())->toBeTrue();
});

test('16. Soft-deleted Brand is excluded from normal queries', function () {
    $brand = Brand::create([
        'name' => 'Pierre Marcolini',
        'slug' => 'pierre-marcolini',
    ]);

    $brand->delete();

    expect(Brand::all()->pluck('id'))->not->toContain($brand->id)
        ->and(Brand::where('name', 'Pierre Marcolini')->first())->toBeNull();
});

test('17. withTrashed can retrieve soft-deleted Brand', function () {
    $brand = Brand::create([
        'name' => 'Domori',
        'slug' => 'domori',
    ]);

    $brand->delete();

    $found = Brand::withTrashed()->where('slug', 'domori')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($brand->id)
        ->and($found->trashed())->toBeTrue();
});

test('18. Restore works at model level', function () {
    $brand = Brand::create([
        'name' => 'Amedei',
        'slug' => 'amedei',
    ]);

    $brand->delete();
    expect($brand->trashed())->toBeTrue();

    $brand->restore();
    expect($brand->fresh()->trashed())->toBeFalse();
});

test('19. Force delete works when no future Product dependency exists', function () {
    $brand = Brand::create([
        'name' => 'Michel Cluizel',
        'slug' => 'michel-cluizel',
    ]);

    expect($brand->canBeDeleted())->toBeTrue();

    $brand->forceDelete();

    expect(Brand::withTrashed()->find($brand->id))->toBeNull();
});

test('20. Product-related delete protection is architected and deferred', function () {
    $brand = Brand::create([
        'name' => 'Future Protected Brand',
        'slug' => 'future-protected-brand',
    ]);

    // Currently returns true because Product model does not exist yet
    expect($brand->canBeDeleted())->toBeTrue();
});
