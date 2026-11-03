<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Filament\Resources\Orders\Actions\OrderOpsActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTitle(): string
    {
        return __('Order #:number', ['number' => $this->record->order_number]);
    }

    public function getSubheading(): ?string
    {
        $placedAt = $this->record->placed_at ?? $this->record->created_at;

        return __('Placed on :date • Customer: :name', [
            'date' => $placedAt->translatedFormat('d M Y, h:i A'),
            'name' => $this->record->customer_name,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printInvoice')
                ->label('Print Invoice')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (): string => route('admin.orders.invoice', $this->record), shouldOpenInNewTab: true),
            OrderOpsActions::makePrintPackingSlipAction(),
            OrderOpsActions::makeEditOpsNotesAction(),
            OrderOpsActions::makeEditShippingAddressAction(),
            OrderOpsActions::makeResendStatusEmailAction(),
            OrderActions::makeConfirmAction(),
            OrderActions::makeProcessAction(),
            OrderActions::makeShipAction(),
            OrderActions::makeDeliverAction(),
            OrderActions::makeCancelAction(),
            OrderActions::makeRefundAction(),
        ];
    }
}
