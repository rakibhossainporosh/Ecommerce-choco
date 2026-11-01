<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TranslatesRelationManagerTitle
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $title = static::$title ?? static::getRelationshipTitle();

        return __($title);
    }
}
