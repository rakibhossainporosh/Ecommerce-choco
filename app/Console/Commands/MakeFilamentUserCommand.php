<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as BaseMakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeFilamentUserCommand extends BaseMakeUserCommand
{
    /**
     * @return array<string, mixed>
     */
    protected function getUserData(): array
    {
        $data = parent::getUserData();

        if ($this->panel?->getId() === 'admin') {
            $data['can_access_admin_panel'] = true;
        }

        return $data;
    }

    protected function createUser(): Model&Authenticatable
    {
        $user = parent::createUser();

        if ($this->panel?->getId() === 'admin' && ! $user->getAttribute('can_access_admin_panel')) {
            $user->forceFill(['can_access_admin_panel' => true])->save();
        }

        return $user;
    }
}
