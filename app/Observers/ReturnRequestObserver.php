<?php

namespace App\Observers;

use App\Models\ReturnRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ReturnRequestObserver
{
    /**
     * Handle the ReturnRequest "created" event.
     */
    public function created(ReturnRequest $returnRequest): void
    {
        $users = rescue(fn () => User::permission('returns.view')->get(), collect());

        if ($users && $users->isNotEmpty()) {
            $orderNumber = $returnRequest->order?->order_number ?? $returnRequest->order_id;

            Notification::make()
                ->title(__('New Return Request'))
                ->body(__('A return request has been submitted for Order #:number.', ['number' => $orderNumber]))
                ->warning()
                ->icon('heroicon-o-arrow-path')
                ->actions([
                    Action::make('view')
                        ->button()
                        ->url(route('filament.admin.resources.return-requests.view', $returnRequest)),
                ])
                ->sendToDatabase($users);
        }
    }

    /**
     * Handle the ReturnRequest "updated" event.
     */
    public function updated(ReturnRequest $returnRequest): void
    {
        if ($returnRequest->wasChanged('status') && $returnRequest->user) {
            $statusLabel = $returnRequest->status->label();
            $orderNumber = $returnRequest->order?->order_number ?? $returnRequest->order_id;

            Notification::make()
                ->title(__('Return request update'))
                ->body(__('Your return request for Order #:number has been updated to :status.', ['number' => $orderNumber, 'status' => $statusLabel]))
                ->icon($returnRequest->status->getIcon())
                ->color($returnRequest->status->getColor())
                ->sendToDatabase($returnRequest->user);
        }
    }

    /**
     * Handle the ReturnRequest "deleted" event.
     */
    public function deleted(ReturnRequest $returnRequest): void
    {
        //
    }

    /**
     * Handle the ReturnRequest "restored" event.
     */
    public function restored(ReturnRequest $returnRequest): void
    {
        //
    }

    /**
     * Handle the ReturnRequest "force deleted" event.
     */
    public function forceDeleted(ReturnRequest $returnRequest): void
    {
        //
    }
}
