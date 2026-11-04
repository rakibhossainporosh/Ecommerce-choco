<?php

namespace App\Filament\Resources\CourierShipments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CourierShipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Consignment')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('order.order_number')
                            ->label('Order #')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('courier.name')
                            ->label('Courier'),
                        TextEntry::make('tracking_number')
                            ->placeholder(__('—'))
                            ->copyable(),
                        TextEntry::make('status')->badge(),
                    ]),

                Section::make('Timeline')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('shipped_at')
                            ->dateTime()
                            ->placeholder(__('Pending')),
                        TextEntry::make('delivered_at')
                            ->dateTime()
                            ->placeholder(__('Pending')),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
