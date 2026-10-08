<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Orders per Month';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Order::select(
            DB::raw('count(id) as total_orders'),
            DB::raw('date_format(created_at, "%Y-%m") as month')
        )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $totals = [];

        // Fill in missing months
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $labels[] = Carbon::now()->subMonths($i)->format('M Y');

            $monthData = $data->firstWhere('month', $month);
            $totals[] = $monthData ? $monthData->total_orders : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $totals,
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
