<?php

use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

test('1. BrandResource exists and binds to Brand model', function () {
    expect(BrandResource::getModel())->toBe(Brand::class);
});

test('2. Admin can access BrandResource index page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(BrandResource::getUrl('index'))
        ->assertOk();
});

test('3. Manager can access BrandResource index page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(BrandResource::getUrl('index'))
        ->assertOk();
});

test('4. Staff cannot access BrandResource because they lack brands.view', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->get(BrandResource::getUrl('index'))
        ->assertForbidden();

    expect(BrandResource::canAccess())->toBeFalse();
});

test('5. Unauthenticated guest cannot access BrandResource and is redirected to login', function () {
    $this->get(BrandResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('6. User with can_access_admin_panel set to false is forbidden from panel', function () {
    $userWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $userWithoutPanel->assignRole('Admin');

    $this->actingAs($userWithoutPanel)
        ->get(BrandResource::getUrl('index'))
        ->assertForbidden();
});

test('7. Admin can create brand via CreateBrand page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Artisan Cocoa Co.',
            'slug' => 'artisan-cocoa-co',
            'description' => 'Single estate craft chocolate makers.',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'name' => 'Artisan Cocoa Co.',
        'slug' => 'artisan-cocoa-co',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('8. Manager can create brand via CreateBrand page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Swiss Delight',
            'slug' => 'swiss-delight',
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'name' => 'Swiss Delight',
        'slug' => 'swiss-delight',
    ]);
});

test('9. Staff cannot create brand', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $this->get(BrandResource::getUrl('create'))
        ->assertForbidden();

    expect(BrandResource::canCreate())->toBeFalse();
});

test('10. Admin can update brand via EditBrand page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $brand = Brand::factory()->create([
        'name' => 'Original Brand Name',
        'slug' => 'original-brand-name',
    ]);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Brand Name',
            'slug' => 'updated-brand-name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'Updated Brand Name',
        'slug' => 'updated-brand-name',
    ]);
});

test('11. Manager can update brand via EditBrand page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $brand = Brand::factory()->create([
        'name' => 'Belgian Sweet',
        'slug' => 'belgian-sweet',
    ]);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm([
            'name' => 'Belgian Sweet Co',
            'slug' => 'belgian-sweet',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'Belgian Sweet Co',
    ]);
});

test('12. Staff cannot access EditBrand page', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $brand = Brand::factory()->create();

    $this->get(BrandResource::getUrl('edit', ['record' => $brand]))
        ->assertForbidden();

    expect(BrandResource::canEdit($brand))->toBeFalse();
});

test('13. Admin can soft-delete brand from EditBrand page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $brand = Brand::factory()->create([
        'name' => 'Deletable Brand',
        'slug' => 'deletable-brand',
    ]);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->callAction('delete');

    expect($brand->fresh()->trashed())->toBeTrue();
});

test('14. Manager cannot delete brand', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $brand = Brand::factory()->create();

    expect(BrandResource::canDelete($brand))->toBeFalse()
        ->and(BrandResource::canDeleteAny())->toBeFalse();

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->assertActionHidden('delete');
});

test('15. Staff cannot delete brand', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $brand = Brand::factory()->create();

    expect(BrandResource::canDelete($brand))->toBeFalse()
        ->and(BrandResource::canDeleteAny())->toBeFalse();
});

test('16. Slug uniqueness is validated on edit without false self-conflict', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $brand = Brand::factory()->create([
        'name' => 'Choco Master',
        'slug' => 'choco-master',
    ]);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm([
            'name' => 'Choco Master Updated',
            'slug' => 'choco-master',
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('17. Name uniqueness validation in form prevents duplicate active brand name', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Brand::factory()->create([
        'name' => 'Existing Brand',
        'slug' => 'existing-brand',
    ]);

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Existing Brand',
            'slug' => 'another-brand-slug',
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('18. Name uniqueness validation in form allows duplicate name if original is soft-deleted', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $oldBrand = Brand::factory()->create([
        'name' => 'Recycled Brand Name',
        'slug' => 'old-brand-slug',
    ]);
    $oldBrand->delete();

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Recycled Brand Name',
            'slug' => 'new-brand-slug',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'name' => 'Recycled Brand Name',
        'slug' => 'new-brand-slug',
    ]);
});

test('19. Table renders records, searches, and filters by active status and trashed', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $activeBrand = Brand::factory()->create(['name' => 'Valrhona Grand Cru', 'slug' => 'valrhona-grand-cru', 'is_active' => true]);
    $inactiveBrand = Brand::factory()->create(['name' => 'Discontinued Cocoa', 'slug' => 'discontinued-cocoa', 'is_active' => false]);
    $deletedBrand = Brand::factory()->create(['name' => 'Archived Sweet', 'slug' => 'archived-sweet']);
    $deletedBrand->delete();

    // Default view: shows active and inactive, hides deleted
    Livewire::test(ListBrands::class)
        ->assertCanSeeTableRecords([$activeBrand, $inactiveBrand])
        ->assertCanNotSeeTableRecords([$deletedBrand]);

    // Active status ternary filter
    Livewire::test(ListBrands::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeBrand])
        ->assertCanNotSeeTableRecords([$inactiveBrand]);

    // Trashed filter
    Livewire::test(ListBrands::class)
        ->filterTable('trashed', 'true')
        ->assertCanSeeTableRecords([$deletedBrand]);

    // Search table
    Livewire::test(ListBrands::class)
        ->searchTable('Valrhona')
        ->assertCanSeeTableRecords([$activeBrand])
        ->assertCanNotSeeTableRecords([$inactiveBrand]);
});
