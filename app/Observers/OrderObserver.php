<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderMailer;
use App\Services\OrderSmsNotifier;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $users = rescue(fn () => User::permission('orders.view')->get(), collect());

        if ($users && $users->isNotEmpty()) {
            Notification::make()
                ->title(__('New Order Received'))
                ->body(__('Order #:number has been placed for :amount.', ['number' => $order->order_number, 'amount' => number_format($order->grand_total, 2).' ৳']))
                ->success()
                ->icon('heroicon-o-shopping-bag')
                ->actions([
                    Action::make('view')
                        ->button()
                        ->url(route('filament.admin.resources.orders.view', $order)),
                ])
                ->sendToDatabase($users);
        }

        $this->notifyOrder($order, 'placed');
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $status = $order->status;

        $this->notifyOrder($order, $status instanceof OrderStatus ? $status->value : (string) $status);
    }

    private function notifyOrder(Order $order, string $step): void
    {
        $orderId = $order->getKey();

        $send = function () use ($orderId, $step): void {
            $fresh = Order::query()->with(['items', 'customer'])->find($orderId);

            if (! $fresh) {
                return;
            }

            try {
                app(OrderMailer::class)->send($fresh, $step);
            } catch (Throwable $exception) {
                Log::error('Order email failed for order '.$orderId.': '.$exception->getMessage());
            }

            try {
                app(OrderSmsNotifier::class)->send($fresh, $step);
            } catch (Throwable $exception) {
                Log::error('Order SMS failed for order '.$orderId.': '.$exception->getMessage());
            }
        };

        if (app()->runningUnitTests()) {
            $send();

            return;
        }

        DB::afterCommit(function () use ($send): void {
            defer($send);
        });
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
