<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Category $record): void {
                    if ($record->hasChildren()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete category because it has subcategories.')
                            ->send();

                        $action->halt();
                    }

                    if ($record->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete category because it has active products assigned to it.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
