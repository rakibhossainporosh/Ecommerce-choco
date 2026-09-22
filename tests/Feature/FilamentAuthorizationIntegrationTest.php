<?php

use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

use function Filament\get_authorization_response;

uses(LazilyRefreshDatabase::class);

/**
 * ARCHITECTURAL SCOPE & BOUNDARIES:
 *
 * 1. What is genuinely verified today:
 *    - Filament 5.8.4 authorization engine (Filament\get_authorization_response) routes
 *      ability checks through Laravel Gate and into our production policies.
 *    - Filament Action authorization (Filament\Actions\Action) respects policy permissions
 *      and automatically controls UI visibility (isVisible/isHidden) and execution guards.
 *    - Centralized Admin full access (Gate::before in AppServiceProvider) remains unconditionally effective.
 *    - Manager and Staff permissions are strictly enforced as configured in RolePermissionSeeder.
 *    - Unauthorized users (no roles / no permissions) are denied across all operations.
 *    - Filament authorization is strictly permission-driven (no role-name checks).
 *    - Separation between Filament panel access (can_access_admin_panel) and business authorization.
 *
 * 2. What requires real Eloquent models (deferred to model creation phase):
 *    - Laravel framework convention auto-discovery (App\Models\{Model} -> App\Policies\{Model}Policy).
 *    - Strict Eloquent type-hinted policy parameters (e.g. Category $category instead of mixed $category = null).
 *
 * 3. What requires real Filament Resources (deferred to resource creation phase):
 *    - Resource routing, navigation registration in the admin sidebar, form schema rendering,
 *      and full Livewire UI page requests.
 */

// Temporary Eloquent model stubs used exclusively to test the Gate/Policy pipeline without creating production models
if (! class_exists('StubCategoryModel')) {
    class StubCategoryModel extends Model
    {
        protected $table = 'stub_categories';
    }
}

if (! class_exists('StubProductModel')) {
    class StubProductModel extends Model
    {
        protected $table = 'stub_products';
    }
}

if (! class_exists('StubOrderModel')) {
    class StubOrderModel extends Model
    {
        protected $table = 'stub_orders';
    }
}

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    Gate::policy(StubCategoryModel::class, CategoryPolicy::class);
    Gate::policy(StubProductModel::class, ProductPolicy::class);
    Gate::policy(StubOrderModel::class, OrderPolicy::class);
});

