<?php

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Shipping Method')
                ->modalHeading('Create Shipping Method')
                ->modalDescription('Define delivery zones, shipping fees, and carrier partners.')
                ->modalIcon(Heroicon::OutlinedTruck)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
