<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Roles\Concerns\SyncsRolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Support\AdminAccess;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    use HasDescriptiveNotifications;
    use SyncsRolePermissions;

    protected static string $resource = RoleResource::class;

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
                ->visible(fn (Role $record): bool => AdminAccess::roleIsDeletable($record))
                ->before(function (DeleteAction $action, Role $record): void {
                    if (! AdminAccess::roleIsDeletable($record)) {
                        Notification::make()
                            ->danger()
                            ->title(__('Built-in roles, and roles that still have users, stay in place.'))
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
        $grouped = [];

        foreach ($this->getRecord()->permissions->pluck('name') as $name) {
            $prefix = strstr($name, '.', true) ?: $name;
            $grouped[AdminAccess::stateKey($prefix)][] = $name;
        }

        $data['permission_groups'] = $grouped;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->pullRolePermissions($data);
    }

    protected function afterSave(): void
    {
        $this->syncSelectedPermissions();
    }
}
