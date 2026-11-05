<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\PaymentMethodPresenter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('orders.view') || $user?->can('reports.view'));
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->withCount('items')->latest()->limit(8)
            )
            ->heading(__('Latest Orders'))
            ->headerActions([
                Action::make('view_all')
                    ->label(__('View All Orders'))
                    ->icon('heroicon-m-arrow-right')
                    ->url('/admin/orders'),
            ])
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $record): ?string => $record->customer_phone ?: $record->customer_email)
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => __(':count items', ['count' => $state]))
                    ->alignCenter(),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn (mixed $state): string => PaymentMethodPresenter::label($state))
                    ->description(fn (Order $record): string => $record->payment_status?->label() ?? (string) $record->payment_status),

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
                    ->tooltip(fn (Order $record): string => $record->created_at?->translatedFormat('M d, Y h:i A') ?? ''),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(fn (Order $record): ?string => OrderResource::canView($record) ? OrderResource::getUrl('view', ['record' => $record]) : null)
            ->paginated(false);
    }
}
