<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Users\Concerns\AssignsPanelRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateUser extends CreateRecord
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->pullAssignedRole($data);
    }

    protected function afterCreate(): void
    {
        $this->syncAssignedRole();
    }
}
