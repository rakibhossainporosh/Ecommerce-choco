<?php

namespace App\Filament\Resources\ReturnRequests\Pages;

use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnRequest extends EditRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
