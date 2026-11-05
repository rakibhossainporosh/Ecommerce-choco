<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\User;
use App\Support\AdminAccess;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);
    $this->admin->assignRole('Admin');
});

test('guests are sent to the admin login before they can open roles', function () {
    $this->get(RoleResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('manager and staff cannot open role or user management', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(RoleResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();

    expect($manager->can('products.view'))->toBeTrue()
        ->and($manager->can('roles.view'))->toBeFalse()
        ->and($manager->can('users.view'))->toBeFalse();
});

test('admin can create a role and that user only sees the allowed area', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Catalog viewer',
            'permission_groups' => [
                'products' => ['products.view'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::findByName('Catalog viewer', 'web');

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('products.view'))->toBeTrue()
        ->and($role->hasPermissionTo('orders.view'))->toBeFalse()
        ->and($role->hasPermissionTo('roles.view'))->toBeFalse();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Catalog Person',
            'email' => 'catalog-person@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'Catalog viewer',
            'can_access_admin_panel' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $catalogUser = User::query()->where('email', 'catalog-person@example.com')->first();

    expect($catalogUser)->not->toBeNull()
        ->and($catalogUser->hasRole('Catalog viewer'))->toBeTrue()
        ->and($catalogUser->can_access_admin_panel)->toBeTrue();

    $this->actingAs($catalogUser);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee(ProductResource::getUrl('index'), false)
        ->assertDontSee(OrderResource::getUrl('index'), false)
        ->assertDontSee(RoleResource::getUrl('index'), false)
        ->assertDontSee('Total Revenue');

    $this->get(ProductResource::getUrl('index'))->assertSuccessful();
    $this->get(OrderResource::getUrl('index'))->assertForbidden();
    $this->get(RoleResource::getUrl('index'))->assertForbidden();
    $this->get(UserResource::getUrl('index'))->assertForbidden();

    expect(StatsOverview::canView())->toBeFalse()
        ->and(LatestOrders::canView())->toBeFalse()
        ->and(OrdersChart::canView())->toBeFalse()
        ->and(OrderStatusChart::canView())->toBeFalse();
});

test('a customers-only role sees the customer stat and not revenue', function () {
    $role = Role::create([
        'name' => 'Customer desk',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo(['dashboard.view', 'customers.view']);

    $user = User::factory()->create(['can_access_admin_panel' => true]);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(StatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Total Customers')
        ->assertDontSee('Total Revenue')
        ->assertDontSee('Stock Alert');

    expect(LatestOrders::canView())->toBeFalse();
});

test('built-in roles and roles assigned to users cannot be deleted', function () {
    $this->actingAs($this->admin);

    expect(RoleResource::canDelete(Role::findByName('Admin')))->toBeFalse()
        ->and(RoleResource::canDelete(Role::findByName('Manager')))->toBeFalse()
        ->and(RoleResource::canDelete(Role::findByName('Staff')))->toBeFalse();

    $role = Role::create([
        'name' => 'Seasonal',
        'guard_name' => 'web',
    ]);

    expect(RoleResource::canDelete($role))->toBeTrue();

    $this->admin->assignRole($role);

    expect(RoleResource::canDelete($role->fresh()))->toBeFalse();

    Livewire::test(ListRoles::class)
        ->callTableBulkAction('delete', [Role::findByName('Admin')]);

    expect(Role::findByName('Admin'))->not->toBeNull();
});

test('the last admin cannot be demoted or deleted', function () {
    $this->actingAs($this->admin);

    expect(UserResource::canDelete($this->admin))->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->fillForm([
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'Staff',
            'can_access_admin_panel' => true,
        ])
        ->call('save')
        ->assertNotified();

    $this->admin->refresh();

    expect($this->admin->hasRole('Admin'))->toBeTrue()
        ->and($this->admin->hasRole('Staff'))->toBeFalse();
});

test('saving a role cannot grant access-control permissions', function () {
    expect(AdminAccess::onlyAssignable(['products.view', 'roles.view', 'users.delete']))
        ->toBe(['products.view']);
});

test('saving manager keeps the permissions already on that role', function () {
    $this->actingAs($this->admin);

    $manager = Role::findByName('Manager');

    expect($manager->hasPermissionTo('products.view'))->toBeTrue();

    Livewire::test(EditRole::class, ['record' => $manager->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($manager->fresh()->hasPermissionTo('products.view'))->toBeTrue()
        ->and($manager->fresh()->hasPermissionTo('roles.view'))->toBeFalse();
});
