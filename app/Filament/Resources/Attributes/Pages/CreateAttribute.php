<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Attributes\AttributeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = AttributeResource::class;
}
