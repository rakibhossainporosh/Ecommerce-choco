<?php

namespace App\Support;

use App\Models\Setting;

class ReturnPolicySettings
{
    /**
     * Number of days after delivery that a customer may request a return.
     */
    public static function windowDays(): int
    {
        $days = (int) Setting::get('return_window_days', 3);

        return max(1, $days);
    }
}
