<?php

use App\Filament\Widgets\LatestActivities;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WorkspaceOverview;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\UiText;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);
    $this->admin->assignRole('Admin');
});

test('admin can access dashboard and view widgets', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin');
    $response->assertSuccessful()
        ->assertSeeLivewire(WorkspaceOverview::class)
        ->assertSeeLivewire(StatsOverview::class);
});

test('payments-only dashboard stats labels stay strings on windows locales', function () {
    $role = Role::create([
        'name' => 'Payments desk',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo([
        'dashboard.view',
        'payments.view',
    ]);

    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);
    $user->assignRole($role);

    $this->actingAs($user);

    foreach (['en', 'bn'] as $locale) {
        app()->setLocale($locale);

        Livewire::test(StatsOverview::class)
            ->assertSuccessful()
            ->assertSee(UiText::line('Payments'))
            ->assertDontSee('Total Revenue');
    }
});

test('limited module users still see a useful dashboard', function () {
    $role = Role::create([
        'name' => 'Ops desk',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo([
        'dashboard.view',
        'categories.view',
        'courier-shipments.view',
        'activities.view',
    ]);

    $user = User::factory()->create([
        'name' => 'Desk Operator',
        'can_access_admin_panel' => true,
    ]);
    $user->assignRole($role);

    $this->actingAs($user);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSeeLivewire(WorkspaceOverview::class)
        ->assertSeeLivewire(StatsOverview::class)
        ->assertSeeLivewire(LatestActivities::class)
        ->assertDontSee('Total Revenue')
        ->assertSee('Categories')
        ->assertSee('Courier Shipments')
        ->assertSee('Activities');

    Livewire::test(WorkspaceOverview::class)
        ->assertSuccessful()
        ->assertSee('Desk Operator')
        ->assertSee('Ops desk')
        ->assertDontSee('Organize your product categories');

    Livewire::test(StatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Categories')
        ->assertSee('Courier Shipments')
        ->assertSee('Activities Today')
        ->assertDontSee('Total Revenue');

    Livewire::test(LatestActivities::class)
        ->assertSuccessful()
        ->assertSee('Recent Activity');
});

test('stats overview widget correctly calculates revenue, orders, customers and stock alert', function () {
    $this->actingAs($this->admin);

    // Create customers
    Customer::create([
        'name' => 'করিম সাহেব',
        'phone' => '01711111111',
        'is_active' => true,
    ]);

    // Create an order
    Order::create([
        'order_number' => 'ORD-TEST-101',
        'customer_name' => 'করিম সাহেব',
        'customer_phone' => '01711111111',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'payment_method' => 'cod',
        'currency' => 'BDT',
        'subtotal' => 1500,
        'grand_total' => 1560,
        'shipping_address_line' => 'ঢাকা',
        'shipping_area' => 'Dhaka',
        'shipping_city' => 'Dhaka',
    ]);

    $variant = ProductVariant::factory()->create();
    Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'low_stock_threshold' => 5,
    ]);

    Livewire::test(StatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Total Revenue')
        ->assertSee('1,560.00')
        ->assertSee('Total Orders')
        ->assertSee('1 pending review')
        ->assertSee('Total Customers')
        ->assertSee('Stock Alert')
        ->assertSee('1 items');
});

test('orders chart widget calculates data with filters', function () {
    $this->actingAs($this->admin);

    Livewire::test(OrdersChart::class)
        ->assertSuccessful()
        ->set('filter', '7days')
        ->assertSuccessful()
        ->set('filter', '12months')
        ->assertSuccessful();
});

test('latest orders widget renders table records', function () {
    $this->actingAs($this->admin);

    $order = Order::create([
        'order_number' => 'ORD-LATEST-999',
        'customer_name' => 'রহিম উদ্দিন',
        'customer_phone' => '01822222222',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'payment_method' => 'cod',
        'currency' => 'BDT',
        'subtotal' => 3000,
        'grand_total' => 3060,
        'shipping_address_line' => 'চট্টগ্রাম',
        'shipping_area' => 'Outside Dhaka',
        'shipping_city' => 'Chittagong',
    ]);

    Livewire::test(LatestOrders::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$order]);
});

test('order status chart widget calculates status breakdown', function () {
    $this->actingAs($this->admin);

    Livewire::test(OrderStatusChart::class)
        ->assertSuccessful();
});
