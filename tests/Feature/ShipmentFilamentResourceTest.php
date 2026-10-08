<?php

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Support\Icons\Heroicon;
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
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Shipment::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('1. ShipmentResource binds to Shipment model and registers proper navigation metadata', function () {
    expect(ShipmentResource::getModel())->toBe(Shipment::class)
        ->and(ShipmentResource::getNavigationIcon())->toBe(Heroicon::OutlinedPaperAirplane)
        ->and(ShipmentResource::getNavigationLabel())->toBe('Shipments');
});

test('2. Admin can access ShipmentResource and view pages', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);
    expect(ShipmentResource::canAccess())->toBeTrue();

    $shipment = Shipment::factory()->create();

    $this->actingAs($admin)
        ->get(ShipmentResource::getUrl('view', ['record' => $shipment]))
        ->assertOk();
});

test('3. Staff can access ShipmentResource', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);
    expect(ShipmentResource::canAccess())->toBeTrue();
});

test('4. Unauthenticated guest is redirected to admin login', function () {
    $shipment = Shipment::factory()->create();

    $this->get(ShipmentResource::getUrl('view', ['record' => $shipment]))
        ->assertRedirect(route('filament.admin.auth.login'));
});

test('5. Shipment editing is disabled in resource', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    
    $shipment = Shipment::factory()->create();
    
    $this->actingAs($admin);
    expect(ShipmentResource::canEdit($shipment))->toBeFalse();
});

test('6. Shipments can only be deleted if pending or cancelled', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);
    
    $pendingShipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
    $cancelledShipment = Shipment::factory()->create(['status' => ShipmentStatus::Cancelled]);
    $inTransitShipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);
    
    expect(ShipmentResource::canDelete($pendingShipment))->toBeTrue()
        ->and(ShipmentResource::canDelete($cancelledShipment))->toBeTrue()
        ->and(ShipmentResource::canDelete($inTransitShipment))->toBeFalse();
        
    expect(ShipmentResource::canDeleteAny())->toBeFalse();
});
