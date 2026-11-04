<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Attributes\AttributeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateAttribute extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = AttributeResource::class;

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
