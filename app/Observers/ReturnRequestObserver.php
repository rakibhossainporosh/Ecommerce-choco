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
        $users = User::permission('returns.view')->get();

        Notification::make()
            ->title('New Return Request')
            ->body("A return request has been submitted for Order #{$returnRequest->order->order_number}.")
            ->warning()
            ->icon('heroicon-o-arrow-path')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(route('filament.admin.resources.return-requests.view', $returnRequest)),
            ])
            ->sendToDatabase($users);
    }

    /**
     * Handle the ReturnRequest "updated" event.
     */
    public function updated(ReturnRequest $returnRequest): void
    {
        //
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
