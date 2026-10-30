<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Products')
                ->badge(Product::query()->count()),

            'active' => Tab::make('Active')
                ->badge(Product::query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Draft / Inactive')
                ->badge(Product::query()->where('is_active', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'featured' => Tab::make('Featured')
                ->badge(Product::query()->where('is_featured', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),

            'out_of_stock' => Tab::make('Out of Stock')
                ->badge(
                    Product::query()->whereDoesntHave('variants.inventory', fn ($q) => $q->where('quantity', '>', 0))->count()
                )
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->whereDoesntHave('variants.inventory', fn ($q) => $q->where('quantity', '>', 0))
                ),
        ];
    }
}
