<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Models\ReturnRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReturnRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $schema->components([
            Section::make('Return Request Information')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('order.order_number')
                        ->label('Order #')
                        ->weight('bold')
                        ->copyable()
                        ->url(fn (ReturnRequest $record): ?string => $record->order_id ? route('filament.admin.resources.orders.view', ['record' => $record->order_id]) : null),

                    TextEntry::make('user.name')
                        ->label('Customer')
                        ->weight('bold')
                        ->placeholder('Guest / Walk-in'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                ]),

                Grid::make(2)->schema([
                    TextEntry::make('refund_amount')
                        ->label('Refund Amount')
                        ->formatStateUsing($currencyFormat)
                        ->weight('bold'),

                    TextEntry::make('created_at')
                        ->label('Submitted At')
                        ->dateTime('M j, Y H:i'),
                ]),

                Grid::make(1)->schema([
                    TextEntry::make('reason')
                        ->label('Customer Reason for Return'),

                    TextEntry::make('admin_note')
                        ->label('Internal Admin Note')
                        ->placeholder('No internal note recorded yet.'),
                ]),
            ]),
        ]);
    }
}
