<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = BrandResource::class;
}
