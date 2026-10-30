<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ShippingMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('order_id')
                    ->label('Associated Order')
                    ->relationship('order', 'order_number')
                    ->getOptionLabelFromRecordUsing(fn (Order $record): string => "{$record->order_number} — {$record->customer_name} ({$record->shipping_city})")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state && ($order = Order::find($state))) {
                            $set('customer_id', $order->customer_id ?? Customer::where('user_id', $order->user_id)->value('id'));
                            $set('recipient_name', $order->customer_name);
                            $set('recipient_phone', $order->customer_phone);
                            $set('shipping_address_line', $order->shipping_address_line);
                            $set('shipping_area', $order->shipping_area);
                            $set('shipping_city', $order->shipping_city ?? 'Dhaka');
                            $set('shipping_postcode', $order->shipping_postcode);
                            $set('shipping_country', $order->shipping_country ?? 'Bangladesh');
                            $set('shipping_charge', (float) $order->shipping_amount);
                        }
                    })
                    ->prefixIcon(Heroicon::OutlinedShoppingCart),

                Select::make('customer_id')
                    ->label('Customer Profile')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('Guest / Walk-in')
                    ->prefixIcon(Heroicon::OutlinedUser),

                Select::make('shipping_method_id')
                    ->label('Shipping Zone / Rate')
                    ->relationship('shippingMethod', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state && ($method = ShippingMethod::find($state))) {
                            $set('provider', $method->provider->value);
                            $set('shipping_charge', (float) $method->charge);
                        }
                    })
                    ->prefixIcon(Heroicon::OutlinedTruck),

                Select::make('provider')
                    ->label('Courier Partner')
                    ->options(ShippingProvider::class)
                    ->default(ShippingProvider::Steadfast)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedBuildingStorefront),

                Select::make('status')
                    ->label('Shipment Status')
                    ->options(ShipmentStatus::class)
                    ->default(ShipmentStatus::Pending)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedCheckCircle),

                TextInput::make('tracking_code')
                    ->label('Consignment ID / Tracking Code')
                    ->placeholder('e.g. STDF109283')
                    ->maxLength(100)
                    ->nullable()
                    ->prefixIcon(Heroicon::OutlinedIdentification),

                TextInput::make('shipping_charge')
                    ->label('Delivery Fee')
                    ->prefix('৳')
                    ->numeric()
                    ->minValue(0)
                    ->default(70.00)
                    ->required(),

                TextInput::make('weight_kg')
                    ->label('Parcel Weight')
                    ->suffix('kg')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->nullable()
                    ->placeholder('e.g. 0.50'),

                TextInput::make('recipient_name')
                    ->label('Recipient Full Name')
                    ->required()
                    ->maxLength(255)
                    ->prefixIcon(Heroicon::OutlinedUser),

                TextInput::make('recipient_phone')
                    ->label('Recipient Phone')
                    ->tel()
                    ->required()
                    ->maxLength(50)
                    ->prefixIcon(Heroicon::OutlinedPhone),

                TextInput::make('shipping_address_line')
                    ->label('Delivery Address Line')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->prefixIcon(Heroicon::OutlinedMapPin),

                TextInput::make('shipping_area')
                    ->label('Area / Thana')
                    ->placeholder('e.g. Dhanmondi, Gulshan, Mirpur')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('shipping_city')
                    ->label('City / District')
                    ->default('Dhaka')
                    ->required()
                    ->maxLength(100),

                TextInput::make('shipping_postcode')
                    ->label('Postal Code')
                    ->placeholder('e.g. 1205')
                    ->maxLength(20)
                    ->nullable(),

                TextInput::make('shipping_country')
                    ->label('Country')
                    ->default('Bangladesh')
                    ->required()
                    ->maxLength(50),

                Textarea::make('notes')
                    ->label('Fulfillment & Delivery Notes')
                    ->placeholder('Handling instructions (fragile, liquid hair oil packaging, etc.)...')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
