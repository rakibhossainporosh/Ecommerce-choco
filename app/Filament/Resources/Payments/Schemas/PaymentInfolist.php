<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $schema
            ->components([
                Section::make('Transaction Overview')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                    ->schema([
                        TextEntry::make('payment_number')
                            ->label('Payment Ref #')
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('order.order_number')
                            ->label('Associated Order')
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->placeholder('Walk-in / Guest'),

                        TextEntry::make('amount')
                            ->label('Amount')
                            ->formatStateUsing($currencyFormat)
                            ->weight('bold'),

                        TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge()
                            ->formatStateUsing(fn (?PaymentMethod $state): ?string => $state?->label()
                                ?? (is_string($state) ? PaymentMethod::tryFrom($state)?->label() : null)
                                ?? (string) $state
                            ),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state): string => match ($state instanceof PaymentTransactionStatus ? $state : PaymentTransactionStatus::tryFrom((string) $state)) {
                                PaymentTransactionStatus::Completed => 'success',
                                PaymentTransactionStatus::Pending => 'warning',
                                PaymentTransactionStatus::Failed => 'danger',
                                PaymentTransactionStatus::Refunded => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('currency')
                            ->label('Currency'),

                        TextEntry::make('transaction_id')
                            ->label('TrxID / Gateway Ref')
                            ->placeholder('—')
                            ->copyable(),

                        TextEntry::make('account_number')
                            ->label('Sender Account / Phone')
                            ->placeholder('—'),

                        TextEntry::make('paid_at')
                            ->label('Payment Date')
                            ->dateTime('M j, Y H:i')
                            ->placeholder('Pending verification'),

                        TextEntry::make('recordedBy.name')
                            ->label('Recorded By')
                            ->placeholder('Storefront / Customer Checkout'),

                        TextEntry::make('created_at')
                            ->label('Recorded At')
                            ->dateTime('M j, Y H:i'),

                        TextEntry::make('notes')
                            ->label('Merchant / Transaction Notes')
                            ->columnSpanFull()
                            ->visible(fn (Payment $record): bool => filled($record->notes)),
                    ]),
            ]);
    }
}
