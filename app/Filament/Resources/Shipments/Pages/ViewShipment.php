<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Shipment;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_packed')
                ->label('Pack Parcel')
                ->icon(Heroicon::OutlinedCube)
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record): bool => $record->canBePacked() && auth()->user()?->can('shipping.update'))
                ->action(function (Shipment $record): void {
                    $record->markAsPacked(actor: auth()->user());
                    Notification::make()
                        ->info()
                        ->title('Parcel Packed')
                        ->body("Shipment {$record->shipment_number} marked as packed.")
                        ->send();
                }),

            Action::make('mark_shipped')
                ->label('Dispatch / Ship')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record): bool => $record->canBeShipped() && auth()->user()?->can('shipping.ship'))
                ->form([
                    TextInput::make('tracking_code')
                        ->label('Carrier Tracking / Consignment ID')
                        ->placeholder('e.g. STDF92817')
                        ->default(fn (Shipment $record): ?string => $record->tracking_code),
                ])
                ->action(function (Shipment $record, array $data): void {
                    $record->markAsShipped(
                        trackingCode: $data['tracking_code'] ?? null,
                        actor: auth()->user()
                    );
                    Notification::make()
                        ->success()
                        ->title('Shipment Dispatched')
                        ->body("Shipment {$record->shipment_number} dispatched.")
                        ->send();
                }),

            Action::make('mark_delivered')
                ->label('Mark Delivered')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record): bool => $record->canBeDelivered() && auth()->user()?->can('shipping.deliver'))
                ->action(function (Shipment $record): void {
                    $record->markAsDelivered(actor: auth()->user());
                    Notification::make()
                        ->success()
                        ->title('Shipment Delivered')
                        ->body("Shipment {$record->shipment_number} successfully delivered.")
                        ->send();
                }),

            Action::make('mark_returned')
                ->label('Mark Returned')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record): bool => ! $record->status->isTerminal() && in_array($record->status, [ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::FailedDelivery], true) && auth()->user()?->can('shipping.update'))
                ->form([
                    TextInput::make('reason')
                        ->label('Return Reason')
                        ->placeholder('e.g. Customer unreachable / refused parcel')
                        ->required(),
                ])
                ->action(function (Shipment $record, array $data): void {
                    $record->markAsReturned(reason: $data['reason'], actor: auth()->user());
                    Notification::make()
                        ->warning()
                        ->title('Shipment Returned')
                        ->body("Shipment {$record->shipment_number} marked as returned.")
                        ->send();
                }),

            Action::make('cancel_shipment')
                ->label('Cancel Consignment')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Shipment $record): bool => $record->canBeCancelled() && auth()->user()?->can('shipping.update'))
                ->form([
                    TextInput::make('reason')
                        ->label('Cancellation Reason')
                        ->placeholder('e.g. Duplicate consignment / customer cancelled')
                        ->required(),
                ])
                ->action(function (Shipment $record, array $data): void {
                    $record->cancel(reason: $data['reason'], actor: auth()->user());
                    Notification::make()
                        ->danger()
                        ->title('Shipment Cancelled')
                        ->body("Shipment {$record->shipment_number} has been cancelled.")
                        ->send();
                }),
        ];
    }
}
