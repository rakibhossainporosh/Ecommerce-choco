<?php

namespace App\Filament\Resources\ReturnRequests\Pages;

use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
