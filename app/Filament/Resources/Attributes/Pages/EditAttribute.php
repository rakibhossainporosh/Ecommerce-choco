<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Attributes\AttributeResource;
use App\Models\Attribute;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditAttribute extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action, Attribute $record): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot delete this attribute because it has associated attribute values.'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
