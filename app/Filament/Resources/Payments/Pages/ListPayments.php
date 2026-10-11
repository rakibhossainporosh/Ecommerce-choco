<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentTransactionStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Record Payment')
                ->modalHeading('Record Payment Transaction')
                ->modalDescription('Enter transaction details to record and reconcile payments against an order.')
                ->modalIcon(Heroicon::OutlinedBanknotes)
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Payments')
                ->badge(Payment::query()->count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Pending))
                ->badge(Payment::query()->where('status', PaymentTransactionStatus::Pending)->count())
                ->badgeColor('warning'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Completed))
                ->badge(Payment::query()->where('status', PaymentTransactionStatus::Completed)->count())
                ->badgeColor('success'),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Failed))
                ->badge(Payment::query()->where('status', PaymentTransactionStatus::Failed)->count())
                ->badgeColor('danger'),
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Refunded))
                ->badge(Payment::query()->where('status', PaymentTransactionStatus::Refunded)->count())
                ->badgeColor('gray'),
        ];
    }
}
