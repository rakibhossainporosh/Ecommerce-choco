<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Consignment')
                ->modalHeading('Create Shipment Consignment')
                ->modalDescription('Fulfill an order by assigning a courier, tracking code, and delivery destination.')
                ->modalIcon(Heroicon::OutlinedTruck)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
