<?php

namespace App\Filament\Resources\ReturnRequests\Pages;

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Return Request')
                ->modalHeading(__('Submit Return Request'))
                ->modalDescription(__('Enter transaction and defect details to record a return request against an order.'))
                ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
                ->modalWidth(Width::TwoExtraLarge)
                ->using(function (array $data): Model {
                    $lineItems = $data['line_items'] ?? [];
                    unset($data['line_items'], $data['status']);

                    $order = Order::with('items')->findOrFail($data['order_id']);

                    return ReturnRequest::createPendingForOrder(
                        $order,
                        $data,
                        is_array($lineItems) ? $lineItems : [],
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        $counts = ReturnRequest::query()
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = fn (ReturnRequestStatus $status): int => (int) ($counts[$status->value] ?? 0);
        $total = (int) $counts->sum();

        return [
            'all' => Tab::make('All Requests')
                ->badge($total),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Pending))
                ->badge($countFor(ReturnRequestStatus::Pending))
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Approved))
                ->badge($countFor(ReturnRequestStatus::Approved))
                ->badgeColor('info'),
            'received' => Tab::make('Received')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Received))
                ->badge($countFor(ReturnRequestStatus::Received))
                ->badgeColor('primary'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Completed))
                ->badge($countFor(ReturnRequestStatus::Completed))
                ->badgeColor('success'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Rejected))
                ->badge($countFor(ReturnRequestStatus::Rejected))
                ->badgeColor('danger'),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Cancelled))
                ->badge($countFor(ReturnRequestStatus::Cancelled))
                ->badgeColor('gray'),
        ];
    }
}
