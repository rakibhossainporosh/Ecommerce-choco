<?php

namespace App\Filament\Concerns;

use App\Support\UiText;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

trait TranslatesPageNavigation
{
    public static function getNavigationLabel(): string
    {
        if (filled(static::$navigationLabel)) {
            return UiText::line(static::$navigationLabel);
        }

        return UiText::line(parent::getNavigationLabel());
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = static::$navigationGroup;

        return is_string($group) ? UiText::line($group) : $group;
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return UiText::line(static::$title);
        }

        $title = parent::getTitle();

        return is_string($title) ? UiText::line($title) : $title;
    }
}
