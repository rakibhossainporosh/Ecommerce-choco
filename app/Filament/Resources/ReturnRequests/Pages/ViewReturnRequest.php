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
        ];
    }
}
