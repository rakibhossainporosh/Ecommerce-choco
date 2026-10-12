<?php

namespace App\Filament\Resources\ReturnRequests\Tables;

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReturnRequestsTable
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
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->state(fn (ReturnRequest $record): string => $record->user?->name
                        ?? $record->order?->customer_name
                        ?? $record->order?->customer?->name
                        ?? 'Guest / Walk-in'
                    )
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('order', fn ($sub) => $sub->where('customer_name', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('refund_amount')
                    ->label('Refund Amount')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(35)
                    ->tooltip(fn (ReturnRequest $record): ?string => $record->reason)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(! app()->runningUnitTests())
            ->filters([
                SelectFilter::make('status')
                    ->options(ReturnRequestStatus::class),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Return Request')
                    ->modalDescription(fn (ReturnRequest $record): string => "Approve return request for Order #{$record->order?->order_number}?")
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Pending && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        $record->status = ReturnRequestStatus::Approved;
                        $record->save();

                        Notification::make()
                            ->info()
                            ->title('Return Request Approved')
                            ->body("Return request for Order #{$record->order?->order_number} has been approved.")
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Rejection Reason / Internal Note')
                            ->placeholder('Explain why this return request is rejected...')
                            ->required(),
                    ])
                    ->visible(fn (ReturnRequest $record): bool => in_array($record->status, [ReturnRequestStatus::Pending, ReturnRequestStatus::Approved], true) && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record, array $data): void {
                        $record->status = ReturnRequestStatus::Rejected;
                        $record->admin_note = $data['admin_note'];
                        $record->save();

                        Notification::make()
                            ->danger()
                            ->title('Return Request Rejected')
                            ->body("Return request for Order #{$record->order?->order_number} has been rejected.")
                            ->send();
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Return Request')
                    ->modalDescription(fn (ReturnRequest $record): string => "Mark return request for Order #{$record->order?->order_number} as completed? Verify items were received and refund processed.")
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Approved && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        $record->status = ReturnRequestStatus::Completed;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Return Request Completed')
                            ->body("Return request for Order #{$record->order?->order_number} is marked as completed.")
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}
