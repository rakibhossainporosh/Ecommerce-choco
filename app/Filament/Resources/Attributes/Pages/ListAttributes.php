<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Filament\Resources\Attributes\AttributeResource;
use App\Models\Attribute;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Attributes')
                ->badge(Attribute::query()->count()),

            'variant' => Tab::make('Variant Scope')
                ->badge(Attribute::query()->where('scope', Attribute::SCOPE_VARIANT)->count())
                ->badgeColor('purple')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('scope', Attribute::SCOPE_VARIANT)),

            'product' => Tab::make('Product Scope')
                ->badge(Attribute::query()->where('scope', Attribute::SCOPE_PRODUCT)->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('scope', Attribute::SCOPE_PRODUCT)),

            'active' => Tab::make('Active')
                ->badge(Attribute::query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Inactive')
                ->badge(Attribute::query()->where('is_active', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
