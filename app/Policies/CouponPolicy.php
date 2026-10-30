<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.update');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        if ($coupon->usages()->exists()) {
            return false;
        }

        return $user->can('coupons.delete');
    }

    public function restore(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.delete');
    }

    public function forceDelete(User $user, Coupon $coupon): bool
    {
        if ($coupon->usages()->exists()) {
            return false;
        }

        return $user->can('coupons.delete');
    }
}
