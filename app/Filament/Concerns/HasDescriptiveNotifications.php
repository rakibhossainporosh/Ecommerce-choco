<?php

namespace App\Filament\Concerns;

trait HasDescriptiveNotifications
{
    protected function getCreatedNotificationTitle(): ?string
    {
        $label = static::getResource()::getModelLabel();

        return ucfirst($label).' created successfully';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $label = static::getResource()::getModelLabel();

        return ucfirst($label).' updated successfully';
    }
}
