<?php

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Inventory::truncate();
    ProductVariant::truncate();
    Product::truncate();
    Order::truncate();
    Customer::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->seed(StorefrontSeeder::class);
});

test('customer can register and credentials are saved in customers table', function () {
    $response = $this->post('/register', [
        'name' => 'হাসান মাহমুদ',
        'phone' => '01712345678',
        'email' => 'hasan@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect('/account');
    $this->assertTrue(Auth::guard('customer')->check());
    $this->assertEquals('হাসান মাহমুদ', Auth::guard('customer')->user()->name);

    $this->assertDatabaseHas('customers', [
        'phone' => '01712345678',
        'name' => 'হাসান মাহমুদ',
        'email' => 'hasan@example.com',
    ]);

    $customer = Customer::where('phone', '01712345678')->first();
    $this->assertTrue(Hash::check('secret123', $customer->password));
});

test('customer can login with phone and password', function () {
    $customer = Customer::create([
        'name' => 'তানভীর রহমান',
        'phone' => '01812345678',
        'email' => 'tanvir@example.com',
        'password' => 'my-password-123',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'login' => '01812345678',
        'password' => 'my-password-123',
    ]);

    $response->assertRedirect('/account');
    $this->assertTrue(Auth::guard('customer')->check());
    $this->assertEquals($customer->id, Auth::guard('customer')->id());
});

test('customer login fails with incorrect password', function () {
    Customer::create([
        'name' => 'তানভীর রহমান',
        'phone' => '01812345678',
        'password' => 'correct-pass',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'login' => '01812345678',
        'password' => 'wrong-pass',
    ]);

    $response->assertSessionHasErrors(['login']);
    $this->assertFalse(Auth::guard('customer')->check());
});

test('guest cannot access account page without logging in', function () {
    $response = $this->get('/account');
    $response->assertRedirect('/login');
});

test('authenticated customer can view account dashboard and order history', function () {
    $customer = Customer::create([
        'name' => 'সাকিব আল হাসান',
        'phone' => '01912345678',
        'password' => 'pass123456',
        'is_active' => true,
    ]);

    Auth::guard('customer')->login($customer);

    $response = $this->get('/account');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Customer/Account', false)
        ->has('customer', fn (Assert $c) => $c
            ->where('id', $customer->id)
            ->where('name', 'সাকিব আল হাসান')
            ->where('phone', '01912345678')
            ->etc()
        )
        ->has('orders')
        ->has('stats')
    );
});

test('authenticated customer can logout', function () {
    $customer = Customer::create([
        'name' => 'মাহমুদুল্লাহ',
        'phone' => '01612345678',
        'password' => 'pass123456',
        'is_active' => true,
    ]);

    Auth::guard('customer')->login($customer);
    $this->assertTrue(Auth::guard('customer')->check());

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertFalse(Auth::guard('customer')->check());
});

test('order placement triggers SMS notification successfully', function () {
    Log::spy();

    $product = Product::where('slug', 'cotton-shirt')->first();
    $variant = $product->defaultVariant;
    $variant->inventory->update(['quantity' => 15]);

    $response = $this->post('/checkout', [
        'customer_name' => 'নজরুল ইসলাম',
        'customer_phone' => '01799999999',
        'shipping_address' => 'বনানী, ঢাকা',
        'shipping_city' => 'Dhaka',
        'shipping_amount' => 60,
        'cart_items' => [
            [
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('orders', 1);

    $order = Order::first();
    $this->assertNotNull($order);

    // Verify Log driver logged the SMS
    Log::shouldHaveReceived('info')->withArgs(function ($message) use ($order) {
        return str_contains($message, 'SMS [LOG DRIVER]') && str_contains($message, $order->order_number);
    });
});
