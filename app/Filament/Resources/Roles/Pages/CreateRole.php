<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Roles\Concerns\SyncsRolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateRole extends CreateRecord
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->pullRolePermissions($data);
    }

    protected function afterCreate(): void
    {
        $this->syncSelectedPermissions();
    }
}
