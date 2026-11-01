<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Order Status Breakdown';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ? __($this->heading) : null;
    }

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '275px';

    protected function getData(): array
    {
        $statusConfigs = [
            OrderStatus::Pending->value => ['label' => OrderStatus::Pending->label(), 'color' => '#F59E0B'],
            OrderStatus::Confirmed->value => ['label' => OrderStatus::Confirmed->label(), 'color' => '#0EA5E9'],
            OrderStatus::Processing->value => ['label' => OrderStatus::Processing->label(), 'color' => '#3B82F6'],
            OrderStatus::Shipped->value => ['label' => OrderStatus::Shipped->label(), 'color' => '#8B5CF6'],
            OrderStatus::Delivered->value => ['label' => OrderStatus::Delivered->label(), 'color' => '#10B981'],
            OrderStatus::Cancelled->value => ['label' => OrderStatus::Cancelled->label(), 'color' => '#EF4444'],
            OrderStatus::Returned->value => ['label' => OrderStatus::Returned->label(), 'color' => '#64748B'],
        ];

        $counts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($statusConfigs as $key => $config) {
            $total = $counts[$key] ?? 0;
            if ($total > 0 || in_array($key, [OrderStatus::Pending->value, OrderStatus::Processing->value, OrderStatus::Delivered->value])) {
                $labels[] = "{$config['label']} ({$total})";
                $data[] = $total;
                $backgroundColors[] = $config['color'];
            }
        }

        if (array_sum($data) === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#334155'],
                    ],
                ],
                'labels' => [__('No Orders Yet')],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 2,
                    'borderColor' => '#0F172A',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 16,
                    ],
                ],
            ],
            'cutout' => '65%',
            'maintainAspectRatio' => false,
        ];
    }
}
