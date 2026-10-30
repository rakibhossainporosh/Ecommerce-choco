<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Categories')
                ->badge(Category::query()->count()),

            'active' => Tab::make('Active')
                ->badge(Category::query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Inactive')
                ->badge(Category::query()->where('is_active', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'root' => Tab::make('Root Categories')
                ->badge(Category::query()->whereNull('parent_id')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id')),

            'subcategories' => Tab::make('Subcategories')
                ->badge(Category::query()->whereNotNull('parent_id')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('parent_id')),
        ];
    }
}
