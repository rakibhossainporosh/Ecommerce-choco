<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReturnRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state && ($order = Order::find($state))) {
                            if ($order->user_id) {
                                $set('user_id', $order->user_id);
                            }
                            $set('refund_amount', (float) $order->grand_total);
                        }
                    })
                    ->prefixIcon(Heroicon::OutlinedShoppingCart),

                Select::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('Guest / Walk-in')
                    ->prefixIcon(Heroicon::OutlinedUser),

                Select::make('status')
                    ->label('Status')
                    ->options(ReturnRequestStatus::class)
                    ->default(ReturnRequestStatus::Pending)
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedCheckCircle),

                TextInput::make('refund_amount')
                    ->label('Refund Amount')
                    ->numeric()
                    ->prefix(config('currency.symbol', '৳'))
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->helperText('Cannot exceed the associated order grand total.'),

                Textarea::make('reason')
                    ->label('Reason for Return')
                    ->placeholder('Describe the defects, damage, or customer complaint...')
                    ->rows(3)
                    ->columnSpanFull()
                    ->required(),

                Textarea::make('admin_note')
                    ->label('Admin Note (Internal)')
                    ->placeholder('Internal review notes, investigation findings, or rejection reason...')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
