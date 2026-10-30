<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    DB::table('order_status_histories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_status_histories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/*
|--------------------------------------------------------------------------
| CAT-9C: ORDER AUTHORIZATION TESTS
|--------------------------------------------------------------------------
*/

test('1. Admin authorizes all order operations via centralized Gate::before', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Spatie permission checks via Gate
    expect(Gate::forUser($admin)->allows('orders.view'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('orders.confirm'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('orders.process'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('orders.ship'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('orders.deliver'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('orders.cancel'))->toBeTrue();

    // OrderPolicy method checks via Gate
    expect($admin->can('viewAny', Order::class))->toBeTrue()
        ->and($admin->can('view', $order))->toBeTrue()
        ->and($admin->can('confirm', $order))->toBeTrue()
        ->and($admin->can('process', $order))->toBeTrue()
        ->and($admin->can('ship', $order))->toBeTrue()
        ->and($admin->can('deliver', $order))->toBeTrue()
        ->and($admin->can('cancel', $order))->toBeTrue();
});

test('2. Admin has zero direct database permissions but retains full order access', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    // Admin role has zero direct DB permission assignments
    expect($admin->permissions)->toBeEmpty()
        ->and($admin->getPermissionsViaRoles())->toBeEmpty();

    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

    expect(Gate::forUser($admin)->allows('confirm', $order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('process', $order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('ship', $order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('deliver', $order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('cancel', $order))->toBeTrue();
});

test('3. Manager can view, confirm, process, ship, deliver, and cancel orders', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Raw permissions
    expect($manager->can('orders.view'))->toBeTrue()
        ->and($manager->can('orders.confirm'))->toBeTrue()
        ->and($manager->can('orders.process'))->toBeTrue()
        ->and($manager->can('orders.ship'))->toBeTrue()
        ->and($manager->can('orders.deliver'))->toBeTrue()
        ->and($manager->can('orders.cancel'))->toBeTrue();

    // Policy methods via Gate
    expect($manager->can('viewAny', Order::class))->toBeTrue()
        ->and($manager->can('view', $order))->toBeTrue()
        ->and($manager->can('confirm', $order))->toBeTrue()
        ->and($manager->can('process', $order))->toBeTrue()
        ->and($manager->can('ship', $order))->toBeTrue()
        ->and($manager->can('deliver', $order))->toBeTrue()
        ->and($manager->can('cancel', $order))->toBeTrue();
});

test('4. Staff can view orders', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect($staff->can('orders.view'))->toBeTrue()
        ->and($staff->can('viewAny', Order::class))->toBeTrue()
        ->and($staff->can('view', $order))->toBeTrue();
});

test('5. Staff cannot confirm, process, ship, deliver, or cancel orders', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Raw permission checks
    expect($staff->can('orders.confirm'))->toBeFalse()
        ->and($staff->can('orders.process'))->toBeFalse()
        ->and($staff->can('orders.ship'))->toBeFalse()
        ->and($staff->can('orders.deliver'))->toBeFalse()
        ->and($staff->can('orders.cancel'))->toBeFalse();

    // Policy ability checks
    expect($staff->can('confirm', $order))->toBeFalse()
        ->and($staff->can('process', $order))->toBeFalse()
        ->and($staff->can('ship', $order))->toBeFalse()
        ->and($staff->can('deliver', $order))->toBeFalse()
        ->and($staff->can('cancel', $order))->toBeFalse();
});

test('6. Unauthenticated/non-authorized user cannot access Order policy abilities', function () {
    $user = User::factory()->create(); // Zero roles and zero permissions
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect($user->can('orders.view'))->toBeFalse()
        ->and($user->can('orders.confirm'))->toBeFalse()
        ->and($user->can('orders.process'))->toBeFalse()
        ->and($user->can('orders.ship'))->toBeFalse()
        ->and($user->can('orders.deliver'))->toBeFalse()
        ->and($user->can('orders.cancel'))->toBeFalse();

    expect($user->can('viewAny', Order::class))->toBeFalse()
        ->and($user->can('view', $order))->toBeFalse()
        ->and($user->can('confirm', $order))->toBeFalse()
        ->and($user->can('process', $order))->toBeFalse()
        ->and($user->can('ship', $order))->toBeFalse()
        ->and($user->can('deliver', $order))->toBeFalse()
        ->and($user->can('cancel', $order))->toBeFalse();
});

test('7. Permission-based authorization works through Spatie permissions directly', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // User initially has zero access
    expect($user->can('confirm', $order))->toBeFalse();

    // Explicitly grant only orders.confirm
    $user->givePermissionTo('orders.confirm');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($user->can('confirm', $order))->toBeTrue()
        ->and($user->can('process', $order))->toBeFalse()
        ->and($user->can('ship', $order))->toBeFalse()
        ->and($user->can('deliver', $order))->toBeFalse()
        ->and($user->can('cancel', $order))->toBeFalse()
        ->and($user->can('view', $order))->toBeFalse();
});

test('8. Removing a permission from a Manager causes the corresponding policy ability to fail', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

    expect($manager->can('cancel', $order))->toBeTrue();

    // Revoke orders.cancel from Manager role
    $managerRole = Role::findByName('Manager', 'web');
    $managerRole->revokePermissionTo('orders.cancel');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($manager->fresh()->can('cancel', $order))->toBeFalse()
        ->and($manager->can('confirm', $order))->toBeTrue()
        ->and($manager->can('process', $order))->toBeTrue();
});

test('9. Policy authorization does not bypass CAT-9B domain transition rules', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    // Order is in Pending status
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // 1. Manager has permission to ship orders
    expect($manager->can('ship', $order))->toBeTrue();

    // 2. But pending -> shipped is an invalid domain transition
    // Policy permission check succeeds, but executing the domain action throws InvalidOrderTransitionException
    expect(fn () => $order->ship())
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from pending to shipped.');

    // Status remains unchanged
    expect($order->status)->toBe(OrderStatus::Pending);
});

test('10. Authorized Manager attempting invalid cancellation receives InvalidOrderTransitionException', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    // Order is already Delivered (terminal state)
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    // Manager has permission to cancel
    expect($manager->can('cancel', $order))->toBeTrue();

    // Attempting to cancel a delivered order fails at the domain layer
    expect(fn () => $order->cancel('Customer wants refund after delivery', $manager))
        ->toThrow(InvalidOrderTransitionException::class, 'Cannot transition order from delivered to cancelled.');

    expect($order->status)->toBe(OrderStatus::Delivered);
});

test('11. Laravel automatically resolves OrderPolicy for Order model', function () {
    $policy = Gate::getPolicyFor(Order::class);

    expect($policy)->toBeInstanceOf(OrderPolicy::class);
});

test('12. Unauthenticated guest is denied all order operations via Gate', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect(Gate::allows('viewAny', Order::class))->toBeFalse()
        ->and(Gate::allows('view', $order))->toBeFalse()
        ->and(Gate::allows('confirm', $order))->toBeFalse()
        ->and(Gate::allows('process', $order))->toBeFalse()
        ->and(Gate::allows('ship', $order))->toBeFalse()
        ->and(Gate::allows('deliver', $order))->toBeFalse()
        ->and(Gate::allows('cancel', $order))->toBeFalse();
});

