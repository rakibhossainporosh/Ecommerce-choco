<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = UnitResource::class;
}
