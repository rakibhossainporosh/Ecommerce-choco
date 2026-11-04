<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditBrand extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Brand $record): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot delete this brand because it has associated products.'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
