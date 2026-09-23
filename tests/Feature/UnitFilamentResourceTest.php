<?php

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
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
    Unit::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. UnitResource exists and binds to Unit model', function () {
    expect(UnitResource::getModel())->toBe(Unit::class);
});

test('2. Admin can access UnitResource index page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(UnitResource::getUrl('index'))
        ->assertOk();
});

test('3. Manager can access UnitResource index page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(UnitResource::getUrl('index'))
        ->assertOk();
});

test('4. Staff cannot access UnitResource because they lack units.view', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->get(UnitResource::getUrl('index'))
        ->assertForbidden();

    expect(UnitResource::canAccess())->toBeFalse();
});

test('5. Unauthenticated guest cannot access UnitResource and is redirected to login', function () {
    $this->get(UnitResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('6. User with can_access_admin_panel set to false is forbidden from panel', function () {
    $userWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $userWithoutPanel->assignRole('Admin');

    $this->actingAs($userWithoutPanel)
        ->get(UnitResource::getUrl('index'))
        ->assertForbidden();
});

test('7. Admin can create unit via CreateUnit page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Kilogram Metric',
            'code' => 'kg-metric',
            'description' => 'Metric kilogram unit.',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'name' => 'Kilogram Metric',
        'code' => 'kg-metric',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('8. Manager can create unit via CreateUnit page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Gram Metric',
            'code' => 'gm-metric',
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'name' => 'Gram Metric',
        'code' => 'gm-metric',
    ]);
});

test('9. Staff cannot create unit', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $this->get(UnitResource::getUrl('create'))
        ->assertForbidden();

    expect(UnitResource::canCreate())->toBeFalse();
});

test('10. Admin can update unit via EditUnit page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $unit = Unit::factory()->create([
        'name' => 'Original Unit Name',
        'code' => 'orig-code',
    ]);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Unit Name',
            'code' => 'updated-code',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'name' => 'Updated Unit Name',
        'code' => 'updated-code',
    ]);
});

test('11. Manager can update unit via EditUnit page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $unit = Unit::factory()->create([
        'name' => 'Manager Unit',
        'code' => 'mgr-code',
    ]);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'name' => 'Manager Unit Updated',
            'code' => 'mgr-code',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'name' => 'Manager Unit Updated',
    ]);
});

test('12. Staff cannot access EditUnit page', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $unit = Unit::factory()->create();

    $this->get(UnitResource::getUrl('edit', ['record' => $unit]))
        ->assertForbidden();

    expect(UnitResource::canEdit($unit))->toBeFalse();
});

test('13. Admin can soft-delete unit from EditUnit page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $unit = Unit::factory()->create([
        'name' => 'Deletable Unit Admin',
        'code' => 'del-admin',
    ]);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->callAction('delete');

    expect($unit->fresh()->trashed())->toBeTrue();
});

test('14. Manager cannot delete unit', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $unit = Unit::factory()->create();

    expect(UnitResource::canDelete($unit))->toBeFalse()
        ->and(UnitResource::canDeleteAny())->toBeFalse();

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->assertActionHidden('delete');
});

test('15. Staff cannot delete unit', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $unit = Unit::factory()->create();

    expect(UnitResource::canDelete($unit))->toBeFalse()
        ->and(UnitResource::canDeleteAny())->toBeFalse();
});

test('16. Code uniqueness is validated on edit without false self-conflict', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $unit = Unit::factory()->create([
        'name' => 'Self Edit Unit',
        'code' => 'self-edit',
    ]);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'name' => 'Self Edit Unit Updated',
            'code' => 'self-edit',
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('17. Name uniqueness validation in form prevents duplicate active unit name', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Unit::factory()->create([
        'name' => 'Existing Unique Unit',
        'code' => 'exist-code',
    ]);

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Existing Unique Unit',
            'code' => 'different-code',
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('18. Name uniqueness validation in form allows duplicate name if original is soft-deleted', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $oldUnit = Unit::factory()->create([
        'name' => 'Recycled Unit Name',
        'code' => 'old-code-recycle',
    ]);
    $oldUnit->delete();

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Recycled Unit Name',
            'code' => 'new-code-recycle',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'name' => 'Recycled Unit Name',
        'code' => 'new-code-recycle',
    ]);
});

test('19. Code uniqueness validation in form rejects duplicate code even if original is soft-deleted', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $oldUnit = Unit::factory()->create([
        'name' => 'Retired Unit',
        'code' => 'retired-code',
    ]);
    $oldUnit->delete();

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Brand New Unit',
            'code' => 'retired-code',
        ])
        ->call('create')
        ->assertHasFormErrors(['code']);
});

test('20. Table renders records, searches, and filters by active status and trashed', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $activeUnit = Unit::factory()->create(['name' => 'Active Metric Kilogram', 'code' => 'kg-act', 'is_active' => true]);
    $inactiveUnit = Unit::factory()->create(['name' => 'Dormant Imperial Ounce', 'code' => 'oz-inact', 'is_active' => false]);
    $deletedUnit = Unit::factory()->create(['name' => 'Archived Dram', 'code' => 'drm-del']);
    $deletedUnit->delete();

    // Default view: shows active and inactive, hides deleted
    Livewire::test(ListUnits::class)
        ->assertCanSeeTableRecords([$activeUnit, $inactiveUnit])
        ->assertCanNotSeeTableRecords([$deletedUnit]);

    // Active status ternary filter
    Livewire::test(ListUnits::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeUnit])
        ->assertCanNotSeeTableRecords([$inactiveUnit]);

    // Trashed filter
    Livewire::test(ListUnits::class)
        ->filterTable('trashed', 'true')
        ->assertCanSeeTableRecords([$deletedUnit]);

    // Search table
    Livewire::test(ListUnits::class)
        ->searchTable('Kilogram')
        ->assertCanSeeTableRecords([$activeUnit])
        ->assertCanNotSeeTableRecords([$inactiveUnit]);
});

test('21. Soft-deleted record can be resolved through resource route binding', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $unit = Unit::factory()->create([
        'name' => 'Trashed Resolved Unit',
        'code' => 'tru-res',
    ]);
    $unit->delete();

    $this->get(UnitResource::getUrl('edit', ['record' => $unit]))
        ->assertOk();
});
