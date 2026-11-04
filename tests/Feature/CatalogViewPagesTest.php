<?php

use App\Filament\Resources\Attributes\AttributeResource;
use App\Filament\Resources\Attributes\Pages\ListAttributes;
use App\Filament\Resources\Attributes\Pages\ViewAttribute;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Pages\ViewBrand;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\Couriers\Pages\ViewCourier;
use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use App\Filament\Resources\CourierShipments\Pages\ListCourierShipments;
use App\Filament\Resources\CourierShipments\Pages\ViewCourierShipment;
use App\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Resources\ShippingMethods\Pages\ViewShippingMethod;
use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Pages\ViewUnit;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\ShippingMethod;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create(['can_access_admin_panel' => true]);
    $this->admin->assignRole('Admin');
    $this->actingAs($this->admin);
});

test('catalog and shipping resources register view pages', function () {
    expect(BrandResource::getPages())->toHaveKey('view')
        ->and(CategoryResource::getPages())->toHaveKey('view')
        ->and(AttributeResource::getPages())->toHaveKey('view')
        ->and(UnitResource::getPages())->toHaveKey('view')
        ->and(ShippingMethodResource::getPages())->toHaveKey('view')
        ->and(CourierResource::getPages())->toHaveKey('view')
        ->and(CourierShipmentResource::getPages())->toHaveKey('view');
});

test('list tables expose view actions for previously edit-only resources', function (string $listPage, string $factory) {
    $record = $factory::factory()->create();

    Livewire::test($listPage)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$record])
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit');
})->with([
    'brands' => [ListBrands::class, Brand::class],
    'categories' => [ListCategories::class, Category::class],
    'attributes' => [ListAttributes::class, Attribute::class],
    'units' => [ListUnits::class, Unit::class],
    'shipping methods' => [ListShippingMethods::class, ShippingMethod::class],
    'couriers' => [ListCouriers::class, Courier::class],
]);

test('view pages render successfully for previously edit-only resources', function (string $viewPage, string $factory, string $resource) {
    $record = $factory::factory()->create();

    Livewire::test($viewPage, ['record' => $record->getRouteKey()])
        ->assertSuccessful()
        ->assertActionExists('edit');

    $this->get($resource::getUrl('view', ['record' => $record]))
        ->assertOk();
})->with([
    'brands' => [ViewBrand::class, Brand::class, BrandResource::class],
    'categories' => [ViewCategory::class, Category::class, CategoryResource::class],
    'attributes' => [ViewAttribute::class, Attribute::class, AttributeResource::class],
    'units' => [ViewUnit::class, Unit::class, UnitResource::class],
    'shipping methods' => [ViewShippingMethod::class, ShippingMethod::class, ShippingMethodResource::class],
    'couriers' => [ViewCourier::class, Courier::class, CourierResource::class],
]);

test('courier shipment view page and list view action work', function () {
    $shipment = CourierShipment::factory()->create();

    Livewire::test(ListCourierShipments::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$shipment])
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit');

    Livewire::test(ViewCourierShipment::class, ['record' => $shipment->getRouteKey()])
        ->assertSuccessful()
        ->assertActionExists('edit');
});
