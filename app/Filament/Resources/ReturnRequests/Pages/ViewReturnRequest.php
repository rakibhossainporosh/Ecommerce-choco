<?php

namespace App\Filament\Resources\ReturnRequests\Pages;

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\ReturnRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('Approve Return Request'))
                ->modalDescription(fn (ReturnRequest $record): string => __('Approve return request for Order #:number?', ['number' => $record->order?->order_number]))
                ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Pending && auth()->user()?->can('returns.update'))
                ->action(function (ReturnRequest $record): void {
                    $record->status = ReturnRequestStatus::Approved;
                    $record->save();

                    Notification::make()
                        ->info()
                        ->title(__('Return Request Approved'))
                        ->body(__('Return request for Order #:number has been approved.', ['number' => $record->order?->order_number]))
                        ->send();
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
                ->visible(fn (ReturnRequest $record): bool => in_array($record->status, [ReturnRequestStatus::Pending, ReturnRequestStatus::Approved], true) && auth()->user()?->can('returns.update'))
                ->action(function (ReturnRequest $record, array $data): void {
                    $record->status = ReturnRequestStatus::Rejected;
                    $record->admin_note = $data['admin_note'];
                    $record->save();

                    Notification::make()
                        ->danger()
                        ->title(__('Return Request Rejected'))
                        ->body(__('Return request for Order #:number has been rejected.', ['number' => $record->order?->order_number]))
                        ->send();
                }),

            Action::make('complete')
                ->label('Complete')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('Complete Return Request'))
                ->modalDescription(fn (ReturnRequest $record): string => __('Mark return request for Order #:number as completed? Verify items were received and refund processed.', ['number' => $record->order?->order_number]))
                ->visible(fn (ReturnRequest $record): bool => $record->status === ReturnRequestStatus::Approved && auth()->user()?->can('returns.update'))
                ->action(function (ReturnRequest $record): void {
                    $record->status = ReturnRequestStatus::Completed;
                    $record->save();

                    Notification::make()
                        ->success()
                        ->title(__('Return Request Completed'))
                        ->body(__('Return request for Order #:number is marked as completed.', ['number' => $record->order?->order_number]))
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}
