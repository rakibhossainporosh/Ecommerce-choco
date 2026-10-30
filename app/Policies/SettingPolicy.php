<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.create');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('settings.update');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->can('settings.delete');
    }

    public function restore(User $user, Setting $setting): bool
    {
        return $user->can('settings.delete');
    }

    public function forceDelete(User $user, Setting $setting): bool
    {
        return $user->can('settings.delete');
    }
}
