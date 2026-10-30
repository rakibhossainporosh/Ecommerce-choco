<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as BaseMakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeFilamentUserCommand extends BaseMakeUserCommand
{
    public function handle(): int
    {
        $this->configurePanel(question: 'Which panel would you like to create this user in?');

        $this->options = $this->options();

        if (! $this->panel) {
            $this->error('Filament has not been installed yet: php artisan filament:install --panels');

            return static::FAILURE;
        }

        if ($this->panel->getId() === 'admin' && ! Role::where('name', 'Admin')->where('guard_name', 'web')->exists()) {
            $this->error('The "Admin" role does not exist. Please run: php artisan db:seed --class=RolePermissionSeeder');

            return static::FAILURE;
        }

        $user = $this->createUser();
        $this->sendSuccessMessage($user);

        return static::SUCCESS;
    }

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

        if ($this->panel?->getId() === 'admin') {
            if (! $user->getAttribute('can_access_admin_panel')) {
                $user->forceFill(['can_access_admin_panel' => true])->save();
            }

            $adminRole = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

            if (! $adminRole) {
                throw new RuntimeException('The "Admin" role does not exist. Please run: php artisan db:seed --class=RolePermissionSeeder');
            }

            if (! $user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
            }
        }

        return $user;
    }
}
