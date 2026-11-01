<?php

namespace App\Filament\Resources\AbandonedCarts\Pages;

use App\Enums\AbandonedCartStatus;
use App\Filament\Resources\AbandonedCarts\AbandonedCartResource;
use App\Models\AbandonedCart;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewAbandonedCart extends ViewRecord
{
    protected static string $resource = AbandonedCartResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();

        if (! $record instanceof AbandonedCart) {
            return __('Abandoned cart');
        }

        return $record->name ?: ($record->phone ?: __('Abandoned cart'));
    }

    public function getBreadcrumb(): string
    {
        return $this->getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Close')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord() instanceof AbandonedCart
                    && in_array($this->getRecord()->status, [AbandonedCartStatus::Open, AbandonedCartStatus::Reminded], true)
                    && (bool) auth()->user()?->can('orders.update'))
                ->action(function (): void {
                    $record = $this->getRecord();

                    if (! $record instanceof AbandonedCart) {
                        return;
                    }

                    $record->status = AbandonedCartStatus::Closed;
                    $record->save();

                    Notification::make()
                        ->title(__('Cart closed.'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
