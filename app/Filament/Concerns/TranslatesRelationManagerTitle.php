<?php

namespace App\Filament\Concerns;

use App\Support\UiText;
use Illuminate\Database\Eloquent\Model;

trait TranslatesRelationManagerTitle
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $title = static::$title ?? static::getRelationshipTitle();

        return UiText::line($title);
    }

    protected static function getModelLabel(): ?string
    {
        $label = property_exists(static::class, 'modelLabel') ? static::$modelLabel : null;

        return filled($label) ? UiText::line($label) : null;
    }

    protected static function getPluralModelLabel(): ?string
    {
        $label = property_exists(static::class, 'pluralModelLabel') ? static::$pluralModelLabel : null;
        $label ??= property_exists(static::class, 'title') ? static::$title : null;

        return filled($label) ? UiText::line($label) : null;
    }
}
