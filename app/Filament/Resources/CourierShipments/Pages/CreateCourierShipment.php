<?php

namespace App\Filament\Resources\CourierShipments\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourierShipment extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierShipmentResource::class;
}
