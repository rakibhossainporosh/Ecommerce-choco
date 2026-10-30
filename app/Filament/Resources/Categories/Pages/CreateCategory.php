<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CategoryResource::class;
}
