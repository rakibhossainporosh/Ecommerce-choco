<?php

namespace App\Filament\Concerns;

use App\Support\UiText;
use Illuminate\Support\Str;
use UnitEnum;

trait TranslatesResourceNavigation
{
    public static function getNavigationLabel(): string
    {
        if (filled(static::$navigationLabel)) {
            return UiText::line(static::$navigationLabel);
        }

        return static::getTitleCasePluralModelLabel();
    }

    public static function getBreadcrumb(): string
    {
        if (filled(static::$breadcrumb)) {
            return UiText::line(static::$breadcrumb);
        }

        return static::getPluralModelLabel();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = static::$navigationGroup;

        return is_string($group) ? UiText::line($group) : $group;
    }

    public static function getModelLabel(): string
    {
        if (filled(static::$modelLabel)) {
            return UiText::line(static::$modelLabel);
        }

        return UiText::line(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        if (filled(static::$pluralModelLabel)) {
            return UiText::line(static::$pluralModelLabel);
        }

        if (filled(static::$navigationLabel)) {
            return UiText::line(static::$navigationLabel);
        }

        $english = filled(static::$modelLabel)
            ? static::$modelLabel
            : parent::getModelLabel();

        return UiText::line(Str::plural($english));
    }
}
