<?php

use App\Models\CourierShipment;
use App\Models\User;
use App\Policies\CourierPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

test('admin can access courier endpoints', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($admin->can('couriers.view'))->toBeTrue()
        ->and($admin->can('couriers.delete'))->toBeTrue();
});

test('manager can view and create but not delete couriers', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($manager->can('couriers.view'))->toBeTrue()
        ->and($manager->can('couriers.create'))->toBeTrue()
        ->and($manager->can('couriers.delete'))->toBeFalse();
});

test('staff can view but not create couriers', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($staff->can('couriers.view'))->toBeTrue()
        ->and($staff->can('couriers.create'))->toBeFalse()
        ->and($staff->can('courier-shipments.view'))->toBeTrue()
        ->and($staff->can('courier-shipments.create'))->toBeFalse();
});

test('courier policy prevents deletion if shipments exist', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('couriers.delete');

    $shipment = CourierShipment::factory()->create();
    $courier = $shipment->courier;

    expect((new CourierPolicy)->delete($user, $courier))->toBeFalse();

    $shipment->delete();

    expect((new CourierPolicy)->delete($user, $courier))->toBeTrue();
});
