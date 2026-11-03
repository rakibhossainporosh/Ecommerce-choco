<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\ReturnItemValidator;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
                    ->relationship(
                        'order',
                        'order_number',
                        fn ($query) => $query->where('status', OrderStatus::Delivered),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        $set('line_items', []);
                        $set('customer_id', null);

                        if ($state && ($order = Order::with('items')->find($state))) {
                            if ($order->user_id) {
                                $set('user_id', $order->user_id);
                            }
                            if ($order->customer_id) {
                                $set('customer_id', $order->customer_id);
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
                    ->placeholder(__('Guest / Walk-in'))
                    ->prefixIcon(Heroicon::OutlinedUser),

                Hidden::make('customer_id'),

                TextInput::make('refund_amount')
                    ->label('Refund Amount')
                    ->numeric()
                    ->prefix(config('currency.symbol', '৳'))
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->helperText(__('Synced from selected lines. Leave lines empty to return all remaining units (goods full return; amount can be lowered for goodwill).')),

                Textarea::make('reason')
                    ->label('Reason for Return')
                    ->placeholder(__('Describe the defects, damage, or customer complaint...'))
                    ->rows(3)
                    ->columnSpanFull()
                    ->required(),

                Textarea::make('admin_note')
                    ->label('Admin Note (Internal)')
                    ->placeholder(__('Internal review notes, investigation findings, or rejection reason...'))
                    ->rows(3)
                    ->columnSpanFull(),

                Repeater::make('line_items')
                    ->label('Return Line Items')
                    ->helperText(__('Leave empty to auto-return every remaining unit. Quantities cannot exceed remaining returnable stock.'))
                    ->columnSpanFull()
                    ->default([])
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function (?array $state, callable $get, callable $set): void {
                        $orderId = $get('order_id');
                        if (! $orderId || blank($state)) {
                            return;
                        }

                        $order = Order::with('items')->find($orderId);
                        if (! $order) {
                            return;
                        }

                        try {
                            $normalized = ReturnItemValidator::normalize($order, $state ?? []);
                            if ($normalized !== []) {
                                $set('refund_amount', ReturnItemValidator::refundTotal($order, $normalized));
                            }
                        } catch (\Throwable) {
                            // Keep manual refund_amount until save-time validation.
                        }
                    })
                    ->schema([
                        Select::make('order_item_id')
                            ->label('Order Item')
                            ->options(function (callable $get): array {
                                $orderId = $get('../../order_id') ?? $get('../../../order_id');

                                if (! $orderId) {
                                    return [];
                                }

                                $order = Order::with('items')->find($orderId);

                                if (! $order) {
                                    return [];
                                }

                                $remaining = ReturnItemValidator::remainingQuantities($order);

                                return $order->items
                                    ->filter(fn ($item): bool => (int) ($remaining[$item->id] ?? 0) > 0)
                                    ->mapWithKeys(fn ($item): array => [
                                        $item->id => ($item->variant_name ?: $item->product_name)
                                            .' × remaining '.($remaining[$item->id] ?? 0),
                                    ])
                                    ->all();
                            })
                            ->required()
                            ->searchable()
                            ->live(),

                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->default(1)
                            ->live(),

                        TextInput::make('refund_amount')
                            ->label('Line Refund')
                            ->numeric()
                            ->prefix(config('currency.symbol', '৳'))
                            ->minValue(0)
                            ->required()
                            ->default(0),
                    ])
                    ->columns(3)
                    ->addActionLabel(__('Add return item'))
                    ->collapsible(),
            ]);
    }
}
