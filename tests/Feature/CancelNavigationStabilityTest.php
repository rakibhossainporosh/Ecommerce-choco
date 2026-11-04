<?php

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);
});

function assertCancelAvoidsHistoryBack(object $component): void
{
    $method = new ReflectionMethod($component->instance(), 'getCancelFormAction');
    /** @var Action $cancelAction */
    $cancelAction = $method->invoke($component->instance());

    expect($cancelAction->getName())->toBe('cancel');

    $handler = (string) $cancelAction->getAlpineClickHandler();

    expect($handler)
        ->toMatch('/Livewire\.navigate\(|window\.location\.href\s*=/')
        ->not->toContain('history.back()')
        ->not->toContain('document.referrer');
}

test('create and edit cancel actions never use history.back', function (): void {
    assertCancelAvoidsHistoryBack(Livewire::test(CreateProduct::class));
    assertCancelAvoidsHistoryBack(Livewire::test(CreateCategory::class));
    assertCancelAvoidsHistoryBack(Livewire::test(CreateBrand::class));

    $product = Product::factory()->create();
    assertCancelAvoidsHistoryBack(Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]));

    $brand = Brand::factory()->create();
    assertCancelAvoidsHistoryBack(Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()]));
});
