<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Policies\CustomerPolicy;
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
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();

    $this->policy = new CustomerPolicy;
    $this->customer = Customer::factory()->create();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Admin can authorize all customer abilities via Gate::before', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $this->customer))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->update($admin, $this->customer))->toBeTrue()
        ->and($this->policy->delete($admin, $this->customer))->toBeTrue()
        ->and($this->policy->deleteAny($admin))->toBeTrue();
});

test('2. Admin cannot delete customer if customer has placed orders', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Order::factory()->create(['customer_id' => $this->customer->id]);

    expect($this->policy->delete($admin, $this->customer))->toBeFalse();
});

test('3. Manager can view, create, and update customers, but cannot delete', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->policy->viewAny($manager))->toBeTrue()
        ->and($this->policy->view($manager, $this->customer))->toBeTrue()
        ->and($this->policy->create($manager))->toBeTrue()
        ->and($this->policy->update($manager, $this->customer))->toBeTrue()
        ->and($this->policy->delete($manager, $this->customer))->toBeFalse()
        ->and($this->policy->deleteAny($manager))->toBeFalse();
});

test('4. Staff can view, create, and update customers, but cannot delete', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($this->policy->viewAny($staff))->toBeTrue()
        ->and($this->policy->view($staff, $this->customer))->toBeTrue()
        ->and($this->policy->create($staff))->toBeTrue()
        ->and($this->policy->update($staff, $this->customer))->toBeTrue()
        ->and($this->policy->delete($staff, $this->customer))->toBeFalse()
        ->and($this->policy->deleteAny($staff))->toBeFalse();
});

test('5. Unprivileged user without roles is denied all customer operations', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $this->customer))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $this->customer))->toBeFalse()
        ->and($this->policy->delete($user, $this->customer))->toBeFalse()
        ->and($this->policy->deleteAny($user))->toBeFalse();
});
