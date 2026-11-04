<?php

namespace App\Filament\Resources\CourierShipments\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCourierShipment extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierShipmentResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
