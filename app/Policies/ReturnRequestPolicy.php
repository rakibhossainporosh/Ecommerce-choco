<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;

class ReturnRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.view');
    }

    public function create(User $user): bool
    {
        return $user->can('returns.create');
    }

    public function update(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.update');
    }

    public function delete(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.delete');
    }

    public function restore(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.delete');
    }

    public function forceDelete(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.delete');
    }
}
