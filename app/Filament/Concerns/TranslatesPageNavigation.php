<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

trait TranslatesPageNavigation
{
    public static function getNavigationLabel(): string
    {
        if (filled(static::$navigationLabel)) {
            return __(static::$navigationLabel);
        }

        return __(parent::getNavigationLabel());
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = static::$navigationGroup;

        return is_string($group) ? __($group) : $group;
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return __(static::$title);
        }

        $title = parent::getTitle();

        return is_string($title) ? __($title) : $title;
    }
}
