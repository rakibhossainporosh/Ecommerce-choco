<?php

namespace App\Filament\Resources\AbandonedCarts\Tables;

use App\Enums\AbandonedCartStatus;
use App\Filament\Resources\AbandonedCarts\AbandonedCartResource;
use App\Models\AbandonedCart;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AbandonedCartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('Mobile number')
                    ->placeholder('—')
                    ->searchable()
                    ->alignCenter()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items')
                    ->label('Items')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->state(fn (AbandonedCart $record): int => (int) collect($record->items ?? [])->sum(
                        fn (mixed $item): int => is_array($item) ? (int) ($item['quantity'] ?? 0) : 0
                    )),
                TextColumn::make('subtotal')
                    ->label('Total')
                    ->alignCenter()
                    ->formatStateUsing(fn (mixed $state): string => config('currency.symbol', '৳').' '.number_format(
                        (float) $state,
                        (int) config('currency.decimals', 2),
                        (string) config('currency.decimal_separator', '.'),
                        (string) config('currency.thousands_separator', ',')
                    ))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (AbandonedCartStatus|string|null $state): string => match ($state instanceof AbandonedCartStatus ? $state : AbandonedCartStatus::tryFrom((string) $state)) {
                        AbandonedCartStatus::Open => 'warning',
                        AbandonedCartStatus::Reminded => 'info',
                        AbandonedCartStatus::Recovered => 'success',
                        AbandonedCartStatus::Closed => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('last_activity_at')
                    ->label('Last seen')
                    ->alignCenter()
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('reminded_at')
                    ->label('Reminded')
                    ->alignCenter()
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_activity_at', 'desc')
            ->paginated(! app()->runningUnitTests())
            ->filters([
                SelectFilter::make('status')
                    ->options(AbandonedCartStatus::class),
            ])
            ->recordUrl(fn (AbandonedCart $record): string => AbandonedCartResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (AbandonedCart $record): bool => in_array($record->status, [AbandonedCartStatus::Open, AbandonedCartStatus::Reminded], true)
                        && (bool) auth()->user()?->can('orders.update'))
                    ->action(function (AbandonedCart $record): void {
                        $record->status = AbandonedCartStatus::Closed;
                        $record->save();

                        Notification::make()
                            ->title(__('Cart closed.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
