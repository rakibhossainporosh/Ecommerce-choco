<?php

namespace App\Filament\Resources\CourierShipments\Schemas;

use App\Enums\CourierShipmentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CourierShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Consignment')
                    ->description(__('Link the order to a courier partner and assign tracking.'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabledOn('edit')
                            ->prefixIcon(Heroicon::OutlinedShoppingCart),

                        Select::make('courier_id')
                            ->label('Courier')
                            ->relationship('courier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedTruck),

                        TextInput::make('tracking_number')
                            ->label('Tracking number')
                            ->maxLength(255)
                            ->placeholder(__('e.g. TRK-1234-ABCD'))
                            ->prefixIcon(Heroicon::OutlinedHashtag),

                        Select::make('status')
                            ->label('Status')
                            ->options(CourierShipmentStatus::class)
                            ->required()
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedSignal),
                    ]),

                Section::make('Timeline')
                    ->description(__('Dispatch and delivery milestones for this consignment.'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        DateTimePicker::make('shipped_at')
                            ->label('Shipped at')
                            ->seconds(false),

                        DateTimePicker::make('delivered_at')
                            ->label('Delivered at')
                            ->seconds(false),
                    ]),
            ]);
    }
}
