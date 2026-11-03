<?php

namespace App\Livewire;

use App\Support\ClearApplicationCaches;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ClearCachesButton extends Component
{
    public function clear(ClearApplicationCaches $clearApplicationCaches): void
    {
        if (! auth()->user()?->can('settings.update')) {
            Notification::make()
                ->title(__('You do not have permission to update settings.'))
                ->danger()
                ->send();

            return;
        }

        $result = $clearApplicationCaches->handle();

        if (! $result['ok']) {
            Notification::make()
                ->title(__('Cache clear failed.'))
                ->body($result['failed']
                    ? __('Could not finish :command.', ['command' => $result['failed']])
                    : __('Something went wrong while clearing caches.'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('Cache cleared.'))
            ->body(__('The website and admin are refreshed. Check your latest changes now.'))
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.clear-caches-button', [
            'canClear' => (bool) auth()->user()?->can('settings.update'),
        ]);
    }
}
