<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AdminAccess;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && AdminAccess::roleIsDeletable($role);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('roles.delete');
    }
}
