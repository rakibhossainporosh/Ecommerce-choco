<?php

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewShippingMethod extends ViewRecord
{
    protected static string $resource = ShippingMethodResource::class;

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

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->modalHeading(__('Edit Shipping Method'))
                ->modalDescription(__('Update delivery zones, shipping fees, and carrier partners.'))
                ->modalIcon(Heroicon::OutlinedTruck)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
