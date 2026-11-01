<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Order & Sales Analytics';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '275px';

    public ?string $filter = '30days';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ? __($this->heading) : null;
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => __('Last 7 Days'),
            '30days' => __('Last 30 Days'),
            '12months' => __('Last 12 Months'),
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter === '7days') {
            $periods = collect(range(6, 0))->map(function ($daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->translatedFormat('D, M d'),
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                ];
            });
        } elseif ($activeFilter === '12months') {
            $periods = collect(range(11, 0))->map(function ($monthsAgo) {
                $date = Carbon::now()->subMonths($monthsAgo);

                return [
                    'label' => $date->translatedFormat('M Y'),
                    'start' => $date->copy()->startOfMonth(),
                    'end' => $date->copy()->endOfDay(),
                ];
            });
        } else {
            // Default 30 days
            $periods = collect(range(29, 0))->map(function ($daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->translatedFormat('M d'),
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                ];
            });
        }

        $startDate = $periods->first()['start'];
        $endDate = $periods->last()['end'];

        $orders = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'created_at', 'grand_total', 'status']);

        $labels = [];
        $ordersData = [];

        foreach ($periods as $period) {
            $labels[] = $period['label'];
            $periodOrders = $orders->filter(function ($order) use ($period) {
                return $order->created_at >= $period['start'] && $order->created_at <= $period['end'];
            });

            $ordersData[] = $periodOrders->count();
        }

        return [
            'datasets' => [
                [
                    'label' => __('Orders Placed'),
                    'data' => $ordersData,
                    'borderColor' => '#E86A28',
                    'backgroundColor' => 'rgba(232, 106, 40, 0.15)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
