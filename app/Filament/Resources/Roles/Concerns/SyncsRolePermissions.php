<?php

namespace App\Filament\Resources\Roles\Concerns;

use App\Support\AdminAccess;
use Spatie\Permission\PermissionRegistrar;

trait SyncsRolePermissions
{
    /**
     * @var list<string>
     */
    protected array $selectedPermissions = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function pullRolePermissions(array $data): array
    {
        $this->selectedPermissions = AdminAccess::collect($data['permission_groups'] ?? []);
        unset($data['permission_groups']);
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function syncSelectedPermissions(): void
    {
        if ($this->getRecord()->name === 'Admin') {
            $this->getRecord()->syncPermissions([]);
        } else {
            $this->getRecord()->syncPermissions($this->selectedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
