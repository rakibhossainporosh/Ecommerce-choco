<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivity extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = ActivityResource::class;
}
