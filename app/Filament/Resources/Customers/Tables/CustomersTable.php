<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->state(fn (Customer $record): float => $record->total_spent)
                    ->formatStateUsing($currencyFormat)
                    ->sortable(),

                IconColumn::make('has_user_account')
                    ->label('Storefront User')
                    ->boolean()
                    ->state(fn (Customer $record): bool => $record->user_id !== null)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Customer Since')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                TernaryFilter::make('registered')
                    ->label('Storefront Account')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('user_id'),
                        false: fn (Builder $q) => $q->whereNull('user_id'),
                    ),

                TernaryFilter::make('has_orders')
                    ->label('Order Activity')
                    ->queries(
                        true: fn (Builder $q) => $q->has('orders'),
                        false: fn (Builder $q) => $q->doesntHave('orders'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Customer $record): void {
                        if (! $record->canBeDeleted()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete customer with placed orders.')
                                ->body('To preserve business and financial integrity, customers with historical orders cannot be deleted.')
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            foreach ($records as $record) {
                                if (! $record->canBeDeleted()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Cannot delete customer with placed orders.')
                                        ->body("Customer '{$record->name}' has existing orders. Deletion halted.")
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ]);
    }
}
