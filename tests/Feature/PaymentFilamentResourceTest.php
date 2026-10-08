<?php

use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Support\Icons\Heroicon;
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
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('1. PaymentResource binds to Payment model and registers proper navigation metadata', function () {
    expect(PaymentResource::getModel())->toBe(Payment::class)
        ->and(PaymentResource::getNavigationIcon())->toBe(Heroicon::OutlinedBanknotes)
        ->and(PaymentResource::getNavigationLabel())->toBe('Payments');
});

test('2. Admin can access PaymentResource and view pages', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);
    expect(PaymentResource::canAccess())->toBeTrue()
        ->and(PaymentResource::canCreate())->toBeTrue();

    $payment = Payment::factory()->create();

    $this->actingAs($admin)
        ->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertOk();
});

test('3. Manager can access PaymentResource and view pages', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);
    expect(PaymentResource::canAccess())->toBeTrue()
        ->and(PaymentResource::canCreate())->toBeTrue();

    $payment = Payment::factory()->create();

    $this->actingAs($manager)
        ->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertOk();
});

test('4. Staff can access PaymentResource and view pages with create ability', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);
    expect(PaymentResource::canAccess())->toBeTrue()
        ->and(PaymentResource::canCreate())->toBeTrue();

    $payment = Payment::factory()->create();

    $this->actingAs($staff)
        ->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertOk();
});

test('5. Unauthenticated guest is redirected to admin login', function () {
    $payment = Payment::factory()->create();

    $this->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertRedirect('/admin/login');
});

test('6. User without admin panel access is forbidden', function () {
    $user = User::factory()->create(['can_access_admin_panel' => false]);
    $user->assignRole('Admin');

    $payment = Payment::factory()->create();

    $this->actingAs($user)
        ->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertForbidden();
});

test('7. Payment ledger records prohibit deletion and inline form editing', function () {
    $payment = Payment::factory()->create();

    expect(PaymentResource::canDelete($payment))->toBeFalse()
        ->and(PaymentResource::canDeleteAny())->toBeFalse()
        ->and(PaymentResource::canEdit($payment))->toBeFalse();
});

test('8. Payment creation is configured as a modal on ListPayments page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    expect(PaymentResource::getPages())->not->toHaveKey('create');

    $listPage = new ListPayments;
    $actions = invade($listPage)->getHeaderActions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0]->getName())->toBe('create')
        ->and($actions[0]->getModalHeading())->toBe('Record Payment Transaction');
});

test('9. Admin can verify pending payment on ViewPayment page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $order = Order::factory()->create([
        'grand_total' => 1200.00,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $payment = Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => 1200.00,
    ]);

    Livewire::test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionVisible('mark_completed')
        ->callAction('mark_completed')
        ->assertHasNoActionErrors()
        ->assertNotified('Payment Verified');

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Completed)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('10. Admin can refund completed payment on ViewPayment page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $order = Order::factory()->create([
        'grand_total' => 600.00,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 600.00,
        'status' => PaymentTransactionStatus::Completed,
    ]);

    Livewire::test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionVisible('refund')
        ->callAction('refund', [
            'reason' => 'Customer cancelled item',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Payment Refunded');

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Refunded)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::Refunded);
});

test('11. PaymentsRelationManager is registered in OrderResource relations', function () {
    expect(OrderResource::getRelations())->toContain(PaymentsRelationManager::class);
});
