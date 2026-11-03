<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderMailer;
use App\Support\OrderMailSettings;
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

test('packing slip requires admin access and orders.view', function () {
    $order = Order::factory()->create();

    $this->get(route('admin.orders.packing-slip', $order))
        ->assertRedirect('/admin/login');

    $user = User::factory()->create(['can_access_admin_panel' => true]);

    $this->actingAs($user)
        ->get(route('admin.orders.packing-slip', $order))
        ->assertForbidden();
});

test('admin can print packing slip without prices and with pick checklist', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'order_number' => 'ORD-PACK-100',
        'customer_name' => 'Warehouse Test',
        'customer_phone' => '01811112222',
        'shipping_address_line' => 'Bay 7, Warehouse Road',
        'shipping_city' => 'Dhaka',
        'admin_note' => 'Fragile — top of carton',
        'gift_message' => 'Happy birthday',
        'tags' => ['fragile', 'vip'],
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::Cod,
        'grand_total' => 2500.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Single Origin Bar',
        'variant_name' => '70%',
        'sku' => 'SO-70',
        'unit_price' => 1200.00,
        'quantity' => 2,
        'line_total' => 2400.00,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.packing-slip', $order))
        ->assertOk()
        ->assertSee('ORD-PACK-100')
        ->assertSee('Warehouse Test')
        ->assertSee('Single Origin Bar')
        ->assertSee('SO-70')
        ->assertSee('Happy birthday')
        ->assertSee('Fragile — top of carton')
        ->assertSee('fragile')
        ->assertDontSee('2,500.00')
        ->assertDontSee('1,200.00');
});

test('admin can save ops notes tags and gift message from order view', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('editOpsNotes', data: [
            'admin_note' => 'Call before delivery',
            'gift_message' => 'For Ammu',
            'tags' => ['priority', 'vip'],
        ])
        ->assertHasNoActionErrors();

    $order->refresh();

    expect($order->admin_note)->toBe('Call before delivery')
        ->and($order->gift_message)->toBe('For Ammu')
        ->and($order->tagList())->toBe(['priority', 'vip']);
});

test('admin can correct shipping address before shipment', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'shipping_address_line' => 'Old Street 1',
        'shipping_city' => 'Dhaka',
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('editShippingAddress', data: [
            'customer_name' => 'Corrected Name',
            'customer_phone' => '01799998888',
            'shipping_address_line' => 'New House 9',
            'shipping_area' => 'Gulshan',
            'shipping_city' => 'Dhaka',
            'shipping_postcode' => '1212',
            'shipping_country' => 'Bangladesh',
        ])
        ->assertHasNoActionErrors();

    $order->refresh();

    expect($order->customer_name)->toBe('Corrected Name')
        ->and($order->shipping_address_line)->toBe('New House 9')
        ->and($order->shipping_area)->toBe('Gulshan')
        ->and($order->shipping_postcode)->toBe('1212');
});

test('order view shows ops notes card content', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'admin_note' => 'Hold for pickup',
        'gift_message' => 'Eid Mubarak',
        'tags' => ['cod-risk'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee('Ops notes & tags')
        ->assertSee('Hold for pickup')
        ->assertSee('Eid Mubarak')
        ->assertSee('cod-risk')
        ->assertSee('Status & activity');
});

test('resend status email action invokes order mailer for current step', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'customer_email' => 'buyer@example.com',
    ]);

    OrderMailSettings::save([
        'mail_enabled' => true,
        'mail_provider' => 'gmail',
        'mail_gmail_address' => 'shop@gmail.com',
        'mail_gmail_password' => 'secret-app-pass',
        'mail_admin_address' => 'admin@noboshop.com',
        'rules' => OrderMailSettings::defaultRules(),
    ]);

    $mailer = Mockery::mock(OrderMailer::class);
    $mailer->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (Order $sent): bool => $sent->is($order)), 'confirmed');
    $this->app->instance(OrderMailer::class, $mailer);

    $this->actingAs($admin);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('resendStatusEmail')
        ->assertHasNoActionErrors();
});
