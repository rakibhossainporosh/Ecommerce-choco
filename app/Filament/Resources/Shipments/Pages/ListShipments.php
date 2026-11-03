<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Shipment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Consignment')
                ->modalHeading(__('Create Shipment Consignment'))
                ->modalDescription(__('Fulfill an order by assigning a courier, tracking code, and delivery destination.'))
                ->modalIcon(Heroicon::OutlinedTruck)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        $counts = Shipment::query()
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = fn (ShipmentStatus $status): int => (int) ($counts[$status->value] ?? 0);
        $total = (int) $counts->sum();

        return [
            'all' => Tab::make('All Shipments')
                ->badge($total),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShipmentStatus::Pending))
                ->badge($countFor(ShipmentStatus::Pending))
                ->badgeColor('gray'),
            'in_transit' => Tab::make('In Transit')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ShipmentStatus::Packed,
                    ShipmentStatus::InTransit,
                    ShipmentStatus::OutForDelivery,
                ]))
                ->badge(
                    $countFor(ShipmentStatus::Packed)
                    + $countFor(ShipmentStatus::InTransit)
                    + $countFor(ShipmentStatus::OutForDelivery)
                )
                ->badgeColor('primary'),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShipmentStatus::Delivered))
                ->badge($countFor(ShipmentStatus::Delivered))
                ->badgeColor('success'),
            'exceptions' => Tab::make('Exceptions')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ShipmentStatus::FailedDelivery,
                    ShipmentStatus::Returned,
                    ShipmentStatus::Cancelled,
                ]))
                ->badge(
                    $countFor(ShipmentStatus::FailedDelivery)
                    + $countFor(ShipmentStatus::Returned)
                    + $countFor(ShipmentStatus::Cancelled)
                )
                ->badgeColor('danger'),
        ];
    }
}
