<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
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
| CAT-9D: FILAMENT ORDER RESOURCE TESTS
|--------------------------------------------------------------------------
*/

test('1. Admin can access OrderResource index and view page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create();

    $this->actingAs($admin)
        ->get(OrderResource::getUrl('index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk();
});

test('2. Manager can access OrderResource index and view page', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create();

    $this->actingAs($manager)
        ->get(OrderResource::getUrl('index'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk();
});

test('3. Staff can view OrderResource index and view page', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $order = Order::factory()->create();

    $this->actingAs($staff)
        ->get(OrderResource::getUrl('index'))
        ->assertOk();

    $this->actingAs($staff)
        ->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk();
});

test('4. Staff cannot execute lifecycle actions', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($staff);

    Livewire::test(ListOrders::class)
        ->assertTableActionHidden('confirm', $order)
        ->assertTableActionHidden('process', $order)
        ->assertTableActionHidden('ship', $order)
        ->assertTableActionHidden('deliver', $order)
        ->assertTableActionHidden('cancel', $order);
});

test('5. Manager can see lifecycle actions according to domain transitions', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $pendingOrder = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->assertTableActionVisible('confirm', $pendingOrder)
        ->assertTableActionVisible('cancel', $pendingOrder)
        ->assertTableActionHidden('process', $pendingOrder)
        ->assertTableActionHidden('ship', $pendingOrder)
        ->assertTableActionHidden('deliver', $pendingOrder);
});

test('6. Confirm action calls existing Order domain method', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('confirm', $order)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Order Confirmed');

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('7. Process action calls existing domain method', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('process', $order)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Order Processing Started');

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('8. Ship action calls existing domain method', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Processing]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('ship', $order)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Order Shipped');

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('9. Deliver action calls existing domain method', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('deliver', $order)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Order Delivered');

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

test('10. Cancel requires a non-empty reason and enforces validation', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('cancel', $order, [
            'cancellation_reason' => '',
        ])
        ->assertHasTableActionErrors(['cancellation_reason' => 'required']);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('11. Cancel calls existing domain method and records metadata', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);

    Livewire::test(ListOrders::class)
        ->callTableAction('cancel', $order, [
            'cancellation_reason' => 'Customer requested cancellation via phone call',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Order Cancelled');

    $freshOrder = $order->fresh();
    expect($freshOrder->status)->toBe(OrderStatus::Cancelled)
        ->and($freshOrder->cancellation_reason)->toBe('Customer requested cancellation via phone call')
        ->and($freshOrder->cancelled_by)->toBe($manager->id)
        ->and($freshOrder->cancelled_at)->not->toBeNull();
});

test('12. Stale UI invalid transition is handled cleanly through notification without bypassing domain rules', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    $this->actingAs($manager);

    $action = OrderActions::makeConfirmAction();
    $action->record($order);
    $action->call();

    $notifications = session()->get('filament.notifications');
    expect($notifications)->toBeArray()->not->toBeEmpty();
    $latest = end($notifications);

    expect($latest['title'])->toBe('Transition Failed')
        ->and($latest['body'])->toContain('Cannot transition order from delivered to confirmed')
        ->and($latest['status'])->toBe('danger');

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

test('13. Order list displays correct snapshot and order data', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'order_number' => 'ORD-999888',
        'customer_name' => 'Kazi Nazrul',
        'customer_phone' => '01711000999',
        'customer_email' => 'nazrul@example.com',
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::Cod,
        'grand_total' => 1550.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertSee('ORD-999888')
        ->assertSee('Kazi Nazrul')
        ->assertSee('01711000999')
        ->assertSee('Confirmed')
        ->assertSee('Paid')
        ->assertSee('Cash on Delivery')
        ->assertSee('1,550.00');
});

test('14. Order detail view displays OrderItem snapshot fields without relying on active products', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'order_number' => 'ORD-123456',
        'customer_name' => 'Begum Rokeya',
        'customer_phone' => '01811223344',
        'customer_email' => 'rokeya@example.com',
        'billing_same_as_shipping' => true,
        'subtotal' => 1200.00,
        'discount_amount' => 100.00,
        'shipping_amount' => 60.00,
        'grand_total' => 1160.00,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Historical Dark Chocolate 70%',
        'variant_name' => 'Special Edition 150g',
        'sku' => 'HIST-CHOCO-70',
        'unit_price' => 600.00,
        'quantity' => 2,
        'discount_amount' => 50.00,
        'line_total' => 1150.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee('ORD-123456')
        ->assertSee('Begum Rokeya')
        ->assertSee('01811223344')
        ->assertSee('rokeya@example.com')
        ->assertSee('Same as shipping address')
        ->assertSee('Historical Dark Chocolate 70%')
        ->assertSee('Special Edition 150g')
        ->assertSee('HIST-CHOCO-70')
        ->assertSee('600.00')
        ->assertSee('1,150.00')
        ->assertSee('1,160.00');
});

test('15. Order detail view displays billing address snapshot when billing is different from shipping', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'billing_same_as_shipping' => false,
        'billing_address_line' => '12/A Dhanmondi R/A',
        'billing_area' => 'Dhanmondi',
        'billing_city' => 'Dhaka',
        'billing_postcode' => '1209',
        'billing_country' => 'Bangladesh',
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee('12/A Dhanmondi R/A')
        ->assertSee('Dhanmondi')
        ->assertSee('1209')
        ->assertDontSee('Same as shipping address');
});

test('16. No delete action, bulk delete action, or delete permissions exist for orders', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create();

    expect(OrderResource::canDelete($order))->toBeFalse()
        ->and(OrderResource::canDeleteAny())->toBeFalse();

    $this->actingAs($admin);

    Livewire::test(ListOrders::class)
        ->assertTableActionDoesNotExist('delete');
});

test('17. No create or edit workflow exists for OrderResource', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create();

    expect(OrderResource::canCreate())->toBeFalse()
        ->and(OrderResource::canEdit($order))->toBeFalse();

    $this->actingAs($admin);

    // Routes do not exist in Filament
    $this->get('/admin/orders/create')->assertNotFound();
    $this->get("/admin/orders/{$order->id}/edit")->assertNotFound();
});

test('18. View page header actions allow lifecycle execution by authorized manager', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionVisible('confirm')
        ->assertActionVisible('cancel')
        ->callAction('confirm')
        ->assertHasNoActionErrors()
        ->assertNotified('Order Confirmed');

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('19. View page header actions reject execution by staff', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($staff);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionHidden('confirm')
        ->assertActionHidden('cancel');
});

test('20. Unauthenticated guest is redirected to login', function () {
    $this->get(OrderResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});
