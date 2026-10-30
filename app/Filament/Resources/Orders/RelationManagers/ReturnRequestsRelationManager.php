<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
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

        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('refund_amount')
                    ->label('Refund Amount')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn (ReturnRequest $record): ?string => $record->reason),

                TextColumn::make('admin_note')
                    ->label('Admin Note')
                    ->limit(35)
                    ->placeholder('—')
                    ->tooltip(fn (ReturnRequest $record): ?string => $record->admin_note),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Submit Return Request')
                    ->modalHeading('Submit Return Request for Order')
                    ->modalDescription('Record a customer return or refund claim for this order.')
                    ->modalIcon(Heroicon::OutlinedArrowUturnLeft)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(fn (): bool => auth()->user()?->can('returns.create') ?? false)
                    ->form([
                        Select::make('status')
                            ->options(ReturnRequestStatus::class)
                            ->default(ReturnRequestStatus::Pending)
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedCheckCircle),

                        TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->prefix(config('currency.symbol', '৳'))
                            ->numeric()
                            ->minValue(0)
                            ->default((float) $order->grand_total)
                            ->required()
                            ->helperText('Order grand total is ৳'.number_format((float) $order->grand_total, 2)),

                        Textarea::make('reason')
                            ->label('Reason for Return')
                            ->placeholder('Describe the defects, customer issue, or return reason...')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('admin_note')
                            ->label('Admin Note (Internal)')
                            ->placeholder('Internal review notes or remarks...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(function (array $data) use ($order): array {
                        $data['user_id'] = $order->user_id;

                        return $data;
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
                    ->modalHeading('Approve Return Request')
                    ->modalDescription('Approve this return request?')
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Pending && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        $record->status = ReturnRequestStatus::Approved;
                        $record->save();

                        Notification::make()
                            ->info()
                            ->title('Return Request Approved')
                            ->body('Return request has been approved.')
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
                            ->body('Return request has been rejected.')
                            ->send();
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Return Request')
                    ->modalDescription('Mark return request as completed? Verify items were received and refund processed.')
                    ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Approved && auth()->user()?->can('returns.update'))
                    ->action(function (ReturnRequest $record): void {
                        $record->status = ReturnRequestStatus::Completed;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Return Request Completed')
                            ->body('Return request is marked as completed.')
                            ->send();
                    }),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }
}
