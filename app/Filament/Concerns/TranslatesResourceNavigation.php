<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;
use UnitEnum;

trait TranslatesResourceNavigation
{
    public static function getNavigationLabel(): string
    {
        if (filled(static::$navigationLabel)) {
            return __(static::$navigationLabel);
        }

        return static::getTitleCasePluralModelLabel();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = static::$navigationGroup;

        return is_string($group) ? __($group) : $group;
    }

    public static function getModelLabel(): string
    {
        if (filled(static::$modelLabel)) {
            return __(static::$modelLabel);
        }

        return __(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        if (filled(static::$pluralModelLabel)) {
            return __(static::$pluralModelLabel);
        }

        if (filled(static::$navigationLabel)) {
            return __(static::$navigationLabel);
        }

        $english = filled(static::$modelLabel)
            ? static::$modelLabel
            : parent::getModelLabel();

        return __(Str::plural($english));
    }
}
