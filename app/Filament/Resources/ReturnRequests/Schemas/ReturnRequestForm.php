<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReturnRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Return Request Details')->schema([
                Grid::make(2)->schema([
                    Select::make('order_id')
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
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->placeholder('Guest / Walk-in')
                        ->prefixIcon(Heroicon::OutlinedUser),

                    Select::make('status')
                        ->options(ReturnRequestStatus::class)
                        ->default(ReturnRequestStatus::Pending)
                        ->required()
                        ->prefixIcon(Heroicon::OutlinedCheckCircle),

                    TextInput::make('refund_amount')
                        ->numeric()
                        ->prefix(config('currency.symbol', '৳'))
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->helperText('Cannot exceed the associated order grand total.'),
                ]),
                Grid::make(1)->schema([
                    Textarea::make('reason')
                        ->label('Reason for Return')
                        ->placeholder('Describe the defects, damage, or customer complaint...')
                        ->rows(3)
                        ->required(),

                    Textarea::make('admin_note')
                        ->label('Admin Note (Internal)')
                        ->placeholder('Internal review notes, investigation findings, or rejection reason...')
                        ->rows(3),
                ]),
            ]),
        ]);
    }
}
