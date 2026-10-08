<?php

namespace App\Policies;

use App\Models\CouponUsage;
use App\Models\User;

class CouponUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupon-usages.view');
    }

    public function view(User $user, CouponUsage $couponUsage): bool
    {
        return $user->can('coupon-usages.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CouponUsage $couponUsage): bool
    {
        return false;
    }

    public function delete(User $user, CouponUsage $couponUsage): bool
    {
        return false;
    }

    public function restore(User $user, CouponUsage $couponUsage): bool
    {
        return false;
    }

    public function forceDelete(User $user, CouponUsage $couponUsage): bool
    {
        return false;
    }
}
