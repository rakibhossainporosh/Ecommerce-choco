<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCategory extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CategoryResource::class;

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
                ->before(function (DeleteAction $action, Category $record): void {
                    if ($record->hasChildren()) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot delete category because it has subcategories.'))
                            ->send();

                        $action->halt();
                    }

                    if ($record->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot delete category because it has active products assigned to it.'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
