<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->withCount('items')->latest()->limit(8)
            )
            ->heading('Latest Orders')
            ->headerActions([
                Action::make('view_all')
                    ->label('View All Orders')
                    ->icon('heroicon-m-arrow-right')
                    ->url('/admin/orders'),
            ])
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $record): ?string => $record->customer_phone ?: $record->customer_email)
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('gray')
                    ->suffix(' items')
                    ->alignCenter(),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn (?PaymentMethod $state): ?string => $state?->label()
                        ?? (is_string($state) ? PaymentMethod::tryFrom($state)?->label() : null)
                        ?? (string) $state
                    )
                    ->description(fn (Order $record): string => strtoupper($record->payment_status?->value ?? (string) $record->payment_status)),

                TextColumn::make('status')
                    ->label('Order Status')
                    ->badge()
                    ->formatStateUsing(fn (?OrderStatus $state): ?string => $state?->label()
                        ?? (is_string($state) ? OrderStatus::tryFrom($state)?->label() : null)
                        ?? $state?->value
                        ?? (string) $state
                    )
                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state)) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Confirmed => 'info',
                        OrderStatus::Processing => 'primary',
                        OrderStatus::Shipped => 'purple',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                        OrderStatus::Returned, OrderStatus::Refunded => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('grand_total')
                    ->label('Total Amount')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state): string => '৳ '.number_format((float) $state, 2)),

                TextColumn::make('created_at')
                    ->label('Placed At')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (Order $record): string => $record->created_at?->format('M d, Y h:i A') ?? ''),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
