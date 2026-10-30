<?php

namespace App\Filament\Resources\CourierShipments\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourierShipment extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
