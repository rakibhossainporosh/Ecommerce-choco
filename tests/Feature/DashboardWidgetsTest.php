<?php

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

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
    $response->assertSuccessful();
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
        ->assertSee('1 Items');
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
