<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $users = User::permission('orders.view')->get();

        Notification::make()
            ->title('New Order Received')
            ->body("Order #{$order->order_number} has been placed for ".number_format($order->grand_total, 2).' ৳.')
            ->success()
            ->icon('heroicon-o-shopping-bag')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(route('filament.admin.resources.orders.view', $order)),
            ])
            ->sendToDatabase($users);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
