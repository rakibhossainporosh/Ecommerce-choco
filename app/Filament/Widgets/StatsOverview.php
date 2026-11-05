<?php

namespace App\Filament\Widgets;

use App\Enums\CourierShipmentStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CourierShipment;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Support\AdminDashboard;
use App\Support\UiText;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return AdminDashboard::canSeeOperationalStats();
    }

    /**
     * Filament re-runs __() on Stat labels by default. On Windows that can load a whole
     * language file (e.g. Payments -> payments.php) and return an array.
     */
    protected function metric(string $label, mixed $value): Stat
    {
        return Stat::make(UiText::line($label), $value)->translateLabel(false);
    }

    protected function getStats(): array
    {
        $stats = Cache::remember('admin.dashboard.stats_overview.v2', now()->addSeconds(60), function (): array {
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

            $courierTotals = CourierShipment::query()
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending
                ', [CourierShipmentStatus::Pending->value])
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
                'products_total' => Product::query()->count(),
                'products_active' => Product::query()->where('is_active', true)->count(),
                'categories_total' => Category::query()->count(),
                'brands_total' => Brand::query()->count(),
                'courier_shipments_total' => (int) ($courierTotals->total ?? 0),
                'courier_shipments_pending' => (int) ($courierTotals->pending ?? 0),
                'activities_today' => Activity::query()->whereDate('created_at', $today)->count(),
                'reviews_pending' => Review::query()->where('is_approved', false)->count(),
                'returns_open' => ReturnRequest::query()->whereNotIn('status', [
                    ReturnRequestStatus::Completed->value,
                    ReturnRequestStatus::Rejected->value,
                    ReturnRequestStatus::Cancelled->value,
                ])->count(),
                'coupons_active' => Coupon::query()->where('is_active', true)->count(),
                'payments_total' => Payment::query()->count(),
            ];
        });

        $pendingOrders = $stats['pending_orders'];
        $lowStockCount = $stats['low_stock'];
        $outOfStockCount = $stats['out_of_stock'];
        $user = auth()->user();
        $canSeeOrders = (bool) ($user?->can('orders.view') || $user?->can('reports.view'));
        $cards = [];

        if ($canSeeOrders) {
            $cards[] = $this->metric('Total Revenue', '৳ '.number_format($stats['total_revenue'], 2))
                ->description(__('Today: ৳ :amount', ['amount' => number_format($stats['today_revenue'], 2)]))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($stats['revenue_trend'])
                ->color('success');

            $cards[] = $this->metric('Total Orders', number_format($stats['total_orders']))
                ->description($pendingOrders > 0 ? __(':count pending review', ['count' => $pendingOrders]) : UiText::line('All orders processed'))
                ->descriptionIcon($pendingOrders > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->chart($stats['orders_trend'])
                ->color($pendingOrders > 0 ? 'warning' : 'primary');
        }

        if ($user?->can('customers.view')) {
            $cards[] = $this->metric('Total Customers', number_format($stats['total_customers']))
                ->description(__(':count new this month', ['count' => $stats['new_customers_month']]))
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($stats['customers_trend'])
                ->color('info');
        }

        if ($user?->can('inventory.view')) {
            $cards[] = $this->metric('Stock Alert', __(':count items', ['count' => number_format($lowStockCount)]))
                ->description($outOfStockCount > 0 ? __(':count out of stock', ['count' => $outOfStockCount]) : ($lowStockCount > 0 ? UiText::line('Needs restock') : UiText::line('All items in stock')))
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->url('/admin/inventory/stock-overview');
        }

        if ($user?->can('products.view')) {
            $cards[] = $this->metric('Products', number_format($stats['products_total']))
                ->description(__(':count active', ['count' => number_format($stats['products_active'])]))
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->url('/admin/products');
        }

        if ($user?->can('categories.view')) {
            $cards[] = $this->metric('Categories', number_format($stats['categories_total']))
                ->description(UiText::line('Catalog structure'))
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning')
                ->url('/admin/categories');
        }

        if ($user?->can('brands.view')) {
            $cards[] = $this->metric('Brands', number_format($stats['brands_total']))
                ->description(UiText::line('Brand listings'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->url('/admin/brands');
        }

        if ($user?->can('courier-shipments.view')) {
            $pendingShipments = $stats['courier_shipments_pending'];

            $cards[] = $this->metric('Courier Shipments', number_format($stats['courier_shipments_total']))
                ->description($pendingShipments > 0
                    ? __(':count awaiting dispatch', ['count' => $pendingShipments])
                    : UiText::line('No pending shipments'))
                ->descriptionIcon($pendingShipments > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingShipments > 0 ? 'warning' : 'success')
                ->url('/admin/courier-shipments');
        }

        if ($user?->can('activities.view')) {
            $cards[] = $this->metric('Activities Today', number_format($stats['activities_today']))
                ->description(UiText::line('Admin audit trail'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray')
                ->url('/admin/activities');
        }

        if ($user?->can('reviews.view')) {
            $pendingReviews = $stats['reviews_pending'];

            $cards[] = $this->metric('Reviews', number_format($pendingReviews))
                ->description($pendingReviews > 0 ? UiText::line('Awaiting moderation') : UiText::line('All reviews moderated'))
                ->descriptionIcon($pendingReviews > 0 ? 'heroicon-m-star' : 'heroicon-m-check-badge')
                ->color($pendingReviews > 0 ? 'warning' : 'success')
                ->url('/admin/reviews');
        }

        if ($user?->can('returns.view')) {
            $cards[] = $this->metric('Open Returns', number_format($stats['returns_open']))
                ->description(UiText::line('Needs attention'))
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($stats['returns_open'] > 0 ? 'danger' : 'success')
                ->url('/admin/return-requests');
        }

        if ($user?->can('coupons.view')) {
            $cards[] = $this->metric('Active Coupons', number_format($stats['coupons_active']))
                ->description(UiText::line('Ready to use'))
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary')
                ->url('/admin/coupons');
        }

        if ($user?->can('payments.view') && ! $canSeeOrders) {
            $cards[] = $this->metric('Payments', number_format($stats['payments_total']))
                ->description(UiText::line('Payment records'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url('/admin/payments');
        }

        return $cards;
    }
}
