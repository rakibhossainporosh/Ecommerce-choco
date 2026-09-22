<?php

use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

/**
 * RP-7: AUTHORIZATION HARDENING & REGRESSION PROTECTION
 *
 * ARCHITECTURAL CONTEXT & TEST BOUNDARIES:
 *
 * 1. Tested Foundations:
 *    - Panel Access boundary (can_access_admin_panel) is strictly separate from business permissions.
 *    - Centralized Admin full access operates solely through Gate::before in AppServiceProvider.
 *    - Manager (23 permissions) and Staff (9 permissions) boundaries are rigidly enforced.
 *    - Negative authorization ensures zero unpermitted actions can succeed.
 *    - Direct permission assignment operates independently of roles (Permission = capability, Role = grouping).
 *    - Permission cache determinism guarantees repeatable authorization evaluations.
 *    - Factory defaults prevent accidental privilege escalation.
 *
 * 2. Deferred to Future Phases:
 *    - Production Eloquent models (Category, Product, Order) and database migrations.
 *    - Production Filament Resources (CategoryResource, etc.), Livewire form/table lifecycles, and browser sidebar DOM.
 */

// In-test stubs used exclusively for contract verification without creating production models/resources
if (! class_exists('HardenedStubCategoryModel')) {
    class HardenedStubCategoryModel extends Model
    {
        protected $table = 'hardened_stub_categories';
    }
}

if (! class_exists('HardenedStubProductModel')) {
    class HardenedStubProductModel extends Model
    {
        protected $table = 'hardened_stub_products';
    }
}

if (! class_exists('HardenedStubOrderModel')) {
    class HardenedStubOrderModel extends Model
    {
        protected $table = 'hardened_stub_orders';
    }
}

if (! class_exists('HardenedCategoryResourceStub')) {
    class HardenedCategoryResourceStub extends Resource
    {
        protected static ?string $model = HardenedStubCategoryModel::class;
    }
}

if (! class_exists('HardenedProductResourceStub')) {
    class HardenedProductResourceStub extends Resource
    {
        protected static ?string $model = HardenedStubProductModel::class;
    }
}

if (! class_exists('HardenedOrderResourceStub')) {
    class HardenedOrderResourceStub extends Resource
    {
        protected static ?string $model = HardenedStubOrderModel::class;
    }
}

if (! class_exists('HardenedCustomPageStub')) {
    class HardenedCustomPageStub extends Page
    {
        public static function canAccess(): bool
        {
            return auth()->user()?->can('dashboard.view') ?? false;
        }
    }
}

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    // Reset Spatie permission cache before each test run
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Gate::policy(HardenedStubCategoryModel::class, CategoryPolicy::class);
    Gate::policy(HardenedStubProductModel::class, ProductPolicy::class);
    Gate::policy(HardenedStubOrderModel::class, OrderPolicy::class);

    $this->categoryPolicy = new CategoryPolicy;
    $this->productPolicy = new ProductPolicy;
    $this->orderPolicy = new OrderPolicy;
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 2 & 9: Panel Access vs Business Authorization Matrix
|--------------------------------------------------------------------------
*/

