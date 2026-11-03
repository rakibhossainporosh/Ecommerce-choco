<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = Cache::remember('admin.dashboard.stats_overview', now()->addSeconds(60), function (): array {
            $today = Carbon::today();
            $weekStart = $today->copy()->subDays(6)->startOfDay();
            $monthStart = Carbon::now()->startOfMonth();

            $orderTotals = Order::query()
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status != 'cancelled' THEN grand_total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status != 'cancelled' AND DATE(created_at) = ? THEN grand_total ELSE 0 END) as today_revenue,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
                ", [$today->toDateString()])
                ->first();

            $ordersByDay = Order::query()
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total, SUM(CASE WHEN status != \'cancelled\' THEN grand_total ELSE 0 END) as revenue')
                ->where('created_at', '>=', $weekStart)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy(fn ($row): string => Carbon::parse($row->day)->toDateString());

            $customersByDay = Customer::query()
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->where('created_at', '>=', $weekStart)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy(fn ($row): string => Carbon::parse($row->day)->toDateString());

            $revenueTrend = [];
            $ordersTrend = [];
            $customersTrend = [];

            foreach (range(6, 0) as $daysAgo) {
                $day = $today->copy()->subDays($daysAgo)->toDateString();
                $revenueTrend[] = (float) ($ordersByDay[$day]->revenue ?? 0);
                $ordersTrend[] = (int) ($ordersByDay[$day]->total ?? 0);
                $customersTrend[] = (int) ($customersByDay[$day]->total ?? 0);
            }

            $stock = Inventory::query()
                ->selectRaw('
                    SUM(CASE WHEN quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
                ')
                ->first();

            return [
                'total_revenue' => (float) ($orderTotals->total_revenue ?? 0),
                'today_revenue' => (float) ($orderTotals->today_revenue ?? 0),
                'total_orders' => (int) ($orderTotals->total_orders ?? 0),
                'pending_orders' => (int) ($orderTotals->pending_orders ?? 0),
                'total_customers' => Customer::count(),
                'new_customers_month' => Customer::query()->where('created_at', '>=', $monthStart)->count(),
                'low_stock' => (int) ($stock->low_stock ?? 0),
                'out_of_stock' => (int) ($stock->out_of_stock ?? 0),
                'revenue_trend' => $revenueTrend,
                'orders_trend' => $ordersTrend,
                'customers_trend' => $customersTrend,
            ];
        });

        $pendingOrders = $stats['pending_orders'];
        $lowStockCount = $stats['low_stock'];
        $outOfStockCount = $stats['out_of_stock'];

        return [
            Stat::make(__('Total Revenue'), '৳ '.number_format($stats['total_revenue'], 2))
                ->description(__('Today: ৳ :amount', ['amount' => number_format($stats['today_revenue'], 2)]))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($stats['revenue_trend'])
                ->color('success'),

            Stat::make(__('Total Orders'), number_format($stats['total_orders']))
                ->description($pendingOrders > 0 ? __(':count pending review', ['count' => $pendingOrders]) : __('All orders processed'))
                ->descriptionIcon($pendingOrders > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->chart($stats['orders_trend'])
                ->color($pendingOrders > 0 ? 'warning' : 'primary'),

            Stat::make(__('Total Customers'), number_format($stats['total_customers']))
                ->description(__(':count new this month', ['count' => $stats['new_customers_month']]))
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($stats['customers_trend'])
                ->color('info'),

            Stat::make(__('Stock Alert'), __(':count items', ['count' => number_format($lowStockCount)]))
                ->description($outOfStockCount > 0 ? __(':count out of stock', ['count' => $outOfStockCount]) : ($lowStockCount > 0 ? __('Needs restock') : __('All items in stock')))
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->url('/admin/inventory/stock-overview'),
        ];
    }
}
