<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Payments\PaymentManager;
use App\Support\PaymentMethodPresenter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('Order number copied!'))
                    ->weight('bold'),

                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('customer_phone')
                    ->label('Customer Phone')
                    ->searchable()
                    ->alignCenter()
                    ->copyable()
                    ->copyMessage(__('Phone number copied!')),

                TextColumn::make('customer_email')
                    ->label('Customer Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
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
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?PaymentStatus $state): ?string => $state?->label()
                        ?? (is_string($state) ? PaymentStatus::tryFrom($state)?->label() : null)
                        ?? $state?->value
                        ?? (string) $state
                    )
                    ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::tryFrom((string) $state)) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Unpaid, PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->alignCenter()
                    ->formatStateUsing(fn (mixed $state): string => PaymentMethodPresenter::label($state))
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state): string => config('currency.symbol', '৳').' '.number_format(
                        (float) $state,
                        (int) config('currency.decimals', 2),
                        (string) config('currency.decimal_separator', '.'),
                        (string) config('currency.thousands_separator', ',')
                    ))
                    ->sortable(),

                TextColumn::make('placed_at')
                    ->label('Placed At')
                    ->alignCenter()
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->placeholder(__('—')),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [
                        $status->value => $status->label(),
                    ])->all()),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status) => [
                        $status->value => $status->label(),
                    ])->all()),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(fn (): array => app(PaymentManager::class)->options()),

                Filter::make('placed_at')
                    ->form([
                        DatePicker::make('placed_from')->label('Placed From'),
                        DatePicker::make('placed_until')->label('Placed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['placed_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('placed_at', '>=', $date))
                            ->when($data['placed_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('placed_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('printInvoice')
                    ->label('Invoice')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (Order $record): string => route('admin.orders.invoice', $record), shouldOpenInNewTab: true),
                OrderActions::makeConfirmAction(),
                OrderActions::makeProcessAction(),
                OrderActions::makeShipAction(),
                OrderActions::makeDeliverAction(),
                OrderActions::makeCancelAction(),
            ])
            ->toolbarActions([])
            ->recordUrl(fn (Order $record): ?string => OrderResource::canView($record) ? OrderResource::getUrl('view', ['record' => $record]) : null);
    }
}
