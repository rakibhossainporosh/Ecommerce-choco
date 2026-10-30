<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders')
                ->badge(Order::query()->count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Pending))
                ->badge(Order::query()->where('status', OrderStatus::Pending)->count())
                ->badgeColor('warning'),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Confirmed))
                ->badge(Order::query()->where('status', OrderStatus::Confirmed)->count())
                ->badgeColor('info'),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Processing))
                ->badge(Order::query()->where('status', OrderStatus::Processing)->count())
                ->badgeColor('primary'),
            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Shipped))
                ->badge(Order::query()->where('status', OrderStatus::Shipped)->count())
                ->badgeColor('purple'),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Delivered))
                ->badge(Order::query()->where('status', OrderStatus::Delivered)->count())
                ->badgeColor('success'),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Cancelled))
                ->badge(Order::query()->where('status', OrderStatus::Cancelled)->count())
                ->badgeColor('danger'),
        ];
    }
}
