<?php

use App\Models\Customer;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('customer account exposes notifications and can mark them read', function () {
    $customer = Customer::factory()->create([
        'phone' => '01719998877',
        'password' => 'password',
        'is_active' => true,
    ]);

    Notification::make()
        ->title('Return request update')
        ->body('Your return RMA-TEST is now Approved.')
        ->sendToDatabase($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.account'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Customer/Account')
            ->has('notifications', 1)
            ->where('notifications.0.title', 'Return request update')
            ->where('notifications.0.read', false)
        );

    $notificationId = $customer->notifications()->first()->id;

    $this->actingAs($customer, 'customer')
        ->post(route('customer.notifications.read', $notificationId))
        ->assertRedirect();

    expect($customer->fresh()->notifications()->first()->read_at)->not->toBeNull();
});
