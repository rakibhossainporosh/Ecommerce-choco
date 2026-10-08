<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', '৳ '.number_format(Order::where('status', '!=', 'cancelled')->sum('grand_total'), 2))
                ->description('Total revenue from non-cancelled orders')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Orders', Order::count())
                ->description('All time orders placed')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Total Customers', User::whereHas('roles', function ($q) {
                $q->where('name', 'Customer'); // Assuming a customer role exists or just simply all users
            })->count() ?: User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
