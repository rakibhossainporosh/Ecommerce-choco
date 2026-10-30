<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Couriers\CourierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourier extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
