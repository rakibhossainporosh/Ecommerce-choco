<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateUnit extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = UnitResource::class;

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
