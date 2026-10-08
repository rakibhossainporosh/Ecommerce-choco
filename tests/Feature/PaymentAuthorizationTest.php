<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);

    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->policy = new PaymentPolicy;
    $this->order = Order::factory()->create();
    $this->payment = Payment::factory()->create(['order_id' => $this->order->id]);
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
});

test('1. Admin can view, create, update, and refund payments, but cannot delete records', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $this->payment))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->update($admin, $this->payment))->toBeTrue()
        ->and($this->policy->refund($admin, $this->payment))->toBeTrue()
        ->and($this->policy->delete($admin, $this->payment))->toBeFalse()
        ->and($this->policy->deleteAny($admin))->toBeFalse();
});

test('2. Manager can view, create, update, and refund completed payments, but cannot delete', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->policy->viewAny($manager))->toBeTrue()
        ->and($this->policy->view($manager, $this->payment))->toBeTrue()
        ->and($this->policy->create($manager))->toBeTrue()
        ->and($this->policy->update($manager, $this->payment))->toBeTrue()
        ->and($this->policy->refund($manager, $this->payment))->toBeTrue()
        ->and($this->policy->delete($manager, $this->payment))->toBeFalse()
        ->and($this->policy->deleteAny($manager))->toBeFalse();
});

test('3. Staff can view and record payments, but cannot refund or delete', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($this->policy->viewAny($staff))->toBeTrue()
        ->and($this->policy->view($staff, $this->payment))->toBeTrue()
        ->and($this->policy->create($staff))->toBeTrue()
        ->and($this->policy->refund($staff, $this->payment))->toBeFalse()
        ->and($this->policy->delete($staff, $this->payment))->toBeFalse()
        ->and($this->policy->deleteAny($staff))->toBeFalse();
});

test('4. Unprivileged user is denied all payment operations', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $this->payment))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $this->payment))->toBeFalse()
        ->and($this->policy->refund($user, $this->payment))->toBeFalse()
        ->and($this->policy->delete($user, $this->payment))->toBeFalse()
        ->and($this->policy->deleteAny($user))->toBeFalse();
});

test('5. refund is denied when payment is pending or failed even with refund permission', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $pendingPayment = Payment::factory()->pending()->create(['order_id' => $this->order->id]);
    $failedPayment = Payment::factory()->failed()->create(['order_id' => $this->order->id]);

    expect($this->policy->refund($manager, $pendingPayment))->toBeFalse()
        ->and($this->policy->refund($manager, $failedPayment))->toBeFalse();
});
