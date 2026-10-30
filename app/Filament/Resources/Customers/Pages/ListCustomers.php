<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Customer')
                ->modalHeading('Create Customer')
                ->modalDescription('Enter the customer details to register their profile.')
                ->modalIcon(Heroicon::OutlinedUserPlus)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
