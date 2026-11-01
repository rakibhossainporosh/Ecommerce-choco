<?php

use App\Enums\OrderStatus;
use App\Filament\Pages\FraudCheckSettings;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Livewire\OrderFraudCheck;
use App\Models\Order;
use App\Models\User;
use App\Support\FraudCheckSettings as Settings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an order shows earlier deliveries and cancellations for the same mobile number', function () {
    Order::factory()->create([
        'customer_phone' => '01711111111',
        'status' => OrderStatus::Delivered,
    ]);
    Order::factory()->create([
        'customer_phone' => '8801711111111',
        'status' => OrderStatus::Cancelled,
    ]);
    Order::factory()->create([
        'customer_phone' => '01722222222',
        'status' => OrderStatus::Cancelled,
    ]);
    $order = Order::factory()->create([
        'customer_phone' => '01711111111',
        'status' => OrderStatus::Pending,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(OrderFraudCheck::class, ['orderId' => $order->id])
        ->assertSee('Earlier orders from this number')
        ->assertSee('Check before confirming')
        ->assertDontSee('First order from this number');

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee('Fraud check')
        ->assertSee('This does not cancel the order.');
});

test('steadfast results show after a check and the key stays out of the log', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://portal.steadfast.com.bd/api/v1/fraud_check/01711111111' => Http::response([
            'status' => 200,
            'data' => [
                'total_parcels' => 4,
                'total_delivered_parcels' => 1,
                'total_cancelled_parcels' => 3,
            ],
        ]),
    ]);

    $messages = [];
    Log::listen(function ($event) use (&$messages): void {
        $messages[] = $event->message;
    });

    Settings::save([
        'fraud_check_enabled' => true,
        'fraud_steadfast_api_key' => 'sf-key',
        'fraud_steadfast_secret_key' => 'sf-secret',
    ]);

    $order = Order::factory()->create([
        'customer_phone' => '01711111111',
        'status' => OrderStatus::Pending,
    ]);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(OrderFraudCheck::class, ['orderId' => $order->id])
        ->call('check')
        ->assertSee('Parcels')
        ->assertSee('Many cancellations');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://portal.steadfast.com.bd/api/v1/fraud_check/01711111111'
            && $request->hasHeader('Api-Key', 'sf-key')
            && $request->hasHeader('Secret-Key', 'sf-secret')
            && ! str_contains($request->url(), 'sf-key');
    });

    expect(implode(' ', $messages))->not->toContain('sf-key')
        ->and(implode(' ', $messages))->not->toContain('sf-secret');
});

test('a missing steadfast key does not call the courier', function () {
    Http::preventStrayRequests();
    Http::fake();

    $order = Order::factory()->create(['customer_phone' => '01711111111']);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(OrderFraudCheck::class, ['orderId' => $order->id])
        ->call('check')
        ->assertSee('The outside check is off');

    Http::assertNothingSent();
});

test('admin can save the steadfast key and a blank box keeps it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(FraudCheckSettings::class)
        ->fillForm([
            'fraud_check_enabled' => true,
            'fraud_provider' => 'steadfast',
            'fraud_steadfast_api_key' => 'sf-key',
            'fraud_steadfast_secret_key' => 'sf-secret',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::ready())->toBeTrue()
        ->and(Settings::credential('fraud_steadfast_api_key'))->toBe('sf-key');

    Livewire::test(FraudCheckSettings::class)
        ->fillForm([
            'fraud_check_enabled' => true,
            'fraud_provider' => 'steadfast',
            'fraud_steadfast_api_key' => null,
            'fraud_steadfast_secret_key' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::credential('fraud_steadfast_api_key'))->toBe('sf-key')
        ->and(Settings::credential('fraud_steadfast_secret_key'))->toBe('sf-secret');
});

test('any courier link can be checked when the answer words are filled in', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://fraud.example.com/check' => Http::response([
            'result' => [
                'all' => 6,
                'ok' => 2,
                'no' => 4,
            ],
        ]),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(FraudCheckSettings::class)
        ->fillForm([
            'fraud_check_enabled' => true,
            'fraud_provider' => 'custom',
            'fraud_company_name' => 'Pathao Check',
            'fraud_custom_url' => 'https://fraud.example.com/check',
            'fraud_custom_method' => 'post',
            'fraud_phone_place' => 'field',
            'fraud_phone_field' => 'mobile',
            'fraud_key_name' => 'X-Api-Key',
            'fraud_custom_api_key' => 'custom-key',
            'fraud_extra_key_name' => '',
            'fraud_key_on_link' => false,
            'fraud_word_total' => 'all',
            'fraud_word_delivered' => 'ok',
            'fraud_word_cancelled' => 'no',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $order = Order::factory()->create(['customer_phone' => '01711111111']);

    Livewire::test(OrderFraudCheck::class, ['orderId' => $order->id])
        ->assertSee('Pathao Check')
        ->call('check')
        ->assertSee('Many cancellations');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://fraud.example.com/check'
            && $request->method() === 'POST'
            && $request->hasHeader('X-Api-Key', 'custom-key')
            && $request['mobile'] === '01711111111'
            && ! str_contains($request->url(), 'custom-key');
    });
});

test('a private fraud link is rejected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(FraudCheckSettings::class)
        ->fillForm([
            'fraud_check_enabled' => true,
            'fraud_provider' => 'custom',
            'fraud_custom_url' => 'http://127.0.0.1/fraud',
            'fraud_custom_method' => 'get',
            'fraud_phone_place' => 'link',
            'fraud_key_name' => 'Api-Key',
            'fraud_custom_api_key' => 'secret-key',
            'fraud_word_total' => 'total',
            'fraud_word_delivered' => 'delivered',
            'fraud_word_cancelled' => 'cancelled',
        ])
        ->call('save')
        ->assertHasFormErrors(['fraud_custom_url']);
});

test('staff cannot open fraud check settings', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    Livewire::test(FraudCheckSettings::class)->assertForbidden();
});
