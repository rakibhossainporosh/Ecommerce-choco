<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Record Payment')
                ->modalHeading('Record Payment Transaction')
                ->modalDescription('Enter transaction details to record and reconcile payments against an order.')
                ->modalIcon(Heroicon::OutlinedBanknotes)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
