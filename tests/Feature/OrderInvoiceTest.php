<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('1. Guest cannot access order invoice', function () {
    $order = Order::factory()->create();

    $this->get(route('admin.orders.invoice', $order))
        ->assertRedirect('/admin/login');
});

test('2. Regular user without admin access cannot view order invoice', function () {
    $user = User::factory()->create(['can_access_admin_panel' => false]);
    $order = Order::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.orders.invoice', $order))
        ->assertForbidden();
});

test('3. Admin can view order invoice with complete order data and item snapshots', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'order_number' => 'ORD-INV-999',
        'customer_name' => 'Michael Corleone',
        'customer_phone' => '01712345678',
        'customer_email' => 'michael@example.com',
        'shipping_address_line' => 'House 42, Road 11',
        'shipping_city' => 'Dhaka',
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::Cod,
        'subtotal' => 2000.00,
        'discount_amount' => 200.00,
        'shipping_amount' => 80.00,
        'grand_total' => 1880.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Belgian Dark Truffle 85%',
        'variant_name' => 'Box of 12',
        'sku' => 'BELG-TRUF-85',
        'unit_price' => 1000.00,
        'quantity' => 2,
        'discount_amount' => 100.00,
        'line_total' => 1900.00,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.invoice', $order))
        ->assertOk()
        ->assertSee('ORD-INV-999')
        ->assertSee('Michael Corleone')
        ->assertSee('01712345678')
        ->assertSee('House 42, Road 11')
        ->assertSee('Belgian Dark Truffle 85%')
        ->assertSee('Box of 12')
        ->assertSee('BELG-TRUF-85')
        ->assertSee('1,880.00');
});
