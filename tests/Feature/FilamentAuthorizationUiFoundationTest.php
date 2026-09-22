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

uses(LazilyRefreshDatabase::class);

/**
 * ARCHITECTURAL SCOPE & FOUNDATION CONTRACTS:
 *
 * 1. What is genuinely verified today:
 *    - Panel access boundary: Access to /admin strictly requires can_access_admin_panel = true.
 *      Users with can_access_admin_panel = true (including users without business permissions)
 *      reach the default vendor Dashboard (HTTP 200), confirming panel entry is decoupled from business permissions.
 *    - Action authorization foundation contract: Filament Action::authorize() synchronizes isAuthorized()
 *      and isVisible() via Gate and policy abilities using in-test stub records.
 *    - Resource navigation foundation contract: Filament Resource::canAccess() delegates to canViewAny()
 *      and into policy viewAny() using in-test resource stubs.
 *    - Custom page authorization foundation contract: Filament Page::canAccess() (CanAuthorizeAccess)
 *      evaluates permission checks ($user->can('dashboard.view')) on test page stubs.
 *    - Centralized Admin full access (Gate::before in AppServiceProvider) remains effective
 *      across action authorization, resource canAccess, and custom page canAccess.
 *    - Manager and Staff permission assignments are strictly enforced; unauthorized users are denied.
 *    - UI authorization contracts are strictly permission-driven without hardcoded role checks.
 *
 * 2. What is verified through framework / stub architecture:
 *    - Filament 5.8.4 HasAuthorization trait integration with Laravel Gate and model policies.
 *    - Filament 5.8.4 CanBeAuthorized and CanBeHidden traits synchronizing action visibility and execution guards.
 *    - Filament 5.8.4 CanAuthorizeAccess trait on custom pages.
 *
 * 3. What is deferred until real business Models and Resources are created:
 *    - Production Category, Product, and Order Eloquent models (and migration tables).
 *    - Production CategoryResource, ProductResource, and OrderResource classes.
 *    - Framework auto-discovery of production models to policies.
 *    - Live browser sidebar HTML menu rendering of production resource links.
 *    - Livewire form schema rendering, table column rendering, and CRUD action lifecycle requests.
 *    - Dashboard-specific business authorization (the default vendor Dashboard does not enforce dashboard.view;
 *      dashboard.view remains a business permission reserved for custom dashboard pages/widgets if implemented).
 */

// In-test model and resource stubs used exclusively to verify UI foundation contracts without creating production models
if (! class_exists('UiStubCategoryModel')) {
    class UiStubCategoryModel extends Model
    {
        protected $table = 'ui_stub_categories';
    }
}

if (! class_exists('UiStubProductModel')) {
    class UiStubProductModel extends Model
    {
        protected $table = 'ui_stub_products';
    }
}

if (! class_exists('UiStubOrderModel')) {
    class UiStubOrderModel extends Model
    {
        protected $table = 'ui_stub_orders';
    }
}

if (! class_exists('TestCategoryResourceStub')) {
    class TestCategoryResourceStub extends Resource
    {
        protected static ?string $model = UiStubCategoryModel::class;
    }
}

if (! class_exists('TestProductResourceStub')) {
    class TestProductResourceStub extends Resource
    {
        protected static ?string $model = UiStubProductModel::class;
    }
}

if (! class_exists('TestOrderResourceStub')) {
    class TestOrderResourceStub extends Resource
    {
        protected static ?string $model = UiStubOrderModel::class;
    }
}

if (! class_exists('TestCustomPageStub')) {
    class TestCustomPageStub extends Page
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

    Gate::policy(UiStubCategoryModel::class, CategoryPolicy::class);
    Gate::policy(UiStubProductModel::class, ProductPolicy::class);
    Gate::policy(UiStubOrderModel::class, OrderPolicy::class);
});

test('1. Panel access boundary: /admin route strictly requires can_access_admin_panel', function () {
    // Unauthenticated guest is redirected to login
    $this->get('/admin')->assertRedirect('/admin/login');

    // Admin with can_access_admin_panel = false is forbidden (HTTP 403)
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');
    $this->actingAs($adminWithoutPanel)->get('/admin')->assertForbidden();

    // Admin with can_access_admin_panel = true is granted access (HTTP 200)
    $adminWithPanel = User::factory()->create(['can_access_admin_panel' => true]);
    $adminWithPanel->assignRole('Admin');
    $this->actingAs($adminWithPanel)->get('/admin')->assertOk();

    // Manager with can_access_admin_panel = true is granted access (HTTP 200)
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager)->get('/admin')->assertOk();

    // Staff with can_access_admin_panel = true is granted access (HTTP 200)
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff)->get('/admin')->assertOk();

    // User with can_access_admin_panel = true but NO roles/permissions can enter /admin (HTTP 200)
    // This confirms that entering /admin and the default vendor Dashboard is governed by can_access_admin_panel,
    // not dashboard.view (which is reserved for custom dashboard components/pages if implemented).
    $userWithPanelNoPerms = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($userWithPanelNoPerms)->get('/admin')->assertOk();
});

