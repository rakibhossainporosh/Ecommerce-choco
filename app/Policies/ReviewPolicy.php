<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can('reviews.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.create');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.update');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->can('reviews.delete');
    }

    public function restore(User $user, Review $review): bool
    {
        return $user->can('reviews.delete');
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->can('reviews.delete');
    }
}
