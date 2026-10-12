<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Brands')
                ->badge(Brand::query()->count()),

            'active' => Tab::make('Active')
                ->badge(Brand::query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Inactive')
                ->badge(Brand::query()->where('is_active', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'with_products' => Tab::make('With Products')
                ->badge(Brand::query()->has('products')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('products')),

            'without_products' => Tab::make('Without Products')
                ->badge(Brand::query()->doesntHave('products')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('products')),
        ];
    }
}
