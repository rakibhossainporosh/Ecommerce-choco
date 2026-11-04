<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditReview extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = ReviewResource::class;

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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
