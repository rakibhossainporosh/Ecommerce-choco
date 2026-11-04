<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Js;

/**
 * Filament's default Cancel uses window.history.back() when document.referrer
 * exists. That remounts the panel layout and makes the sidebar jump/flash on
 * create/edit pages (especially heavy ones like Product). Always SPA-navigate
 * (or hard-navigate) to the resource index instead.
 */
trait UsesStableCancelNavigation
{
    protected function getCancelFormAction(): Action
    {
        $url = $this->getResourceUrl();

        $label = $this instanceof CreateRecord
            ? __('filament-panels::resources/pages/create-record.form.actions.cancel.label')
            : __('filament-panels::resources/pages/edit-record.form.actions.cancel.label');

        return Action::make('cancel')
            ->label($label)
            ->alpineClickHandler(
                FilamentView::hasSpaMode($url)
                    ? 'Livewire.navigate('.Js::from($url).')'
                    : 'window.location.href = '.Js::from($url),
            )
            ->color('gray');
    }
}
