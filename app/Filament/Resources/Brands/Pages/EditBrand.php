<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Brand $record): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete this brand because it has associated products.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
