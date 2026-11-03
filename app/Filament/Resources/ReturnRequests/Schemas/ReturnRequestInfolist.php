<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Models\ReturnRequest;
use Filament\Infolists\Components\RepeatableEntry;
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
                    TextEntry::make('rma_number')
                        ->label('RMA #')
                        ->weight('bold')
                        ->copyable(),

                    TextEntry::make('order.order_number')
                        ->label('Order #')
                        ->weight('bold')
                        ->copyable()
                        ->url(fn (ReturnRequest $record): ?string => $record->order_id ? route('filament.admin.resources.orders.view', ['record' => $record->order_id]) : null),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                ]),

                Grid::make(3)->schema([
                    TextEntry::make('customer_name')
                        ->label('Customer')
                        ->weight('bold')
                        ->state(fn (ReturnRequest $record): string => $record->user?->name
                            ?? $record->order?->customer_name
                            ?? $record->order?->customer?->name
                            ?? 'Guest / Walk-in'
                        ),

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
                        ->label('Admin Note / Rejection Reason')
                        ->placeholder(__('No internal note recorded yet.')),
                ]),
            ]),

            Section::make('Returned Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->placeholder(__('Full order return — no line items recorded.'))
                        ->schema([
                            TextEntry::make('orderItem.product_name')
                                ->label('Product')
                                ->state(fn ($record): string => $record->orderItem?->variant_name
                                    ?: $record->orderItem?->product_name
                                    ?: '—'),

                            TextEntry::make('quantity')
                                ->label('Qty'),

                            TextEntry::make('refund_amount')
                                ->label('Line Refund')
                                ->formatStateUsing($currencyFormat),
                        ])
                        ->columns(3),
                ])
                ->collapsed(false),

            Section::make('Status Timeline')
                ->schema([
                    RepeatableEntry::make('statusHistories')
                        ->hiddenLabel()
                        ->placeholder(__('No status history yet.'))
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('When')
                                ->dateTime('M j, Y H:i'),

                            TextEntry::make('from_status')
                                ->label('From')
                                ->badge()
                                ->placeholder('—'),

                            TextEntry::make('to_status')
                                ->label('To')
                                ->badge(),

                            TextEntry::make('changedBy.name')
                                ->label('By')
                                ->placeholder(__('System / Customer')),

                            TextEntry::make('reason')
                                ->label('Note')
                                ->placeholder('—')
                                ->columnSpanFull(),
                        ])
                        ->columns(4),
                ])
                ->collapsed(),
        ]);
    }
}
