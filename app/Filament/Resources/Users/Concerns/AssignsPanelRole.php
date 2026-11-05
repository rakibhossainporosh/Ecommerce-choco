<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Spatie\Permission\PermissionRegistrar;

trait AssignsPanelRole
{
    protected ?string $assignedRole = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function pullAssignedRole(array $data): array
    {
        $this->assignedRole = isset($data['role']) ? (string) $data['role'] : null;
        unset($data['role'], $data['password_confirmation']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function guardLastAdmin(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();
        $keepsAccess = (bool) ($data['can_access_admin_panel'] ?? false);
        $isLastAdmin = $record->hasRole('Admin') && User::role('Admin')->count() <= 1;

        if ($isLastAdmin && $this->assignedRole !== 'Admin') {
            Notification::make()
                ->danger()
                ->title(__('Keep at least one Admin.'))
                ->send();

            throw new Halt;
        }

        if ($isLastAdmin && ! $keepsAccess) {
            Notification::make()
                ->danger()
                ->title(__('The last Admin must keep panel access.'))
                ->send();

            throw new Halt;
        }

        if ($record->is(auth()->user()) && ! $keepsAccess) {
            Notification::make()
                ->danger()
                ->title(__('You cannot turn off your own access.'))
                ->send();

            throw new Halt;
        }

        return $data;
    }

    protected function syncAssignedRole(): void
    {
        if (filled($this->assignedRole)) {
            $this->getRecord()->syncRoles([$this->assignedRole]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
