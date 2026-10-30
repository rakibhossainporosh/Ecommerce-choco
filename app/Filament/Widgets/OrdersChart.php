<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Order & Sales Analytics';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '275px';

    public ?string $filter = '30days';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            '12months' => 'Last 12 Months',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter === '7days') {
            $periods = collect(range(6, 0))->map(function ($daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->format('D, M d'),
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                ];
            });
        } elseif ($activeFilter === '12months') {
            $periods = collect(range(11, 0))->map(function ($monthsAgo) {
                $date = Carbon::now()->subMonths($monthsAgo);

                return [
                    'label' => $date->format('M Y'),
                    'start' => $date->copy()->startOfMonth(),
                    'end' => $date->copy()->endOfDay(),
                ];
            });
        } else {
            // Default 30 days
            $periods = collect(range(29, 0))->map(function ($daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->format('M d'),
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
                    'label' => 'Orders Placed',
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
