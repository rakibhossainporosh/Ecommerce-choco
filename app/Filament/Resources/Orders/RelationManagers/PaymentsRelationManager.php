<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Filament\Concerns\TranslatesRelationManagerTitle;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentMethodPresenter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    use TranslatesRelationManagerTitle;

    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payment History';

    public function table(Table $table): Table
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

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => PaymentMethodPresenter::label($state))
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
                    ->copyable()
                    ->placeholder(__('—')),

                TextColumn::make('account_number')
                    ->label('Account / Phone')
                    ->placeholder(__('—')),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y H:i')
                    ->placeholder(__('Pending'))
                    ->sortable(),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->placeholder(__('System'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Record Payment')
                    ->modalHeading(__('Record Payment for this Order'))
                    ->modalDescription(__('Enter transaction details to record a payment against this order.'))
                    ->modalIcon(Heroicon::OutlinedBanknotes)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn (): bool => auth()->user()?->can('payments.create') ?? false)
                    ->form([
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
                            ->default(function (): float {
                                /** @var Order $order */
                                $order = $this->getOwnerRecord();

                                return (float) ($order->due_amount > 0 ? $order->due_amount : $order->grand_total);
                            }),

                        TextInput::make('transaction_id')
                            ->label('Transaction ID / TrxID')
                            ->placeholder(__('e.g. 9J2834K1'))
                            ->maxLength(100)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedIdentification),

                        TextInput::make('account_number')
                            ->label('Sender Account / Phone Number')
                            ->placeholder(__('e.g. 017XXXXXXXX'))
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
                            ->label('Notes')
                            ->placeholder(__('e.g. Received via bKash Merchant account'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Order $order */
                        $order = $this->getOwnerRecord();

                        $data['customer_id'] = $order->customer_id ?? Customer::where('user_id', $order->user_id)->value('id');
                        $data['currency'] = $order->currency ?? 'BDT';
                        if (auth()->check()) {
                            $data['recorded_by'] = auth()->id();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Payment $record): string => PaymentResource::getUrl('view', ['record' => $record])),

                Action::make('mark_completed')
                    ->label('Verify')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => $record->isPending() && auth()->user()?->can('payments.update'))
                    ->action(function (Payment $record): void {
                        $record->markAsCompleted(actor: auth()->user());
                        Notification::make()
                            ->success()
                            ->title(__('Payment Verified'))
                            ->body(__('Payment :number has been marked as completed.', ['number' => $record->payment_number]))
                            ->send();
                    }),

                Action::make('refund')
                    ->label('Refund')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('Process Refund'))
                    ->modalDescription(fn (Payment $record): string => __('Are you sure you want to refund payment :number for ৳:amount?', [
                        'number' => $record->payment_number,
                        'amount' => number_format((float) $record->amount, 2),
                    ]))
                    ->visible(fn (Payment $record): bool => $record->canBeRefunded() && auth()->user()?->can('payments.refund'))
                    ->form([
                        TextInput::make('reason')
                            ->label('Refund Reason')
                            ->placeholder(__('e.g. Return of items / Overpayment'))
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->refund(reason: $data['reason'], actor: auth()->user());
                        Notification::make()
                            ->success()
                            ->title(__('Payment Refunded'))
                            ->body(__('Payment :number has been successfully refunded.', ['number' => $record->payment_number]))
                            ->send();
                    }),
            ]);
    }

    public function canCreate(): bool
    {
        return auth()->user()?->can('payments.create') ?? false;
    }

    public function canEdit(Model $record): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return false;
    }
}
