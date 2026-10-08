<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    protected static ?string $title = 'Shipment & Delivery History';

    public function table(Table $table): Table
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

                TextColumn::make('provider')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(fn (?ShippingProvider $state): ?string => $state?->label()
                        ?? (is_string($state) ? ShippingProvider::tryFrom($state)?->label() : null)
                        ?? (string) $state
                    )
                    ->sortable(),

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
                    ->sortable(),

                TextColumn::make('tracking_code')
                    ->label('Tracking #')
                    ->copyable()
                    ->placeholder('—')
                    ->url(fn (Shipment $record): ?string => $record->getTrackingUrl(), shouldOpenInNewTab: true),

                TextColumn::make('shipping_charge')
                    ->label('Delivery Fee')
                    ->formatStateUsing($currencyFormat)
                    ->sortable(),

                TextColumn::make('shipped_at')
                    ->label('Dispatched At')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('Pending')
                    ->sortable(),

                TextColumn::make('delivered_at')
                    ->label('Delivered At')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('Pending')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Create Consignment')
                    ->modalHeading('Create Shipment for this Order')
                    ->modalDescription('Generate a consignment with courier and tracking details.')
                    ->modalIcon(Heroicon::OutlinedTruck)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn (): bool => auth()->user()?->can('shipping.create') ?? false)
                    ->form([
                        Select::make('provider')
                            ->label('Courier Partner')
                            ->options(ShippingProvider::class)
                            ->default(ShippingProvider::Steadfast)
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedBuildingStorefront),

                        Select::make('shipping_method_id')
                            ->label('Shipping Zone / Rate')
                            ->relationship('shippingMethod', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedTruck),

                        TextInput::make('tracking_code')
                            ->label('Tracking Code / Consignment ID')
                            ->placeholder('e.g. STDF92817')
                            ->maxLength(100)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedIdentification),

                        TextInput::make('weight_kg')
                            ->label('Parcel Weight')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0.50),

                        Textarea::make('notes')
                            ->label('Packaging & Delivery Notes')
                            ->placeholder('Special instructions for delivery rider...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Order $order */
                        $order = $this->getOwnerRecord();

                        $data['customer_id'] = $order->customer_id ?? Customer::where('user_id', $order->user_id)->value('id');
                        $data['recipient_name'] = $order->customer_name;
                        $data['recipient_phone'] = $order->customer_phone;
                        $data['shipping_address_line'] = $order->shipping_address_line;
                        $data['shipping_area'] = $order->shipping_area;
                        $data['shipping_city'] = $order->shipping_city ?? 'Dhaka';
                        $data['shipping_postcode'] = $order->shipping_postcode;
                        $data['shipping_country'] = $order->shipping_country ?? 'Bangladesh';
                        $data['shipping_charge'] = (float) $order->shipping_amount;
                        $data['status'] = ShipmentStatus::Pending;
                        if (auth()->check()) {
                            $data['dispatched_by'] = auth()->id();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Shipment $record): string => ShipmentResource::getUrl('view', ['record' => $record])),

                Action::make('mark_packed')
                    ->label('Pack')
                    ->icon(Heroicon::OutlinedCube)
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Shipment $record): bool => $record->canBePacked() && auth()->user()?->can('shipping.update'))
                    ->action(function (Shipment $record): void {
                        $record->markAsPacked(actor: auth()->user());
                        Notification::make()->info()->title('Parcel Packed')->send();
                    }),

                Action::make('mark_shipped')
                    ->label('Ship')
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Shipment $record): bool => $record->canBeShipped() && auth()->user()?->can('shipping.ship'))
                    ->form([
                        TextInput::make('tracking_code')
                            ->label('Tracking Code')
                            ->default(fn (Shipment $record): ?string => $record->tracking_code),
                    ])
                    ->action(function (Shipment $record, array $data): void {
                        $record->markAsShipped(
                            trackingCode: $data['tracking_code'] ?? null,
                            actor: auth()->user()
                        );
                        Notification::make()->success()->title('Shipment Dispatched')->send();
                    }),

                Action::make('mark_delivered')
                    ->label('Deliver')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Shipment $record): bool => $record->canBeDelivered() && auth()->user()?->can('shipping.deliver'))
                    ->action(function (Shipment $record): void {
                        $record->markAsDelivered(actor: auth()->user());
                        Notification::make()->success()->title('Shipment Delivered')->send();
                    }),
            ]);
    }

    public function canCreate(): bool
    {
        return auth()->user()?->can('shipping.create') ?? false;
    }

    public function canEdit(Model $record): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return $record instanceof Shipment ? $record->canBeCancelled() || $record->status === ShipmentStatus::Pending : false;
    }
}
