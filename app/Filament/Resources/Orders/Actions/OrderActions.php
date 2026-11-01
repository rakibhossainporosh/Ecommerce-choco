<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InventoryException;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class OrderActions
{
    /**
     * Create the action to confirm a pending order.
     */
    public static function makeConfirmAction(): Action
    {
        return Action::make('confirm')
            ->label('Confirm')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Confirm Order #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Are you sure you want to confirm this order?'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('confirm', $record) ?? false)
                && $record->canTransitionTo(OrderStatus::Confirmed)
            )
            ->authorize('confirm')
            ->action(function (Order $record): void {
                if (! auth()->user()?->can('confirm', $record)) {
                    abort(403);
                }

                try {
                    $record->confirm();

                    Notification::make()
                        ->title(__('Order Confirmed'))
                        ->body(__('Order #:number has been confirmed.', ['number' => $record->order_number]))
                        ->success()
                        ->send();
                } catch (InvalidOrderTransitionException|InventoryException $e) {
                    Notification::make()
                        ->title(__('Transition Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the action to start processing a confirmed order.
     */
    public static function makeProcessAction(): Action
    {
        return Action::make('process')
            ->label('Process')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Start Processing Order #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Are you sure you want to mark this order as processing?'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('process', $record) ?? false)
                && $record->canTransitionTo(OrderStatus::Processing)
            )
            ->authorize('process')
            ->action(function (Order $record): void {
                if (! auth()->user()?->can('process', $record)) {
                    abort(403);
                }

                try {
                    $record->startProcessing();

                    Notification::make()
                        ->title(__('Order Processing Started'))
                        ->body(__('Order #:number is now being processed.', ['number' => $record->order_number]))
                        ->success()
                        ->send();
                } catch (InvalidOrderTransitionException $e) {
                    Notification::make()
                        ->title(__('Transition Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the action to mark an order as shipped.
     */
    public static function makeShipAction(): Action
    {
        return Action::make('ship')
            ->label('Ship')
            ->icon(Heroicon::OutlinedTruck)
            ->color('purple')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Ship Order #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Are you sure you want to mark this order as shipped?'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('ship', $record) ?? false)
                && $record->canTransitionTo(OrderStatus::Shipped)
            )
            ->authorize('ship')
            ->action(function (Order $record): void {
                if (! auth()->user()?->can('ship', $record)) {
                    abort(403);
                }

                try {
                    $record->ship();

                    Notification::make()
                        ->title(__('Order Shipped'))
                        ->body(__('Order #:number has been marked as shipped.', ['number' => $record->order_number]))
                        ->success()
                        ->send();
                } catch (InvalidOrderTransitionException $e) {
                    Notification::make()
                        ->title(__('Transition Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the action to mark an order as delivered.
     */
    public static function makeDeliverAction(): Action
    {
        return Action::make('deliver')
            ->label('Deliver')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Deliver Order #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Are you sure you want to mark this order as delivered?'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('deliver', $record) ?? false)
                && $record->canTransitionTo(OrderStatus::Delivered)
            )
            ->authorize('deliver')
            ->action(function (Order $record): void {
                if (! auth()->user()?->can('deliver', $record)) {
                    abort(403);
                }

                try {
                    $record->deliver();

                    Notification::make()
                        ->title(__('Order Delivered'))
                        ->body(__('Order #:number has been marked as delivered.', ['number' => $record->order_number]))
                        ->success()
                        ->send();
                } catch (InvalidOrderTransitionException $e) {
                    Notification::make()
                        ->title(__('Transition Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the action to cancel an order with a mandatory reason.
     */
    public static function makeCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Cancel Order #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Please provide a reason for cancelling this order.'))
            ->modalSubmitActionLabel('Confirm Cancellation')
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('Cancellation Reason')
                    ->placeholder(__('e.g. Customer requested cancellation / Out of stock'))
                    ->required()
                    ->rows(3)
                    ->maxLength(255)
                    ->rules(['required', 'string', 'min:1']),
            ])
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('cancel', $record) ?? false)
                && $record->canBeCancelled()
            )
            ->authorize('cancel')
            ->action(function (Order $record, array $data): void {
                if (! auth()->user()?->can('cancel', $record)) {
                    abort(403);
                }

                try {
                    $reason = trim((string) ($data['cancellation_reason'] ?? ''));

                    if ($reason === '') {
                        Notification::make()
                            ->title(__('Validation Error'))
                            ->body(__('Cancellation reason cannot be empty.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    /** @var User|null $user */
                    $user = auth()->user();
                    $record->cancel($reason, $user);

                    Notification::make()
                        ->title(__('Order Cancelled'))
                        ->body(__('Order #:number has been cancelled.', ['number' => $record->order_number]))
                        ->success()
                        ->send();
                } catch (InvalidOrderTransitionException $e) {
                    Notification::make()
                        ->title(__('Cancellation Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
