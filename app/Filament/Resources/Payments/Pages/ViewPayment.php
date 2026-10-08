<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
