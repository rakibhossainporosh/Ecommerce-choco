<?php

use App\Enums\ShipmentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Policies\ShipmentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    Shipment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->policy = new ShipmentPolicy;
    $this->order = Order::factory()->create();
    $this->shipment = Shipment::factory()->create(['order_id' => $this->order->id, 'status' => ShipmentStatus::Pending]);
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Shipment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('1. Admin can perform all shipment operations but cannot delete in-transit/delivered or bulk delete', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $this->shipment))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->update($admin, $this->shipment))->toBeTrue()
        ->and($this->policy->ship($admin))->toBeTrue()
        ->and($this->policy->deliver($admin))->toBeTrue()
        ->and($this->policy->delete($admin, $this->shipment))->toBeTrue()
        ->and($this->policy->deleteAny($admin))->toBeFalse();

    $inTransit = Shipment::factory()->create(['order_id' => $this->order->id, 'status' => ShipmentStatus::InTransit]);
    expect($this->policy->delete($admin, $inTransit))->toBeFalse();
});

test('2. Manager can perform all shipment operations but cannot delete in-transit/delivered or bulk delete', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->policy->viewAny($manager))->toBeTrue()
        ->and($this->policy->view($manager, $this->shipment))->toBeTrue()
        ->and($this->policy->create($manager))->toBeTrue()
        ->and($this->policy->update($manager, $this->shipment))->toBeTrue()
        ->and($this->policy->ship($manager))->toBeTrue()
        ->and($this->policy->deliver($manager))->toBeTrue()
        ->and($this->policy->delete($manager, $this->shipment))->toBeTrue()
        ->and($this->policy->deleteAny($manager))->toBeFalse();
});

test('3. Staff can view, create and update, but cannot ship or deliver', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($this->policy->viewAny($staff))->toBeTrue()
        ->and($this->policy->view($staff, $this->shipment))->toBeTrue()
        ->and($this->policy->create($staff))->toBeTrue()
        ->and($this->policy->update($staff, $this->shipment))->toBeTrue()
        ->and($this->policy->ship($staff))->toBeFalse()
        ->and($this->policy->deliver($staff))->toBeFalse()
        ->and($this->policy->delete($staff, $this->shipment))->toBeTrue()
        ->and($this->policy->deleteAny($staff))->toBeFalse();
});

test('4. Unprivileged user is denied all shipping operations', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $this->shipment))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $this->shipment))->toBeFalse()
        ->and($this->policy->ship($user))->toBeFalse()
        ->and($this->policy->deliver($user))->toBeFalse()
        ->and($this->policy->delete($user, $this->shipment))->toBeFalse()
        ->and($this->policy->deleteAny($user))->toBeFalse();
});
