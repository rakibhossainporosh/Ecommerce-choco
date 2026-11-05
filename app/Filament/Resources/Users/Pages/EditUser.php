<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Users\Concerns\AssignsPanelRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccess;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditUser extends EditRecord
{
    use AssignsPanelRole;
    use HasDescriptiveNotifications;

    protected static string $resource = UserResource::class;

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
                ->visible(fn (User $record): bool => AdminAccess::userIsDeletable($record))
                ->before(function (DeleteAction $action, User $record): void {
                    if (! AdminAccess::userIsDeletable($record)) {
                        Notification::make()
                            ->danger()
                            ->title(__('You cannot remove yourself or the last Admin.'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->roles->first()?->name;
        unset($data['password']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->pullAssignedRole($data);

        return $this->guardLastAdmin($data);
    }

    protected function afterSave(): void
    {
        $this->syncAssignedRole();
    }
}