test('2. Action authorization foundation: Action::authorize() synchronizes isAuthorized and isVisible on test stub records', function () {
    $orderRecord = new UiStubOrderModel;

    // Admin: cancel and refund actions are authorized AND visible via Gate::before
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $cancelAdmin = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundAdmin = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelAdmin->isAuthorized())->toBeTrue()
        ->and($cancelAdmin->isVisible())->toBeTrue()
        ->and($refundAdmin->isAuthorized())->toBeTrue()
        ->and($refundAdmin->isVisible())->toBeTrue();

    // Manager: has orders.cancel, lacks orders.refund -> cancel is authorized & visible; refund is denied & hidden
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $cancelManager = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundManager = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelManager->isAuthorized())->toBeTrue()
        ->and($cancelManager->isVisible())->toBeTrue()
        ->and($refundManager->isAuthorized())->toBeFalse()
        ->and($refundManager->isVisible())->toBeFalse();

    // Staff: lacks both orders.cancel and orders.refund -> both are denied & hidden
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $cancelStaff = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundStaff = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelStaff->isAuthorized())->toBeFalse()
        ->and($cancelStaff->isVisible())->toBeFalse()
        ->and($refundStaff->isAuthorized())->toBeFalse()
        ->and($refundStaff->isVisible())->toBeFalse();
});

test('3. Resource navigation foundation: Resource::canAccess() contract delegates to Policy viewAny using test stubs', function () {
    // Admin: evaluates canAccess() === true across all resource stubs via centralized Gate::before
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    expect(TestCategoryResourceStub::canAccess())->toBeTrue()
        ->and(TestProductResourceStub::canAccess())->toBeTrue()
        ->and(TestOrderResourceStub::canAccess())->toBeTrue();

    // Manager: has categories.view, products.view, orders.view -> evaluates canAccess() === true across all stubs
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    expect(TestCategoryResourceStub::canAccess())->toBeTrue()
        ->and(TestProductResourceStub::canAccess())->toBeTrue()
        ->and(TestOrderResourceStub::canAccess())->toBeTrue();

    // Staff: has products.view and orders.view, but lacks categories.view.
    // Once a real CategoryResource exists following this pattern, Staff lacking categories.view will not receive navigation access.
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    expect(TestCategoryResourceStub::canAccess())->toBeFalse()
        ->and(TestProductResourceStub::canAccess())->toBeTrue()
        ->and(TestOrderResourceStub::canAccess())->toBeTrue();

    // Unauthorized user: no permissions, cannot access any resource stub
    $guest = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($guest);

    expect(TestCategoryResourceStub::canAccess())->toBeFalse()
        ->and(TestProductResourceStub::canAccess())->toBeFalse()
        ->and(TestOrderResourceStub::canAccess())->toBeFalse();
});

test('4. Custom Page authorization foundation: canAccess contract verifies permission-driven access on test page stubs', function () {
    // Note: The default vendor Dashboard is NOT protected by this stub; this verifies the custom Page pattern.
    // Admin: allowed via Gate::before
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    expect(TestCustomPageStub::canAccess())->toBeTrue();

    // Manager: has dashboard.view permission -> allowed on custom page stub
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);
    expect(TestCustomPageStub::canAccess())->toBeTrue();

    // Staff: has dashboard.view permission -> allowed on custom page stub
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);
    expect(TestCustomPageStub::canAccess())->toBeTrue();

    // User without dashboard.view permission: denied on custom page stub
    $userWithoutPermission = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($userWithoutPermission);
    expect(TestCustomPageStub::canAccess())->toBeFalse();
});

test('5. UI authorization is purely permission-driven and never checks role names', function () {
    // User with NO role given direct permission 'orders.refund'
    $rolelessUser = User::factory()->create(['can_access_admin_panel' => true]);
    $rolelessUser->givePermissionTo('orders.refund');
    $this->actingAs($rolelessUser);

    $orderRecord = new UiStubOrderModel;
    $refundAction = Action::make('refund')->record($orderRecord)->authorize('refund');
    $cancelAction = Action::make('cancel')->record($orderRecord)->authorize('cancel');

    // Refund is authorized and visible due to permission, without any role check
    expect($refundAction->isAuthorized())->toBeTrue()
        ->and($refundAction->isVisible())->toBeTrue()
        // Cancel is denied and hidden because permission is absent
        ->and($cancelAction->isAuthorized())->toBeFalse()
        ->and($cancelAction->isVisible())->toBeFalse();
});
