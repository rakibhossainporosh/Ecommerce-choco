<?php

namespace App\Filament\Resources\AbandonedCarts\Pages;

use App\Enums\AbandonedCartStatus;
use App\Filament\Resources\AbandonedCarts\AbandonedCartResource;
use App\Models\AbandonedCart;
use App\Support\AbandonedCartSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListAbandonedCarts extends ListRecords
{
    protected static string $resource = AbandonedCartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reminder')
                ->label('Reminder')
                ->icon(Heroicon::OutlinedBell)
                ->modalFooterActionsAlignment(Alignment::End)
                ->extraModalWindowAttributes(['class' => 'abandoned-cart-reminder-modal'])
                ->visible(fn (): bool => (bool) auth()->user()?->can('settings.update'))
                ->fillForm(fn (): array => AbandonedCartSettings::formState())
                ->form([
                    Toggle::make('abandoned_cart_enabled')
                        ->label('Send a reminder')
                        ->helperText(__('Leave this off to keep the list without sending anything.')),
                    TextInput::make('abandoned_cart_delay_minutes')
                        ->label('Wait before reminding')
                        ->numeric()
                        ->minValue(15)
                        ->maxValue(4320)
                        ->suffix(__('minutes'))
                        ->required(),
                    Toggle::make('abandoned_cart_email_enabled')
                        ->label('Send an email'),
                    Toggle::make('abandoned_cart_sms_enabled')
                        ->label('Send an SMS'),
                    TextInput::make('abandoned_cart_email_subject')
                        ->label('Email subject')
                        ->maxLength(160)
                        ->required(),
                    Textarea::make('abandoned_cart_email_body')
                        ->label('Email text')
                        ->helperText(__('You can use {name}, {shop}, {total}, and {url}.'))
                        ->rows(3)
                        ->maxLength(600)
                        ->required(),
                    Textarea::make('abandoned_cart_sms_body')
                        ->label('SMS text')
                        ->helperText(__('You can use {name}, {shop}, {total}, and {url}. One Bangla SMS is about 70 characters.'))
                        ->rows(3)
                        ->maxLength(600)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    AbandonedCartSettings::save($data);

                    Notification::make()
                        ->title(__('Reminder saved.'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Still open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AbandonedCartStatus::Open))
                ->badge(AbandonedCart::query()->where('status', AbandonedCartStatus::Open)->count())
                ->badgeColor('warning'),
            'reminded' => Tab::make('Reminded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AbandonedCartStatus::Reminded))
                ->badge(AbandonedCart::query()->where('status', AbandonedCartStatus::Reminded)->count()),
            'recovered' => Tab::make('Ordered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AbandonedCartStatus::Recovered))
                ->badge(AbandonedCart::query()->where('status', AbandonedCartStatus::Recovered)->count())
                ->badgeColor('success'),
            'all' => Tab::make('All')
                ->badge(AbandonedCart::query()->count()),
        ];
    }
}
