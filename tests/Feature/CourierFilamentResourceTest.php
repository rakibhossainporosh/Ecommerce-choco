<?php

use App\Filament\Resources\Couriers\Pages as CourierPages;
use App\Filament\Resources\CourierShipments\Pages as ShipmentPages;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('courier resource lists couriers', function () {
    Courier::factory()->count(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(CourierPages\ListCouriers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Courier::all());
});

test('can create courier securely', function () {
    $this->actingAs($this->admin);

    Livewire::test(CourierPages\CreateCourier::class)
        ->fillForm([
            'name' => 'FastPath',
            'code' => 'fastpath',
            'api_base_url' => 'https://api.fastpath.com',
            'is_active' => true,
            'credentials' => [
                'api_key' => 'super-secret',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $courier = Courier::where('code', 'fastpath')->first();
    expect($courier)->not->toBeNull()
        ->and($courier->credentials['api_key'])->toBe('super-secret');
});

test('existing credentials are obfuscated on edit page', function () {
    $courier = Courier::factory()->create([
        'credentials' => ['api_key' => 'super-secret'],
    ]);

    $this->actingAs($this->admin);

    Livewire::test(CourierPages\EditCourier::class, ['record' => $courier->id])
        ->assertSuccessful()
        ->assertFormSet(['credentials' => []]); // formatStateUsing returns []
});

test('courier shipment resource lists shipments', function () {
    CourierShipment::factory()->count(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(ShipmentPages\ListCourierShipments::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(CourierShipment::all());
});