test('13. OrderPolicy direct instance methods evaluate correct permission strings', function () {
    $policy = new OrderPolicy;
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $confirmer = User::factory()->create();
    $confirmer->givePermissionTo('orders.confirm');

    $processor = User::factory()->create();
    $processor->givePermissionTo('orders.process');

    $shipper = User::factory()->create();
    $shipper->givePermissionTo('orders.ship');

    $deliverer = User::factory()->create();
    $deliverer->givePermissionTo('orders.deliver');

    $canceller = User::factory()->create();
    $canceller->givePermissionTo('orders.cancel');

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('orders.view');

    expect($policy->confirm($confirmer, $order))->toBeTrue()
        ->and($policy->confirm($processor, $order))->toBeFalse();

    expect($policy->process($processor, $order))->toBeTrue()
        ->and($policy->process($shipper, $order))->toBeFalse();

    expect($policy->ship($shipper, $order))->toBeTrue()
        ->and($policy->ship($deliverer, $order))->toBeFalse();

    expect($policy->deliver($deliverer, $order))->toBeTrue()
        ->and($policy->deliver($canceller, $order))->toBeFalse();

    expect($policy->cancel($canceller, $order))->toBeTrue()
        ->and($policy->cancel($viewer, $order))->toBeFalse();

    expect($policy->view($viewer, $order))->toBeTrue()
        ->and($policy->viewAny($viewer))->toBeTrue()
        ->and($policy->view($confirmer, $order))->toBeFalse();
});

test('14. OrderPolicy methods enforce strict concrete Order parameter type with no default value', function () {
    $reflection = new ReflectionClass(OrderPolicy::class);

    $strictMethods = ['view', 'confirm', 'process', 'ship', 'deliver', 'cancel'];

    foreach ($strictMethods as $methodName) {
        expect($reflection->hasMethod($methodName))->toBeTrue();

        $method = $reflection->getMethod($methodName);
        $parameters = $method->getParameters();

        expect($parameters)->toHaveCount(2)
            ->and($parameters[0]->getName())->toBe('user')
            ->and($parameters[0]->getType()?->getName())->toBe(User::class)
            ->and($parameters[0]->isDefaultValueAvailable())->toBeFalse()
            ->and($parameters[1]->getName())->toBe('order')
            ->and($parameters[1]->getType()?->getName())->toBe(Order::class)
            ->and($parameters[1]->isDefaultValueAvailable())->toBeFalse()
            ->and($parameters[1]->allowsNull())->toBeFalse();

        expect($method->getReturnType()?->getName())->toBe('bool');
    }

    $viewAny = $reflection->getMethod('viewAny');
    $viewAnyParams = $viewAny->getParameters();
    expect($viewAnyParams)->toHaveCount(1)
        ->and($viewAnyParams[0]->getName())->toBe('user')
        ->and($viewAnyParams[0]->getType()?->getName())->toBe(User::class)
        ->and($viewAnyParams[0]->isDefaultValueAvailable())->toBeFalse()
        ->and($viewAny->getReturnType()?->getName())->toBe('bool');
});
