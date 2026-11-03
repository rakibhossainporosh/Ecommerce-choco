<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = Order::query()
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = function (OrderStatus $status) use ($counts): int {
            return (int) ($counts[$status->value] ?? 0);
        };

        $total = (int) $counts->sum();

        return [
            'all' => Tab::make('All Orders')
                ->badge($total),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Pending))
                ->badge($countFor(OrderStatus::Pending))
                ->badgeColor('warning'),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Confirmed))
                ->badge($countFor(OrderStatus::Confirmed))
                ->badgeColor('info'),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Processing))
                ->badge($countFor(OrderStatus::Processing))
                ->badgeColor('primary'),
            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Shipped))
                ->badge($countFor(OrderStatus::Shipped))
                ->badgeColor('purple'),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Delivered))
                ->badge($countFor(OrderStatus::Delivered))
                ->badgeColor('success'),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Cancelled))
                ->badge($countFor(OrderStatus::Cancelled))
                ->badgeColor('danger'),
            'returned' => Tab::make('Returned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Returned))
                ->badge($countFor(OrderStatus::Returned))
                ->badgeColor('gray'),
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Refunded))
                ->badge($countFor(OrderStatus::Refunded))
                ->badgeColor('gray'),
        ];
    }
}
