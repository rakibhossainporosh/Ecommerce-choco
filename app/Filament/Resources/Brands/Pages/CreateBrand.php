<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateBrand extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = BrandResource::class;

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
}
