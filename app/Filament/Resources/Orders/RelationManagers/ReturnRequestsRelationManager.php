<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\ReturnRequestStatus;
use App\Filament\Concerns\TranslatesRelationManagerTitle;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Support\ReturnItemValidator;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
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

class ReturnRequestsRelationManager extends RelationManager
{
    use TranslatesRelationManagerTitle;

    protected static string $relationship = 'returnRequests';

    protected static ?string $title = 'Return & Refund Requests';

    public function table(Table $table): Table
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        /** @var Order $order */
        $order = $this->getOwnerRecord();
        $order->loadMissing('items');

        $remaining = ReturnItemValidator::remainingQuantities($order);

        $itemOptions = $order->items
            ->filter(fn ($item): bool => (int) ($remaining[$item->id] ?? 0) > 0)
            ->mapWithKeys(fn ($item): array => [
                $item->id => ($item->variant_name ?: $item->product_name)
                    .' × remaining '.($remaining[$item->id] ?? 0),
            ])
            ->all();

        return $table
            ->columns([
                TextColumn::make('rma_number')
                    ->label('RMA #')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->placeholder(__('—')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('refund_amount')
                    ->label('Refund Amount')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn (ReturnRequest $record): ?string => $record->reason)
                    ->alignCenter(),

                TextColumn::make('admin_note')
                    ->label('Admin Note')
                    ->limit(35)
                    ->placeholder(__('—'))
                    ->tooltip(fn (ReturnRequest $record): ?string => $record->admin_note)
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Submit Return Request')
                    ->modalHeading(__('Submit Return Request for Order'))
                    ->modalDescription(__('Record a customer return or refund claim for this order.'))
                    ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn (): bool => auth()->user()?->can('returns.create') ?? false)
                    ->form([
                        TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->prefix(config('currency.symbol', '৳'))
                            ->numeric()
                            ->minValue(0)
                            ->default((float) $order->grand_total)
                            ->required()
                            ->helperText(__('Synced from lines. Empty lines auto-return all remaining units (default refund :amount).', [
                                'amount' => '৳'.number_format((float) $order->grand_total, 2),
                            ])),

                        Textarea::make('reason')
                            ->label('Reason for Return')
                            ->placeholder(__('Describe the defects, customer issue, or return reason...'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('admin_note')
                            ->label('Admin Note (Internal)')
                            ->placeholder(__('Internal review notes or remarks...'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Repeater::make('line_items')
                            ->label('Return Line Items')
                            ->helperText(__('Leave empty to auto-return every remaining unit.'))
                            ->default([])
                            ->schema([
                                Select::make('order_item_id')
                                    ->label('Order Item')
                                    ->options($itemOptions)
                                    ->required()
                                    ->searchable(),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('refund_amount')
                                    ->label('Line Refund')
                                    ->numeric()
                                    ->prefix(config('currency.symbol', '৳'))
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->addActionLabel(__('Add return item')),
                    ])
                    ->mutateFormDataUsing(function (array $data) use ($order): array {
                        $data['user_id'] = $order->user_id;
                        $data['customer_id'] = $order->customer_id;
                        $data['status'] = ReturnRequestStatus::Pending;

                        return $data;
                    })
                    ->using(function (array $data, CreateAction $action) use ($order): ReturnRequest {
                        $lineItems = $data['line_items'] ?? [];
                        unset($data['line_items']);

                        try {
                            return ReturnRequest::createPendingForOrder(
                                $order,
                                $data,
                                is_array($lineItems) ? $lineItems : [],
                            );
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(__('Return Request Failed'))
                                ->body($exception->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (ReturnRequest $record): string => ReturnRequestResource::getUrl('view', ['record' => $record])),

                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Return Request'))
                    ->modalDescription(__('Approve this return request?'))
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Pending && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        try {
                            $record->approve(auth()->user());

                            Notification::make()
                                ->info()
                                ->title(__('Return Request Approved'))
                                ->body(__('Return request has been approved.'))
                                ->send();
                        } catch (DomainException|\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Approve Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('markReceived')
                    ->label('Mark Received')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(__('Mark Return Received'))
                    ->modalDescription(__('Confirm return goods have been received?'))
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Approved && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        try {
                            $record->markReceived(auth()->user());

                            Notification::make()
                                ->success()
                                ->title(__('Return Marked Received'))
                                ->body(__('Return goods marked as received.'))
                                ->send();
                        } catch (DomainException|\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Receive Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Rejection Reason / Internal Note')
                            ->placeholder(__('Explain why this return request is rejected...'))
                            ->required(),
                    ])
                    ->visible(fn (ReturnRequest $record): bool => in_array($record->status, [
                        ReturnRequestStatus::Pending,
                        ReturnRequestStatus::Approved,
                        ReturnRequestStatus::Received,
                    ], true) && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record, array $data): void {
                        try {
                            $record->reject($data['admin_note'], auth()->user());

                            Notification::make()
                                ->danger()
                                ->title(__('Return Request Rejected'))
                                ->body(__('Return request has been rejected.'))
                                ->send();
                        } catch (DomainException|\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Reject Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('Complete Return Request'))
                    ->modalDescription(__('Mark return complete? This restocks inventory and refunds up to the approved amount.'))
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Received && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        try {
                            $record->complete(auth()->user());

                            Notification::make()
                                ->success()
                                ->title(__('Return Request Completed'))
                                ->body(__('Return completed with stock restore and refund handling.'))
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Complete Failed'))
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Pending && auth()->user()?->can('returns.delete')),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }
}
