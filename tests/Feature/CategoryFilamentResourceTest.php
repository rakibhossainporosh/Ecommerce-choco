<?php

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
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
    Category::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. CategoryResource exists and binds to Category model', function () {
    expect(CategoryResource::getModel())->toBe(Category::class);
});

test('2. Admin can access CategoryResource index page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(CategoryResource::getUrl('index'))
        ->assertOk();
});

test('3. Manager can access CategoryResource index page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(CategoryResource::getUrl('index'))
        ->assertOk();
});

test('4. Staff cannot access CategoryResource because they lack categories.view', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->get(CategoryResource::getUrl('index'))
        ->assertForbidden();

    expect(CategoryResource::canAccess())->toBeFalse();
});

test('5. Unauthenticated guest cannot access CategoryResource and is redirected to login', function () {
    $this->get(CategoryResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('6. User with can_access_admin_panel set to false is forbidden from panel', function () {
    $userWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $userWithoutPanel->assignRole('Admin');

    $this->actingAs($userWithoutPanel)
        ->get(CategoryResource::getUrl('index'))
        ->assertForbidden();
});

test('7. Admin can create category via CreateCategory page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Artisan Dark Chocolate',
            'slug' => 'artisan-dark-chocolate',
            'description' => 'Rich single-origin chocolate.',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Artisan Dark Chocolate',
        'slug' => 'artisan-dark-chocolate',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('8. Manager can create category via CreateCategory page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'White Chocolate Truffles',
            'slug' => 'white-chocolate-truffles',
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'White Chocolate Truffles',
        'slug' => 'white-chocolate-truffles',
    ]);
});

test('9. Staff cannot create category', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $this->get(CategoryResource::getUrl('create'))
        ->assertForbidden();

    expect(CategoryResource::canCreate())->toBeFalse();
});

test('10. Admin can update category via EditCategory page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-name',
    ]);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name' => 'Updated By Admin',
            'slug' => 'updated-by-admin',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->name)->toBe('Updated By Admin')
        ->and($category->fresh()->slug)->toBe('updated-by-admin');
});

test('11. Manager can update category via EditCategory page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create([
        'name' => 'Manager Target',
        'slug' => 'manager-target',
    ]);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name' => 'Updated By Manager',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->name)->toBe('Updated By Manager');
});

test('12. Staff cannot update category', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $category = Category::factory()->create();

    $this->get(CategoryResource::getUrl('edit', ['record' => $category]))
        ->assertForbidden();

    expect(CategoryResource::canEdit($category))->toBeFalse();
});

test('13. Admin can delete category', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create();

    expect(CategoryResource::canDelete($category))->toBeTrue();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->assertActionVisible('delete')
        ->callAction('delete');

    expect($category->fresh()->trashed())->toBeTrue();
});

test('14. Manager cannot delete category', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $category = Category::factory()->create();

    expect(CategoryResource::canDelete($category))->toBeFalse()
        ->and(CategoryResource::canDeleteAny())->toBeFalse();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->assertActionHidden('delete');
});

test('15. Staff cannot delete category', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $category = Category::factory()->create();

    expect(CategoryResource::canDelete($category))->toBeFalse()
        ->and(CategoryResource::canDeleteAny())->toBeFalse();
});

test('16. Parent category relationship works and can be assigned in resource', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $parentCategory = Category::factory()->create(['name' => 'Confectionery', 'slug' => 'confectionery']);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Pralines',
            'slug' => 'pralines',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $pralines = Category::where('slug', 'pralines')->first();

    expect($pralines)->not->toBeNull()
        ->and($pralines->parent_id)->toBe($parentCategory->id)
        ->and($pralines->parent->name)->toBe('Confectionery');
});

test('17. A category cannot select itself as parent (server-side validation enforced)', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['name' => 'Chocolate Bars', 'slug' => 'chocolate-bars']);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'parent_id' => $category->id,
        ])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

test('18. Slug uniqueness is validated on edit without false self-conflict', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $categoryA = Category::factory()->create(['name' => 'Dark Truffles', 'slug' => 'dark-truffles']);
    $categoryB = Category::factory()->create(['name' => 'Milk Truffles', 'slug' => 'milk-truffles']);

    // Saving existing slug on same record should not trigger unique conflict
    Livewire::test(EditCategory::class, ['record' => $categoryA->getRouteKey()])
        ->fillForm([
            'name' => 'Dark Truffles Updated',
            'slug' => 'dark-truffles',
        ])
        ->call('save')
        ->assertHasNoFormErrors(['slug']);

    // Attempting to use another record's slug must trigger unique validation error
    Livewire::test(EditCategory::class, ['record' => $categoryA->getRouteKey()])
        ->fillForm([
            'slug' => 'milk-truffles',
        ])
        ->call('save')
        ->assertHasFormErrors(['slug']);
});

test('19. Table renders records, searches, and filters by active status and trashed', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $activeCat = Category::factory()->create([
        'name' => 'Ruby Chocolate',
        'slug' => 'ruby-chocolate',
        'is_active' => true,
    ]);

    $inactiveCat = Category::factory()->create([
        'name' => 'Expired Seasonal',
        'slug' => 'expired-seasonal',
        'is_active' => false,
    ]);

    $trashedCat = Category::factory()->create([
        'name' => 'Discontinued Item',
        'slug' => 'discontinued-item',
    ]);
    $trashedCat->delete();

    // Default table shows active and inactive, but not trashed
    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$activeCat, $inactiveCat])
        ->assertCanNotSeeTableRecords([$trashedCat]);

    // Active status ternary filter
    Livewire::test(ListCategories::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeCat])
        ->assertCanNotSeeTableRecords([$inactiveCat]);

    // Trashed filter
    Livewire::test(ListCategories::class)
        ->filterTable('trashed', 'true')
        ->assertCanSeeTableRecords([$trashedCat]);

    // Search table
    Livewire::test(ListCategories::class)
        ->searchTable('Ruby')
        ->assertCanSeeTableRecords([$activeCat])
        ->assertCanNotSeeTableRecords([$inactiveCat]);
});