test('Filament authorization engine: Admin receives full access via centralized Gate::before', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $categoryRecord = new StubCategoryModel;
    $productRecord = new StubProductModel;
    $orderRecord = new StubOrderModel;

    // Category abilities through Filament authorization helper
    expect(get_authorization_response('viewAny', StubCategoryModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $categoryRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubCategoryModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $categoryRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('delete', $categoryRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('deleteAny', StubCategoryModel::class)->allowed())->toBeTrue();

    // Product abilities through Filament authorization helper
    expect(get_authorization_response('viewAny', StubProductModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubProductModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('delete', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('deleteAny', StubProductModel::class)->allowed())->toBeTrue();

    // Order abilities through Filament authorization helper
    expect(get_authorization_response('viewAny', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('cancel', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('refund', $orderRecord)->allowed())->toBeTrue();
});

test('Filament authorization engine: Manager permissions and restrictions are strictly enforced', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $categoryRecord = new StubCategoryModel;
    $productRecord = new StubProductModel;
    $orderRecord = new StubOrderModel;

    // Categories: allowed view/create/update, denied delete/deleteAny
    expect(get_authorization_response('viewAny', StubCategoryModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $categoryRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubCategoryModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $categoryRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('delete', $categoryRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('deleteAny', StubCategoryModel::class)->allowed())->toBeFalse();

    // Products: allowed view/create/update, denied delete/deleteAny
    expect(get_authorization_response('viewAny', StubProductModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubProductModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('delete', $productRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('deleteAny', StubProductModel::class)->allowed())->toBeFalse();

    // Orders: allowed view/create/update/cancel, denied refund
    expect(get_authorization_response('viewAny', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('cancel', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('refund', $orderRecord)->allowed())->toBeFalse();
});

test('Filament authorization engine: Staff permissions and restrictions are strictly enforced', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $productRecord = new StubProductModel;
    $orderRecord = new StubOrderModel;

    // Products: allowed view/viewAny, denied create/update/delete/deleteAny
    expect(get_authorization_response('viewAny', StubProductModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $productRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubProductModel::class)->allowed())->toBeFalse()
        ->and(get_authorization_response('update', $productRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('delete', $productRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('deleteAny', StubProductModel::class)->allowed())->toBeFalse();

    // Orders: allowed view/create/update, denied cancel/refund
    expect(get_authorization_response('viewAny', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('view', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('create', StubOrderModel::class)->allowed())->toBeTrue()
        ->and(get_authorization_response('update', $orderRecord)->allowed())->toBeTrue()
        ->and(get_authorization_response('cancel', $orderRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('refund', $orderRecord)->allowed())->toBeFalse();
});

test('Filament authorization engine: unauthorized user is denied across all operations', function () {
    $guest = User::factory()->create(['can_access_admin_panel' => false]);
    $this->actingAs($guest);

    $categoryRecord = new StubCategoryModel;
    $productRecord = new StubProductModel;
    $orderRecord = new StubOrderModel;

    expect(get_authorization_response('viewAny', StubCategoryModel::class)->allowed())->toBeFalse()
        ->and(get_authorization_response('create', StubCategoryModel::class)->allowed())->toBeFalse()
        ->and(get_authorization_response('delete', $categoryRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('viewAny', StubProductModel::class)->allowed())->toBeFalse()
        ->and(get_authorization_response('update', $productRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('delete', $productRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('viewAny', StubOrderModel::class)->allowed())->toBeFalse()
        ->and(get_authorization_response('cancel', $orderRecord)->allowed())->toBeFalse()
        ->and(get_authorization_response('refund', $orderRecord)->allowed())->toBeFalse();
});

test('Filament Action authorization: custom action respects policy permissions and UI visibility', function () {
    $orderRecord = new StubOrderModel;

    // 1. Admin: cancel and refund actions are authorized and visible
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $cancelActionAdmin = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundActionAdmin = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelActionAdmin->isAuthorized())->toBeTrue()
        ->and($cancelActionAdmin->isVisible())->toBeTrue()
        ->and($refundActionAdmin->isAuthorized())->toBeTrue()
        ->and($refundActionAdmin->isVisible())->toBeTrue();

    // 2. Manager: cancel is authorized and visible; refund is denied and hidden
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $cancelActionManager = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundActionManager = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelActionManager->isAuthorized())->toBeTrue()
        ->and($cancelActionManager->isVisible())->toBeTrue()
        ->and($refundActionManager->isAuthorized())->toBeFalse()
        ->and($refundActionManager->isVisible())->toBeFalse();

    // 3. Staff: both cancel and refund are denied and hidden
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $cancelActionStaff = Action::make('cancel')->record($orderRecord)->authorize('cancel');
    $refundActionStaff = Action::make('refund')->record($orderRecord)->authorize('refund');

    expect($cancelActionStaff->isAuthorized())->toBeFalse()
        ->and($cancelActionStaff->isVisible())->toBeFalse()
        ->and($refundActionStaff->isAuthorized())->toBeFalse()
        ->and($refundActionStaff->isVisible())->toBeFalse();
});

test('Filament authorization is strictly permission-driven without role name dependency', function () {
    // Create a user with NO role at all
    $customUser = User::factory()->create(['can_access_admin_panel' => true]);
    $customUser->givePermissionTo('orders.refund');
    $this->actingAs($customUser);

    $orderRecord = new StubOrderModel;
    $refundAction = Action::make('refund')->record($orderRecord)->authorize('refund');
    $cancelAction = Action::make('cancel')->record($orderRecord)->authorize('cancel');

    // Granted permission works immediately without needing a specific role name
    expect($refundAction->isAuthorized())->toBeTrue()
        ->and($refundAction->isVisible())->toBeTrue()
        // Ungranted permission is denied
        ->and($cancelAction->isAuthorized())->toBeFalse()
        ->and($cancelAction->isVisible())->toBeFalse();
});

test('Separation of concerns: panel access and business authorization remain independent', function () {
    // An Admin without panel access has policy authorization but cannot enter /admin
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');
    $this->actingAs($adminWithoutPanel);

    expect(get_authorization_response('viewAny', StubProductModel::class)->allowed())->toBeTrue();

    $this->get('/admin')->assertForbidden();

    // A Manager with panel access can enter /admin
    $managerWithPanel = User::factory()->create(['can_access_admin_panel' => true]);
    $managerWithPanel->assignRole('Manager');
    $this->actingAs($managerWithPanel);

    $this->get('/admin')->assertOk();
});
