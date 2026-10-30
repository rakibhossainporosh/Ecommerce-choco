<?php

namespace Database\Seeders;

use App\Enums\ShippingProvider;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Inside Dhaka (Standard)',
                'code' => 'inside_dhaka_std',
                'provider' => ShippingProvider::Steadfast,
                'charge' => 70.00,
                'free_shipping_threshold' => 2000.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
                'description' => 'Delivery within Dhaka metro areas via Steadfast Courier.',
            ],
            [
                'name' => 'Outside Dhaka (All Bangladesh)',
                'code' => 'outside_dhaka_std',
                'provider' => ShippingProvider::Steadfast,
                'charge' => 130.00,
                'free_shipping_threshold' => 3000.00,
                'estimated_days_min' => 2,
                'estimated_days_max' => 4,
                'is_active' => true,
                'description' => 'Nationwide delivery outside Dhaka via Steadfast Courier.',
            ],
            [
                'name' => 'Inside Dhaka (Express / Same Day)',
                'code' => 'inside_dhaka_express',
                'provider' => ShippingProvider::Pathao,
                'charge' => 120.00,
                'free_shipping_threshold' => null,
                'estimated_days_min' => 1,
                'estimated_days_max' => 1,
                'is_active' => true,
                'description' => 'Fast delivery within Dhaka city via Pathao Courier.',
            ],
        ];

        foreach ($methods as $data) {
            ShippingMethod::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
