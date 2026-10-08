<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'order_number')
                    ->getOptionLabelFromRecordUsing(fn (Order $record): string => "{$record->order_number} — {$record->customer_name} (Due: ৳".number_format($record->due_amount, 2).')')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state && ($order = Order::find($state))) {
                            $set('amount', $order->due_amount > 0 ? $order->due_amount : $order->grand_total);
                            $set('customer_id', $order->customer_id ?? Customer::where('user_id', $order->user_id)->value('id'));
                        }
                    })
                    ->prefixIcon(Heroicon::OutlinedShoppingCart),

                Select::make('customer_id')
                    ->label('Customer Profile')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('Walk-in / Guest')
                    ->prefixIcon(Heroicon::OutlinedUser),

                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Bkash)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedCreditCard),

                Select::make('status')
                    ->label('Payment Status')
                    ->options(PaymentTransactionStatus::class)
                    ->default(PaymentTransactionStatus::Completed)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedCheckCircle),

                TextInput::make('amount')
                    ->label('Amount (BDT)')
                    ->prefix('৳')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->placeholder('0.00'),

                TextInput::make('transaction_id')
                    ->label('Transaction ID / TrxID')
                    ->placeholder('e.g. 9J2834K1')
                    ->maxLength(100)
                    ->nullable()
                    ->prefixIcon(Heroicon::OutlinedIdentification),

                TextInput::make('account_number')
                    ->label('Sender Account / Phone Number')
                    ->placeholder('e.g. 017XXXXXXXX')
                    ->maxLength(50)
                    ->nullable()
                    ->tel()
                    ->prefixIcon(Heroicon::OutlinedPhone),

                DateTimePicker::make('paid_at')
                    ->label('Payment Date & Time')
                    ->default(now())
                    ->nullable()
                    ->prefixIcon(Heroicon::OutlinedCalendar),

                Textarea::make('notes')
                    ->label('Merchant / Transaction Notes')
                    ->placeholder('Verification details, transaction notes...')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
