<?php

namespace App\Support;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\CourierShipments\CourierShipmentResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\User;

class AdminDashboard
{
    /**
     * @return list<array{label: string, description: string, url: string, icon: string}>
     */
    public static function shortcuts(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $items = [
            [
                'permission' => 'orders.view',
                'resource' => OrderResource::class,
                'label' => __('Orders'),
                'description' => __('Review and fulfill customer orders'),
                'icon' => 'heroicon-o-shopping-bag',
            ],
            [
                'permission' => 'products.view',
                'resource' => ProductResource::class,
                'label' => __('Products'),
                'description' => __('Manage catalog products and variants'),
                'icon' => 'heroicon-o-cube',
            ],
            [
                'permission' => 'categories.view',
                'resource' => CategoryResource::class,
                'label' => __('Categories'),
                'description' => __('Organize your product categories'),
                'icon' => 'heroicon-o-tag',
            ],
            [
                'permission' => 'brands.view',
                'resource' => BrandResource::class,
                'label' => __('Brands'),
                'description' => __('Manage brand listings'),
                'icon' => 'heroicon-o-building-storefront',
            ],
            [
                'permission' => 'customers.view',
                'resource' => CustomerResource::class,
                'label' => __('Customers'),
                'description' => __('View customer profiles and activity'),
                'icon' => 'heroicon-o-user-group',
            ],
            [
                'permission' => 'courier-shipments.view',
                'resource' => CourierShipmentResource::class,
                'label' => __('Courier Shipments'),
                'description' => __('Track courier deliveries'),
                'icon' => 'heroicon-o-paper-airplane',
            ],
            [
                'permission' => 'payments.view',
                'resource' => PaymentResource::class,
                'label' => __('Payments'),
                'description' => __('Monitor payment activity'),
                'icon' => 'heroicon-o-banknotes',
            ],
            [
                'permission' => 'reviews.view',
                'resource' => ReviewResource::class,
                'label' => __('Reviews'),
                'description' => __('Moderate product reviews'),
                'icon' => 'heroicon-o-star',
            ],
            [
                'permission' => 'returns.view',
                'resource' => ReturnRequestResource::class,
                'label' => __('Return Requests'),
                'description' => __('Handle return requests'),
                'icon' => 'heroicon-o-arrow-uturn-left',
            ],
            [
                'permission' => 'coupons.view',
                'resource' => CouponResource::class,
                'label' => __('Coupons'),
                'description' => __('Manage discount coupons'),
                'icon' => 'heroicon-o-ticket',
            ],
            [
                'permission' => 'activities.view',
                'resource' => ActivityResource::class,
                'label' => __('Activities'),
                'description' => __('Review recent admin activity'),
                'icon' => 'heroicon-o-clipboard-document-list',
            ],
        ];

        $shortcuts = [];

        foreach ($items as $item) {
            if (! $user->can($item['permission'])) {
                continue;
            }

            /** @var class-string $resource */
            $resource = $item['resource'];

            if (! $resource::canViewAny()) {
                continue;
            }

            $shortcuts[] = [
                'label' => $item['label'],
                'description' => $item['description'],
                'url' => $resource::getUrl('index'),
                'icon' => $item['icon'],
            ];
        }

        if ($user->can('inventory.view')) {
            $shortcuts[] = [
                'label' => __('Inventory / Stock'),
                'description' => __('Check stock levels and alerts'),
                'url' => '/admin/inventory/stock-overview',
                'icon' => 'heroicon-o-archive-box',
            ];
        }

        return $shortcuts;
    }

    public static function canSeeOperationalStats(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        foreach ([
            'orders.view',
            'reports.view',
            'customers.view',
            'inventory.view',
            'products.view',
            'categories.view',
            'brands.view',
            'courier-shipments.view',
            'activities.view',
            'reviews.view',
            'returns.view',
            'coupons.view',
            'payments.view',
        ] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
