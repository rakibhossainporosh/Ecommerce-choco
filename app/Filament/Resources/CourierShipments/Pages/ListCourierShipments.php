<?php

namespace App\Filament\Resources\CourierShipments\Pages;

use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourierShipments extends ListRecords
{
    protected static string $resource = CourierShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
