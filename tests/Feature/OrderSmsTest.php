<?php

use App\Enums\OrderStatus;
use App\Filament\Pages\SmsSettings;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderSmsSettings;
use ArrayObject;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    config([
        'services.sms.enabled' => true,
        'services.sms.driver' => 'log',
    ]);
});

test('a placed order texts the customer with the order number', function () {
    $messages = smsLog();

    $order = Order::factory()->create([
        'customer_name' => 'Karim',
        'customer_phone' => '01711111111',
        'order_number' => 'ORD-100001',
    ]);

    $sms = smsLines($messages);

    expect($sms)->toHaveCount(1)
        ->and($sms[0])->toContain('8801711111111')
        ->and($sms[0])->toContain($order->order_number)
        ->and($sms[0])->toContain('Karim');
});

test('order SMS stays off when the shop turns it off', function () {
    saveOrderSms(enabled: false);
    $messages = smsLog();

    Order::factory()->create([
        'customer_phone' => '01711111111',
    ]);

    expect(smsLines($messages))->toBe([]);
});

test('the shop can text every store number and skip the customer', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.greenweb.com.bd/*' => Http::response('Ok', 200),
    ]);

    $rules = OrderSmsSettings::defaultRules();
    $rules['placed'] = ['customer' => false, 'admin' => true];

    saveOrderSms($rules, [
        'sms_driver' => 'greenweb',
        'greenweb_token' => 'gw-secret-token',
        'phones' => ['01811111111', '01811111111', '01911111111'],
        'messages' => [
            'placed' => [
                'customer' => 'Customer {number}',
                'admin' => 'Store {number} for {name}',
            ],
        ],
    ]);

    Order::factory()->create([
        'customer_name' => 'Karim',
        'customer_phone' => '01711111111',
        'order_number' => 'ORD-200002',
    ]);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => $request['to'] === '8801811111111' && str_contains($request['message'], 'Store ORD-200002 for Karim'));
    Http::assertSent(fn ($request): bool => $request['to'] === '8801911111111' && $request['message'] === 'Store ORD-200002 for Karim');
    Http::assertNotSent(fn ($request): bool => $request['to'] === '8801711111111');
});

test('any SMS company sends the fields and header from its document', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://sms.example.com/*' => Http::response('OK', 200),
    ]);

    $rules = OrderSmsSettings::defaultRules();
    $rules['placed'] = ['customer' => true, 'admin' => false];

    saveOrderSms($rules, [
        'sms_driver' => 'custom',
        'sms_custom_url' => 'https://sms.example.com/send',
        'sms_custom_method' => 'json',
        'sms_custom_api_key' => 'other-secret',
        'sms_custom_sender_id' => 'NOBO',
        'sms_custom_fields' => [
            ['name' => 'token', 'value' => '{key}'],
            ['name' => 'number', 'value' => '{to}'],
            ['name' => 'text', 'value' => '{message}'],
            ['name' => 'type', 'value' => 'text'],
            ['name' => 'from', 'value' => '{sender}'],
        ],
        'sms_custom_headers' => [
            ['name' => 'Authorization', 'value' => 'Bearer {key}'],
        ],
        'messages' => [
            'placed' => [
                'customer' => 'Hello {number}',
                'admin' => 'Store {number}',
            ],
        ],
    ]);

    Order::factory()->create([
        'customer_phone' => '01711111111',
        'order_number' => 'ORD-400004',
    ]);

    $storedKey = Setting::query()->where('key', 'sms_custom_api_key')->value('value');

    Http::assertSentCount(1);
    Http::assertSent(function ($request): bool {
        $authorization = $request->header('Authorization');
        $authorization = is_array($authorization) ? ($authorization[0] ?? '') : (string) $authorization;

        $contentType = $request->header('Content-Type');
        $contentType = is_array($contentType) ? implode(' ', $contentType) : (string) $contentType;

        return $request['token'] === 'other-secret'
            && $request['number'] === '8801711111111'
            && $request['text'] === 'Hello ORD-400004'
            && $request['type'] === 'text'
            && $request['from'] === 'NOBO'
            && $authorization === 'Bearer other-secret'
            && str_contains($contentType, 'application/json');
    });

    expect($storedKey)->not->toBe('other-secret')
        ->and(DB::table('activity_log')->where('properties', 'like', '%other-secret%')->count())->toBe(0);
});

test('a private gateway link is not called', function () {
    Http::preventStrayRequests();
    Http::fake();

    saveOrderSms(extra: [
        'sms_driver' => 'custom',
        'sms_custom_url' => 'http://127.0.0.1/sms',
        'sms_custom_api_key' => 'other-secret',
        'sms_custom_fields' => OrderSmsSettings::defaultCustomFields(),
    ]);

    Order::factory()->create([
        'customer_phone' => '01711111111',
    ]);

    Http::assertNothingSent();
    expect(OrderSmsSettings::customUrl())->toBeNull();
});

test('document names are sent without the shop typing codes', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://sms.example.com/*' => Http::response('OK', 200),
    ]);

    $rules = OrderSmsSettings::defaultRules();
    $rules['placed'] = ['customer' => true, 'admin' => false];

    saveOrderSms($rules, [
        'sms_driver' => 'custom',
        'sms_custom_url' => 'https://sms.example.com/send',
        'sms_custom_method' => 'post',
        'sms_custom_api_key' => 'other-secret',
        'sms_custom_sender_id' => 'NOBO',
        'sms_field_key' => 'token',
        'sms_field_phone' => 'number',
        'sms_field_message' => 'text',
        'sms_field_sender' => 'from',
        'sms_custom_extra' => [
            ['name' => 'type', 'value' => 'text'],
        ],
        'messages' => [
            'placed' => [
                'customer' => 'Hello {number}',
                'admin' => 'Store {number}',
            ],
        ],
    ]);

    Order::factory()->create([
        'customer_phone' => '01711111111',
        'order_number' => 'ORD-500005',
    ]);

    Http::assertSent(fn ($request): bool => $request['token'] === 'other-secret'
        && $request['number'] === '8801711111111'
        && $request['text'] === 'Hello ORD-500005'
        && $request['type'] === 'text'
        && $request['from'] === 'NOBO');
});

test('a later order step uses that step message', function () {
    $rules = OrderSmsSettings::defaultRules();
    $rules['placed']['customer'] = false;
    $rules['shipped']['customer'] = true;
    saveOrderSms($rules, [
        'messages' => [
            'shipped' => [
                'customer' => 'Shipped {number}',
                'admin' => 'Store shipped {number}',
            ],
        ],
    ]);
    $messages = smsLog();

    $order = Order::factory()->create([
        'customer_phone' => '01711111111',
        'order_number' => 'ORD-300003',
        'status' => OrderStatus::Processing,
    ]);

    $order->update(['status' => OrderStatus::Shipped]);
    $order->update(['customer_name' => 'Changed']);

    expect(smsLines($messages))->toHaveCount(1)
        ->and(smsLines($messages)[0])->toContain('Shipped ORD-300003');
});

test('a new account texts the customer the saved welcome', function () {
    saveOrderSms(extra: [
        'registered_message' => 'Welcome {name} to {shop}',
    ]);
    $messages = smsLog();

    $this->post('/register', [
        'name' => 'Hasan',
        'phone' => '01712345678',
        'email' => 'hasan@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertRedirect('/account');

    expect(smsLines($messages))->toHaveCount(1)
        ->and(smsLines($messages)[0])->toContain('Welcome Hasan to Nobo Shop')
        ->and(smsLines($messages)[0])->toContain('8801712345678');
});

test('gateway secrets are encrypted and kept out of the activity log', function () {
    saveOrderSms(extra: [
        'sms_driver' => 'greenweb',
        'greenweb_token' => 'gw-secret-token',
        'bulksmsbd_api_key' => 'bd-secret-key',
    ]);

    $stored = Setting::query()->where('key', 'greenweb_token')->value('value');

    saveOrderSms(extra: [
        'sms_driver' => 'bulksmsbd',
        'bulksmsbd_sender_id' => 'NOBO',
    ]);

    expect($stored)->not->toBe('gw-secret-token')
        ->and(OrderSmsSettings::credential('greenweb_token'))->toBe('gw-secret-token')
        ->and(OrderSmsSettings::credential('bulksmsbd_api_key'))->toBe('bd-secret-key')
        ->and(OrderSmsSettings::driver())->toBe('bulksmsbd')
        ->and(DB::table('activity_log')->where('properties', 'like', '%gw-secret-token%')->count())->toBe(0)
        ->and(DB::table('activity_log')->where('properties', 'like', '%bd-secret-key%')->count())->toBe(0);
});

test('admin can save SMS notifications', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(SmsSettings::class)
        ->assertSee('Customer SMS')
        ->assertSee('Store SMS')
        ->assertSee('sms-audience-grid')
        ->assertSee('sms-phone-list')
        ->assertSee('settings-choice-group-wide')
        ->fillForm([
            'sms_enabled' => false,
            'sms_driver' => 'bulksmsbd',
            'bulksmsbd_api_key' => 'bd-secret-key',
            'bulksmsbd_sender_id' => 'NOBO',
            'phones' => [
                ['number' => '01711111111'],
            ],
            'rules' => OrderSmsSettings::defaultRules(),
            'messages' => [
                'placed' => [
                    'customer' => 'Order {number}',
                    'admin' => 'Store {number}',
                ],
            ],
            'registered_enabled' => true,
            'registered_message' => 'Welcome {name}',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('sms_driver'))->toBe('bulksmsbd')
        ->and(OrderSmsSettings::enabled())->toBeFalse()
        ->and(OrderSmsSettings::hasCredential('bulksmsbd_api_key'))->toBeTrue()
        ->and(OrderSmsSettings::phones())->toBe(['01711111111'])
        ->and(OrderSmsSettings::message('placed', 'customer'))->toBe('Order {number}');
});

test('admin cannot save a store number that is not a mobile number', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(SmsSettings::class)
        ->fillForm([
            'sms_enabled' => false,
            'sms_driver' => 'greenweb',
            'phones' => [
                ['number' => '12345'],
            ],
            'rules' => OrderSmsSettings::defaultRules(),
            'messages' => [
                'placed' => [
                    'customer' => 'Order {number}',
                    'admin' => 'Store {number}',
                ],
            ],
            'registered_enabled' => true,
            'registered_message' => 'Welcome {name}',
        ])
        ->call('save')
        ->assertHasFormErrors(['phones.0.number']);
});

test('staff cannot open SMS notifications', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    Livewire::test(SmsSettings::class)->assertForbidden();
});

test('the page shows how many Greenweb SMS are left', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.greenweb.com.bd/*' => Http::response(['balance' => '1280'], 200),
    ]);

    saveOrderSms(extra: [
        'sms_driver' => 'greenweb',
        'greenweb_token' => 'gw-secret-token',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    $logs = smsLog();

    Livewire::test(SmsSettings::class)
        ->call('loadBalance')
        ->assertSet('balanceFigure', '1,280')
        ->call('loadBalance')
        ->call('refreshBalance')
        ->assertSet('balanceFigure', '1,280');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.greenweb.com.bd/g_api.php')
        && str_contains($request->url(), 'balance')
        && $request['token'] === 'gw-secret-token');

    expect(implode(' ', $logs->getArrayCopy()))->not->toContain('gw-secret-token');
});

test('the page shows the BulkSMSBD balance and hides a refused balance', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://bulksmsbd.net/*' => Http::sequence()
            ->push(['response_code' => 202, 'balance' => 49.65], 200)
            ->push(['response_code' => 1002], 200),
    ]);

    saveOrderSms(extra: [
        'sms_driver' => 'bulksmsbd',
        'bulksmsbd_api_key' => 'bd-secret-key',
        'bulksmsbd_sender_id' => 'NOBO',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(SmsSettings::class)
        ->call('loadBalance')
        ->assertSet('balanceFigure', '49.65')
        ->call('refreshBalance')
        ->assertSet('balanceFigure', '—');
});

test('another company balance uses the saved key and ignores a private link', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://sms.example.com/*' => Http::response(['balance' => '80'], 200),
    ]);

    saveOrderSms(extra: [
        'sms_driver' => 'custom',
        'sms_custom_url' => 'https://sms.example.com/send',
        'sms_custom_api_key' => 'other-secret',
        'sms_field_key' => 'api_key',
        'sms_field_phone' => 'number',
        'sms_field_message' => 'text',
        'sms_custom_balance_url' => 'https://sms.example.com/balance',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(SmsSettings::class)
        ->call('loadBalance')
        ->assertSet('balanceFigure', '80');

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://sms.example.com/balance')
        && $request['api_key'] === 'other-secret');

    saveOrderSms(extra: [
        'sms_driver' => 'custom',
        'sms_custom_url' => 'https://sms.example.com/send',
        'sms_custom_balance_url' => 'http://127.0.0.1/balance',
    ]);

    expect(OrderSmsSettings::customBalanceUrl())->toBeNull();

    Livewire::test(SmsSettings::class)
        ->call('refreshBalance')
        ->assertSet('balanceFigure', '—');

    Http::assertSentCount(1);
});

function smsLog(): ArrayObject
{
    $messages = new ArrayObject;

    Log::listen(function (MessageLogged $event) use ($messages): void {
        $messages->append($event->message);
    });

    return $messages;
}

/**
 * @return list<string>
 */
function smsLines(ArrayObject $messages): array
{
    return array_values(array_filter(
        $messages->getArrayCopy(),
        fn (string $message): bool => str_contains($message, 'SMS [LOG DRIVER]'),
    ));
}

/**
 * @param  array<string, array{customer: bool, admin: bool}>|null  $rules
 * @param  array<string, mixed>  $extra
 */
function saveOrderSms(?array $rules = null, array $extra = [], bool $enabled = true): void
{
    OrderSmsSettings::save(array_merge([
        'sms_enabled' => $enabled,
        'sms_driver' => 'log',
        'phones' => [],
        'rules' => $rules ?? OrderSmsSettings::defaultRules(),
        'registered_enabled' => true,
        'registered_message' => 'Welcome {name}. Your {shop} account is ready.',
    ], $extra));
}
