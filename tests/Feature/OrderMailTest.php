<?php

use App\Enums\OrderStatus;
use App\Filament\Pages\EmailSettings;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderMailer;
use App\Support\OrderMailSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Mail::fake();
});

test('order emails stay off until the shop turns them on', function () {
    Order::factory()->create([
        'customer_email' => 'buyer@example.com',
    ]);

    Mail::assertNothingSent();
});

test('a placed order emails the customer and the store when both are on', function () {
    saveOrderMail();

    Order::factory()->create([
        'customer_name' => 'Karim',
        'customer_email' => 'buyer@example.com',
    ]);

    Mail::assertSent(OrderUpdateMail::class, 2);
    Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail): bool => $mail->hasTo('buyer@example.com') && $mail->step === 'placed');
    Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail): bool => $mail->hasTo('admin@noboshop.com') && $mail->audience === 'admin');
});

test('the customer can be switched off without stopping the store email', function () {
    $rules = OrderMailSettings::defaultRules();
    $rules['placed']['customer'] = false;
    saveOrderMail($rules);

    Order::factory()->create([
        'customer_email' => 'buyer@example.com',
    ]);

    Mail::assertSent(OrderUpdateMail::class, 1);
    Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail): bool => $mail->hasTo('admin@noboshop.com'));
});

test('a later order step uses that step switch', function () {
    $rules = OrderMailSettings::defaultRules();
    $rules['placed'] = ['customer' => false, 'admin' => false];
    $rules['shipped'] = ['customer' => true, 'admin' => false];
    saveOrderMail($rules);

    $order = Order::factory()->create([
        'customer_email' => 'buyer@example.com',
        'status' => OrderStatus::Processing,
    ]);

    $order->update(['status' => OrderStatus::Shipped]);

    Mail::assertSent(OrderUpdateMail::class, 1);
    Mail::assertSent(OrderUpdateMail::class, fn (OrderUpdateMail $mail): bool => $mail->step === 'shipped' && $mail->hasTo('buyer@example.com'));
});

test('gmail and webmail are saved separately and the password is not stored in plain text', function () {
    saveOrderMail();

    OrderMailSettings::save([
        'mail_enabled' => true,
        'mail_provider' => 'webmail',
        'mail_gmail_address' => 'shop@gmail.com',
        'mail_gmail_password' => 'secret-app-pass',
        'mail_webmail_address' => 'orders@noboshop.com',
        'mail_webmail_password' => 'mailbox-secret',
        'mail_webmail_host' => 'mail.noboshop.com',
        'mail_webmail_port' => 465,
        'mail_webmail_encryption' => 'ssl',
        'mail_admin_address' => 'admin@noboshop.com',
        'rules' => OrderMailSettings::defaultRules(),
    ]);

    $stored = Setting::query()->where('key', 'mail_gmail_password')->value('value');

    OrderMailSettings::save([
        'mail_enabled' => true,
        'mail_provider' => 'gmail',
        'mail_gmail_address' => 'shop@gmail.com',
        'mail_gmail_password' => 'ab cd ef gh',
        'mail_admin_address' => 'admin@noboshop.com',
        'rules' => OrderMailSettings::defaultRules(),
    ]);

    expect($stored)->not->toBe('secret-app-pass')
        ->and(OrderMailSettings::secret('mail_gmail_password'))->toBe('abcdefgh')
        ->and(OrderMailSettings::secret('mail_webmail_password'))->toBe('mailbox-secret')
        ->and(OrderMailSettings::mailerConfig()['host'])->toBe('smtp.gmail.com')
        ->and(OrderMailSettings::mailerConfig()['scheme'])->toBe('smtp')
        ->and(DB::table('activity_log')->where('properties', 'like', '%secret-app-pass%')->count())->toBe(0)
        ->and(DB::table('activity_log')->where('properties', 'like', '%mailbox-secret%')->count())->toBe(0);
});

test('the order email includes the product and the total', function () {
    saveOrderMail();

    $order = Order::factory()->create([
        'customer_name' => 'Karim',
        'customer_email' => 'buyer@example.com',
        'grand_total' => 250,
    ]);
    $variant = ProductVariant::factory()->create();
    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => 'Cotton shirt',
        'variant_name' => 'M',
        'sku' => 'SKU-1',
        'unit_price' => 250,
        'quantity' => 1,
        'line_total' => 250,
    ]);

    app(OrderMailer::class)->send($order->fresh(), 'placed');

    Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail): bool {
        return str_contains($mail->render(), 'Cotton shirt')
            && str_contains($mail->render(), '250.00');
    });
});

test('admin can save which mailbox sends order emails', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EmailSettings::class)
        ->assertSee('Customer emails')
        ->assertSee('Store emails')
        ->assertSee('mail-audience-grid')
        ->assertSee('fi-width-xs')
        ->assertDontSee('Email the customer')
        ->fillForm([
            'mail_enabled' => false,
            'mail_provider' => 'webmail',
            'mail_webmail_address' => 'orders@noboshop.com',
            'mail_webmail_password' => 'mailbox-secret',
            'mail_webmail_host' => 'mail.noboshop.com',
            'mail_webmail_port' => 587,
            'mail_webmail_encryption' => 'tls',
            'mail_admin_address' => 'admin@noboshop.com',
            'rules' => OrderMailSettings::defaultRules(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('mail_provider'))->toBe('webmail')
        ->and(OrderMailSettings::hasSecret('mail_webmail_password'))->toBeTrue()
        ->and(OrderMailSettings::enabled())->toBeFalse();
});

test('staff cannot open order email settings', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    Livewire::test(EmailSettings::class)->assertForbidden();
});

function saveOrderMail(?array $rules = null): void
{
    OrderMailSettings::save([
        'mail_enabled' => true,
        'mail_provider' => 'gmail',
        'mail_gmail_address' => 'shop@gmail.com',
        'mail_gmail_password' => 'secret-app-pass',
        'mail_admin_address' => 'admin@noboshop.com',
        'mail_webmail_port' => 587,
        'mail_webmail_encryption' => 'tls',
        'rules' => $rules ?? OrderMailSettings::defaultRules(),
    ]);
}
