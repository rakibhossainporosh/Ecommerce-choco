<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Revenue
        $totalRevenue = (float) Order::where('status', '!=', 'cancelled')->sum('grand_total');
        $todayRevenue = (float) Order::where('status', '!=', 'cancelled')
            ->whereDate('created_at', Carbon::today())
            ->sum('grand_total');

        // Last 7 days revenue sparkline
        $revenueTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return (float) Order::where('status', '!=', 'cancelled')
                ->whereDate('created_at', $date)
                ->sum('grand_total');
        })->toArray();

        // 2. Orders & Pending Orders
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();

        // Last 7 days orders sparkline
        $ordersTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return Order::whereDate('created_at', $date)->count();
        })->toArray();

        // 3. Customers (from Customer model)
        $totalCustomers = Customer::count();
        $newCustomersThisMonth = Customer::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // Last 7 days customers sparkline
        $customersTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return Customer::whereDate('created_at', $date)->count();
        })->toArray();

        // 4. Low stock inventory
        $lowStockCount = Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')->count();
        $outOfStockCount = Inventory::where('quantity', '<=', 0)->count();

        return [
            Stat::make('Total Revenue', '৳ '.number_format($totalRevenue, 2))
                ->description('Today: ৳ '.number_format($todayRevenue, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($revenueTrend)
                ->color('success'),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description($pendingOrders > 0 ? "{$pendingOrders} pending review" : 'All orders processed')
                ->descriptionIcon($pendingOrders > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->chart($ordersTrend)
                ->color($pendingOrders > 0 ? 'warning' : 'primary'),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description("{$newCustomersThisMonth} new this month")
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($customersTrend)
                ->color('info'),

            Stat::make('Stock Alert', number_format($lowStockCount).' Items')
                ->description($outOfStockCount > 0 ? "{$outOfStockCount} out of stock" : ($lowStockCount > 0 ? 'Needs restock' : 'All items in stock'))
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->url('/admin/inventory/stock-overview'),
        ];
    }
}
