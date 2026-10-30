<?php

namespace App\Filament\Resources\ReturnRequests\Pages;

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\ReturnRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Return Request')
                ->modalHeading('Submit Return Request')
                ->modalDescription('Enter transaction and defect details to record a return request against an order.')
                ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Requests')
                ->badge(ReturnRequest::query()->count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Pending))
                ->badge(ReturnRequest::query()->where('status', ReturnRequestStatus::Pending)->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Approved))
                ->badge(ReturnRequest::query()->where('status', ReturnRequestStatus::Approved)->count())
                ->badgeColor('info'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Completed))
                ->badge(ReturnRequest::query()->where('status', ReturnRequestStatus::Completed)->count())
                ->badgeColor('success'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReturnRequestStatus::Rejected))
                ->badge(ReturnRequest::query()->where('status', ReturnRequestStatus::Rejected)->count())
                ->badgeColor('danger'),
        ];
    }
}