test('panel access matrix: Case 1 - unauthenticated guest is redirected to login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('panel access matrix: Case 2 - user with can_access_admin_panel = false receives 403', function () {
    $user = User::factory()->create(['can_access_admin_panel' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('panel access matrix: Case 3 - user with can_access_admin_panel = true accesses /admin', function () {
    $user = User::factory()->create(['can_access_admin_panel' => true]);

    $this->actingAs($user)->get('/admin')->assertOk();
});

test('panel access matrix: Case 4 / Case A - Admin role with can_access_admin_panel = false MUST still receive 403', function () {
    // Proves Admin role does NOT bypass panel access boundary
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');

    // Business authorization is active
    expect(Gate::forUser($adminWithoutPanel)->allows('products.delete'))->toBeTrue()
        ->and($adminWithoutPanel->can('orders.refund'))->toBeTrue();

    // But panel access is strictly forbidden
    $this->actingAs($adminWithoutPanel)->get('/admin')->assertForbidden();
});

test('panel access matrix: Case 5 / Case B - can_access_admin_panel = true with zero permissions accesses /admin', function () {
    // Proves panel access is decoupled from business permissions and vendor Dashboard does not require permissions
    $userNoPerms = User::factory()->create(['can_access_admin_panel' => true]);

    expect($userNoPerms->roles)->toBeEmpty()
        ->and($userNoPerms->permissions)->toBeEmpty();

    $this->actingAs($userNoPerms)->get('/admin')->assertOk();
});

test('panel access matrix: Case C - Staff with panel access enters /admin with strictly staff abilities', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff)->get('/admin')->assertOk();

    // Granted staff permission
    expect($staff->can('products.view'))->toBeTrue()
        ->and($staff->can('orders.create'))->toBeTrue()
        // Prohibited permissions
        ->and($staff->can('products.delete'))->toBeFalse()
        ->and($staff->can('orders.cancel'))->toBeFalse()
        ->and($staff->can('categories.view'))->toBeFalse();
});

test('panel access matrix: Case D - Admin with panel access enters /admin with full Gate::before abilities', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get('/admin')->assertOk();

    expect($admin->can('products.delete'))->toBeTrue()
        ->and($admin->can('orders.refund'))->toBeTrue()
        ->and($admin->can('categories.delete'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 5: Centralized Admin Gate::before Regression Matrix
|--------------------------------------------------------------------------
*/

test('admin gate before: Admin has 0 database permissions but authorizes all 29 permissions via Gate::before', function () {
    $adminRole = Role::findByName('Admin', 'web');

    // Verify no direct database permission pivot records exist for Admin
    expect($adminRole->permissions)->toBeEmpty();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($admin->permissions)->toBeEmpty()
        ->and($admin->getPermissionsViaRoles())->toBeEmpty();

    // All 29 seeded permissions must evaluate true via Gate::before
    $allPermissions = Permission::pluck('name')->all();
    expect(count($allPermissions))->toBe(29);

    foreach ($allPermissions as $permission) {
        expect(Gate::forUser($admin)->allows($permission))->toBeTrue()
            ->and($admin->can($permission))->toBeTrue();
    }

    // Policy method checks evaluate true via Gate::before
    expect($this->categoryPolicy->viewAny($admin))->toBeTrue()
        ->and($this->categoryPolicy->create($admin))->toBeTrue()
        ->and($this->categoryPolicy->update($admin))->toBeTrue()
        ->and($this->categoryPolicy->delete($admin))->toBeTrue()
        ->and($this->categoryPolicy->deleteAny($admin))->toBeTrue()
        ->and($this->productPolicy->viewAny($admin))->toBeTrue()
        ->and($this->productPolicy->create($admin))->toBeTrue()
        ->and($this->productPolicy->update($admin))->toBeTrue()
        ->and($this->productPolicy->delete($admin))->toBeTrue()
        ->and($this->productPolicy->deleteAny($admin))->toBeTrue()
        ->and($this->orderPolicy->viewAny($admin))->toBeTrue()
        ->and($this->orderPolicy->create($admin))->toBeTrue()
        ->and($this->orderPolicy->update($admin))->toBeTrue()
        ->and($this->orderPolicy->cancel($admin))->toBeTrue()
        ->and($this->orderPolicy->refund($admin))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 3, 4 & 6: Manager and Staff Boundaries & Negative Tests
|--------------------------------------------------------------------------
*/

test('role boundary: Manager has exactly 23 permissions and is strictly denied the 6 destructive/refund abilities', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    // Exactly 6 denied permissions from the 29-permission catalog
    $deniedPermissions = [
        'categories.delete',
        'brands.delete',
        'units.delete',
        'products.delete',
        'customers.delete',
        'orders.refund',
    ];

    foreach ($deniedPermissions as $deniedPermission) {
        expect($manager->can($deniedPermission))->toBeFalse()
            ->and(Gate::forUser($manager)->allows($deniedPermission))->toBeFalse();
    }

    // Prohibited policy actions evaluate false
    expect($this->categoryPolicy->delete($manager))->toBeFalse()
        ->and($this->categoryPolicy->deleteAny($manager))->toBeFalse()
        ->and($this->productPolicy->delete($manager))->toBeFalse()
        ->and($this->productPolicy->deleteAny($manager))->toBeFalse()
        ->and($this->orderPolicy->refund($manager))->toBeFalse();

    // Permitted policy actions evaluate true
    expect($this->categoryPolicy->viewAny($manager))->toBeTrue()
        ->and($this->categoryPolicy->view($manager))->toBeTrue()
        ->and($this->categoryPolicy->create($manager))->toBeTrue()
        ->and($this->categoryPolicy->update($manager))->toBeTrue()
        ->and($this->productPolicy->viewAny($manager))->toBeTrue()
        ->and($this->productPolicy->view($manager))->toBeTrue()
        ->and($this->productPolicy->create($manager))->toBeTrue()
        ->and($this->productPolicy->update($manager))->toBeTrue()
        ->and($this->orderPolicy->viewAny($manager))->toBeTrue()
        ->and($this->orderPolicy->view($manager))->toBeTrue()
        ->and($this->orderPolicy->create($manager))->toBeTrue()
        ->and($this->orderPolicy->update($manager))->toBeTrue()
        ->and($this->orderPolicy->cancel($manager))->toBeTrue();
});

test('role boundary: Staff has exactly 9 permissions and is strictly denied the 20 catalog/delete/cancel/refund abilities', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    // Exactly 20 denied permissions from the 29-permission catalog
    $deniedPermissions = [
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'brands.view', 'brands.create', 'brands.update', 'brands.delete',
        'units.view', 'units.create', 'units.update', 'units.delete',
        'products.create', 'products.update', 'products.delete',
        'customers.delete',
        'orders.cancel', 'orders.refund',
        'inventory.adjust',
        'reports.view',
    ];

    foreach ($deniedPermissions as $deniedPermission) {
        expect($staff->can($deniedPermission))->toBeFalse()
            ->and(Gate::forUser($staff)->allows($deniedPermission))->toBeFalse();
    }

    // Prohibited policy actions evaluate false
    expect($this->categoryPolicy->viewAny($staff))->toBeFalse()
        ->and($this->categoryPolicy->view($staff))->toBeFalse()
        ->and($this->categoryPolicy->create($staff))->toBeFalse()
        ->and($this->categoryPolicy->update($staff))->toBeFalse()
        ->and($this->categoryPolicy->delete($staff))->toBeFalse()
        ->and($this->categoryPolicy->deleteAny($staff))->toBeFalse()
        ->and($this->productPolicy->create($staff))->toBeFalse()
        ->and($this->productPolicy->update($staff))->toBeFalse()
        ->and($this->productPolicy->delete($staff))->toBeFalse()
        ->and($this->productPolicy->deleteAny($staff))->toBeFalse()
        ->and($this->orderPolicy->cancel($staff))->toBeFalse()
        ->and($this->orderPolicy->refund($staff))->toBeFalse();

    // Permitted policy actions evaluate true
    expect($this->productPolicy->viewAny($staff))->toBeTrue()
        ->and($this->productPolicy->view($staff))->toBeTrue()
        ->and($this->orderPolicy->viewAny($staff))->toBeTrue()
        ->and($this->orderPolicy->view($staff))->toBeTrue()
        ->and($this->orderPolicy->create($staff))->toBeTrue()
        ->and($this->orderPolicy->update($staff))->toBeTrue();
});

test('negative authorization: user with zero permissions is denied across all policy methods', function () {
    $userWithoutPermissions = User::factory()->create();

    // Category policy
    expect($this->categoryPolicy->viewAny($userWithoutPermissions))->toBeFalse()
        ->and($this->categoryPolicy->view($userWithoutPermissions))->toBeFalse()
        ->and($this->categoryPolicy->create($userWithoutPermissions))->toBeFalse()
        ->and($this->categoryPolicy->update($userWithoutPermissions))->toBeFalse()
        ->and($this->categoryPolicy->delete($userWithoutPermissions))->toBeFalse()
        ->and($this->categoryPolicy->deleteAny($userWithoutPermissions))->toBeFalse();

    // Product policy
    expect($this->productPolicy->viewAny($userWithoutPermissions))->toBeFalse()
        ->and($this->productPolicy->view($userWithoutPermissions))->toBeFalse()
        ->and($this->productPolicy->create($userWithoutPermissions))->toBeFalse()
        ->and($this->productPolicy->update($userWithoutPermissions))->toBeFalse()
        ->and($this->productPolicy->delete($userWithoutPermissions))->toBeFalse()
        ->and($this->productPolicy->deleteAny($userWithoutPermissions))->toBeFalse();

    // Order policy
    expect($this->orderPolicy->viewAny($userWithoutPermissions))->toBeFalse()
        ->and($this->orderPolicy->view($userWithoutPermissions))->toBeFalse()
        ->and($this->orderPolicy->create($userWithoutPermissions))->toBeFalse()
        ->and($this->orderPolicy->update($userWithoutPermissions))->toBeFalse()
        ->and($this->orderPolicy->cancel($userWithoutPermissions))->toBeFalse()
        ->and($this->orderPolicy->refund($userWithoutPermissions))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 8: Direct Permission Capability without Role
|--------------------------------------------------------------------------
*/

test('direct permission: user with zero roles granted orders.refund has capability and gains no unrelated abilities', function () {
    $rolelessUser = User::factory()->create();

    expect($rolelessUser->roles)->toBeEmpty();

    // Directly assign single permission
    $rolelessUser->givePermissionTo('orders.refund');

    // Granted capability is functional
    expect($rolelessUser->can('orders.refund'))->toBeTrue()
        ->and($this->orderPolicy->refund($rolelessUser))->toBeTrue();

    // Unrelated abilities remain strictly denied
    expect($rolelessUser->can('orders.cancel'))->toBeFalse()
        ->and($this->orderPolicy->cancel($rolelessUser))->toBeFalse()
        ->and($rolelessUser->can('products.delete'))->toBeFalse()
        ->and($this->productPolicy->delete($rolelessUser))->toBeFalse()
        ->and($rolelessUser->can('categories.delete'))->toBeFalse()
        ->and($this->categoryPolicy->delete($rolelessUser))->toBeFalse()
        ->and($rolelessUser->can('categories.view'))->toBeFalse()
        ->and($this->categoryPolicy->viewAny($rolelessUser))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 7: Filament Authorization Contracts via Stubs
|--------------------------------------------------------------------------
*/

test('filament contracts: Resource canAccess contract delegates to Policy viewAny via Gate', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    expect(HardenedCategoryResourceStub::canAccess())->toBeTrue()
        ->and(HardenedProductResourceStub::canAccess())->toBeTrue()
        ->and(HardenedOrderResourceStub::canAccess())->toBeTrue();

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    // Staff lacks categories.view -> canAccess returns false
    expect(HardenedCategoryResourceStub::canAccess())->toBeFalse()
        // Staff has products.view and orders.view -> canAccess returns true
        ->and(HardenedProductResourceStub::canAccess())->toBeTrue()
        ->and(HardenedOrderResourceStub::canAccess())->toBeTrue();
});

test('filament contracts: Action authorize contract synchronizes isAuthorized and isVisible on stub records', function () {
    $record = new HardenedStubOrderModel;

    // Manager: has cancel, lacks refund
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $cancelAction = Action::make('cancel')->record($record)->authorize('cancel');
    $refundAction = Action::make('refund')->record($record)->authorize('refund');

    expect($cancelAction->isAuthorized())->toBeTrue()
        ->and($cancelAction->isVisible())->toBeTrue()
        ->and($refundAction->isAuthorized())->toBeFalse()
        ->and($refundAction->isVisible())->toBeFalse();

    // Staff: lacks both cancel and refund
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $cancelStaff = Action::make('cancel')->record($record)->authorize('cancel');
    $refundStaff = Action::make('refund')->record($record)->authorize('refund');

    expect($cancelStaff->isAuthorized())->toBeFalse()
        ->and($cancelStaff->isVisible())->toBeFalse()
        ->and($refundStaff->isAuthorized())->toBeFalse()
        ->and($refundStaff->isVisible())->toBeFalse();
});

test('filament contracts: Custom Page canAccess contract strictly evaluates permissions on page stubs', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    expect(HardenedCustomPageStub::canAccess())->toBeTrue();

    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);
    // Staff has dashboard.view -> canAccess is true
    expect(HardenedCustomPageStub::canAccess())->toBeTrue();

    $unprivileged = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($unprivileged);
    // Unprivileged user lacks dashboard.view -> canAccess is false
    expect(HardenedCustomPageStub::canAccess())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| RP-7 TASK 11 & 12: Permission Cache Determinism & Factory Safety
|--------------------------------------------------------------------------
*/

test('permission cache: role and permission assignments reflect deterministically with cache clearing', function () {
    $user = User::factory()->create();

    expect($user->can('orders.refund'))->toBeFalse();

    // Assign permission
    $user->givePermissionTo('orders.refund');

    // Force flush Spatie cache to simulate production cache refresh lifecycle
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($user->can('orders.refund'))->toBeTrue();

    // Revoke permission
    $user->revokePermissionTo('orders.refund');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($user->can('orders.refund'))->toBeFalse();
});

test('factory defaults: newly created user has safe defaults with zero implicit privilege', function () {
    $defaultUser = User::factory()->create();

    // Panel access is false by default
    expect($defaultUser->can_access_admin_panel)->toBeFalse()
        // No roles assigned
        ->and($defaultUser->roles()->count())->toBe(0)
        ->and($defaultUser->hasRole('Admin'))->toBeFalse()
        ->and($defaultUser->hasRole('Manager'))->toBeFalse()
        ->and($defaultUser->hasRole('Staff'))->toBeFalse()
        // No permissions assigned
        ->and($defaultUser->permissions()->count())->toBe(0)
        // Gate checks strictly fail
        ->and(Gate::forUser($defaultUser)->allows('products.view'))->toBeFalse()
        ->and(Gate::forUser($defaultUser)->allows('categories.create'))->toBeFalse()
        ->and(Gate::forUser($defaultUser)->allows('orders.delete'))->toBeFalse();
});
