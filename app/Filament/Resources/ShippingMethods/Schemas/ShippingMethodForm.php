<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use App\Enums\ShippingProvider;
use App\Models\ShippingMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Method / Zone Name')
                    ->placeholder('e.g. Inside Dhaka (Standard)')
                    ->prefixIcon(Heroicon::OutlinedTruck)
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, ?ShippingMethod $record): void {
                        if ($record === null && filled($state)) {
                            $set('code', Str::slug($state, '_'));
                        }
                    }),

                TextInput::make('code')
                    ->label('Internal Identifier / Code')
                    ->placeholder('e.g. inside_dhaka_std')
                    ->prefixIcon(Heroicon::OutlinedTag)
                    ->required()
                    ->maxLength(50)
                    ->unique(ShippingMethod::class, 'code', ignoreRecord: true),

                Select::make('provider')
                    ->label('Delivery Partner / Courier')
                    ->options(ShippingProvider::class)
                    ->default(ShippingProvider::Steadfast)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedBuildingStorefront),

                TextInput::make('charge')
                    ->label('Standard Shipping Fee (BDT)')
                    ->prefix('৳')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(70.00),

                TextInput::make('free_shipping_threshold')
                    ->label('Free Shipping Minimum Spend (BDT)')
                    ->prefix('৳')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->placeholder('Optional threshold (e.g. 2000.00)')
                    ->helperText('Customers enjoy free delivery when their order subtotal meets this amount.'),

                TextInput::make('estimated_days_min')
                    ->label('Min Delivery Days')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                TextInput::make('estimated_days_max')
                    ->label('Max Delivery Days')
                    ->numeric()
                    ->minValue(1)
                    ->default(3)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active for Customer Checkout')
                    ->default(true)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Customer Display Description')
                    ->placeholder('e.g. Standard home delivery within Dhaka metro.')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
