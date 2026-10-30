<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
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
                TextColumn::make('payment_number')
                    ->label('Payment #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest / Walk-in'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (?PaymentMethod $state): ?string => $state?->label()
                        ?? (is_string($state) ? PaymentMethod::tryFrom($state)?->label() : null)
                        ?? (string) $state
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof PaymentTransactionStatus ? $state : PaymentTransactionStatus::tryFrom((string) $state)) {
                        PaymentTransactionStatus::Completed => 'success',
                        PaymentTransactionStatus::Pending => 'warning',
                        PaymentTransactionStatus::Failed => 'danger',
                        PaymentTransactionStatus::Refunded => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('transaction_id')
                    ->label('TrxID')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Pending'),

                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentTransactionStatus::class),

                SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('mark_completed')
                    ->label('Verify & Complete')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => $record->isPending() && auth()->user()?->can('payments.update'))
                    ->action(function (Payment $record): void {
                        $record->markAsCompleted(actor: auth()->user());
                        Notification::make()
                            ->success()
                            ->title('Payment Verified')
                            ->body("Payment {$record->payment_number} has been marked as completed.")
                            ->send();
                    }),

                Action::make('mark_failed')
                    ->label('Mark Failed')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => $record->isPending() && auth()->user()?->can('payments.update'))
                    ->form([
                        TextInput::make('reason')
                            ->label('Failure Reason')
                            ->placeholder('e.g. Invalid TrxID / Amount mismatch')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->markAsFailed(reason: $data['reason'], actor: auth()->user());
                        Notification::make()
                            ->warning()
                            ->title('Payment Failed')
                            ->body("Payment {$record->payment_number} marked as failed.")
                            ->send();
                    }),

                Action::make('refund')
                    ->label('Refund')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Process Refund')
                    ->modalDescription(fn (Payment $record): string => "Are you sure you want to refund payment {$record->payment_number} for ৳".number_format((float) $record->amount, 2).'?')
                    ->visible(fn (Payment $record): bool => $record->canBeRefunded() && auth()->user()?->can('payments.refund'))
                    ->form([
                        TextInput::make('reason')
                            ->label('Refund Reason')
                            ->placeholder('e.g. Return of items / Overpayment')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->refund(reason: $data['reason'], actor: auth()->user());
                        Notification::make()
                            ->success()
                            ->title('Payment Refunded')
                            ->body("Payment {$record->payment_number} has been successfully refunded.")
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
