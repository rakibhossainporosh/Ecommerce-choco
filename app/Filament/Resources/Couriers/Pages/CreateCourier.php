<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Couriers\CourierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourier extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierResource::class;
}
