<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AdminAccess;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.delete') && AdminAccess::userIsDeletable($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('users.delete');
    }
}
