<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Notifications\OrderStatusAlert;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('order status change stores an account alert for the customer', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['is_active' => true]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'status' => OrderStatus::Pending,
    ]);

    $order->update(['status' => OrderStatus::Confirmed]);

    Notification::assertSentTo(
        $customer,
        OrderStatusAlert::class,
        function (OrderStatusAlert $notification) use ($order): bool {
            return $notification->order->is($order)
                && $notification->step === OrderStatus::Confirmed->value;
        }
    );
});

test('account page lists order status alerts', function () {
    $customer = Customer::factory()->create(['is_active' => true]);
    $order = Order::withoutEvents(fn () => Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'order_number' => 'ORD-ALERT-9',
        'status' => OrderStatus::Pending,
    ]));

    $customer->notify(new OrderStatusAlert($order, OrderStatus::Shipped->value));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.account'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Customer/Account')
            ->has('notifications', 1)
            ->where('notifications.0.title', __('storefront.account.order_alert_title', ['number' => 'ORD-ALERT-9']))
            ->where('notifications.0.read', false)
        );
});
