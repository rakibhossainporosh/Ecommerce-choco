<?php

namespace App\Observers;

use App\Models\Customer;
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
            $rma = $returnRequest->rma_number ?? '#'.$returnRequest->id;

            Notification::make()
                ->title(__('New Return Request'))
                ->body(__('Return :rma submitted for Order #:number.', ['rma' => $rma, 'number' => $orderNumber]))
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
        if (! $returnRequest->wasChanged('status')) {
            return;
        }

        $statusLabel = $returnRequest->status->label();
        $orderNumber = $returnRequest->order?->order_number ?? $returnRequest->order_id;
        $rma = $returnRequest->rma_number ?? '#'.$returnRequest->id;
        $body = __('Return :rma for Order #:number is now :status.', [
            'rma' => $rma,
            'number' => $orderNumber,
            'status' => $statusLabel,
        ]);

        $notifiables = collect();

        if ($returnRequest->customer instanceof Customer) {
            $notifiables->push($returnRequest->customer);
        } elseif ($returnRequest->customer_id) {
            $customer = Customer::query()->find($returnRequest->customer_id);
            if ($customer) {
                $notifiables->push($customer);
            }
        }

        if ($returnRequest->user instanceof User) {
            $notifiables->push($returnRequest->user);
        }

        $notifiables = $notifiables->unique(fn ($model) => $model::class.'#'.$model->getKey());

        if ($notifiables->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('Return request update'))
            ->body($body)
            ->icon($returnRequest->status->getIcon())
            ->color($returnRequest->status->getColor())
            ->sendToDatabase($notifiables);
    }
}
