<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = UnitResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
