<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Shipment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $table
            ->columns([
                TextColumn::make('shipment_number')
                    ->label('Shipment #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->alignCenter(),

                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('shipping_city')
                    ->label('City')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('provider')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(fn (?ShippingProvider $state): ?string => $state?->label()
                        ?? (is_string($state) ? ShippingProvider::tryFrom($state)?->label() : null)
                        ?? (string) $state
                    )
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof ShipmentStatus ? $state : ShipmentStatus::tryFrom((string) $state)) {
                        ShipmentStatus::Pending => 'gray',
                        ShipmentStatus::Packed => 'info',
                        ShipmentStatus::InTransit => 'primary',
                        ShipmentStatus::OutForDelivery => 'warning',
                        ShipmentStatus::Delivered => 'success',
                        ShipmentStatus::FailedDelivery, ShipmentStatus::Cancelled => 'danger',
                        ShipmentStatus::Returned => 'purple',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('tracking_code')
                    ->label('Tracking #')
                    ->searchable()
                    ->copyable()
                    ->placeholder(__('—'))
                    ->url(fn (Shipment $record): ?string => $record->getTrackingUrl(), shouldOpenInNewTab: true)
                    ->alignCenter(),

                TextColumn::make('shipping_charge')
                    ->label('Fee')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('shipped_at')
                    ->label('Dispatched At')
                    ->state(fn (Shipment $record): string => $record->shipped_at
                        ? $record->shipped_at->format('M j, Y H:i')
                        : __('Pending'))
                    ->badge()
                    ->color(fn (string $state): string => $state === __('Pending') ? 'warning' : 'gray')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('delivered_at')
                    ->label('Delivered At')
                    ->state(fn (Shipment $record): string => $record->delivered_at
                        ? $record->delivered_at->format('M j, Y H:i')
                        : __('Pending'))
                    ->badge()
                    ->color(fn (string $state): string => $state === __('Pending') ? 'warning' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ShipmentStatus::class),

                SelectFilter::make('provider')
                    ->options(ShippingProvider::class),
            ])
            ->recordActions([
                ViewAction::make(),

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
                            ->title(__('Parcel Packed'))
                            ->body(__('Shipment :number marked as packed and ready to ship.', ['number' => $record->shipment_number]))
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
                            ->placeholder(__('e.g. STDF92817'))
                            ->default(fn (Shipment $record): ?string => $record->tracking_code),
                    ])
                    ->action(function (Shipment $record, array $data): void {
                        $record->markAsShipped(
                            trackingCode: $data['tracking_code'] ?? null,
                            actor: auth()->user()
                        );
                        Notification::make()
                            ->success()
                            ->title(__('Shipment Dispatched'))
                            ->body(__('Shipment :number is now in transit.', ['number' => $record->shipment_number]))
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
                            ->title(__('Shipment Delivered'))
                            ->body(__('Shipment :number successfully delivered.', ['number' => $record->shipment_number]))
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
                            ->placeholder(__('e.g. Customer unreachable / refused parcel'))
                            ->required(),
                    ])
                    ->action(function (Shipment $record, array $data): void {
                        $record->markAsReturned(reason: $data['reason'], actor: auth()->user());
                        Notification::make()
                            ->warning()
                            ->title(__('Shipment Returned'))
                            ->body(__('Shipment :number marked as returned.', ['number' => $record->shipment_number]))
                            ->send();
                    }),
            ])
            ->toolbarActions([])
            ->striped()
            ->emptyStateHeading(__('No shipments yet'))
            ->emptyStateDescription(__('Create a consignment when an order is ready to pack and dispatch.'))
            ->emptyStateIcon(Heroicon::OutlinedTruck);
    }
}
