<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class UiText
{
    /**
     * Translate a short UI label.
     *
     * A label that matches a PHP language file name, such as "Catalog",
     * would otherwise return that file's array on a case-insensitive filesystem.
     */
    public static function line(string $key): string
    {
        $translated = Lang::get($key);

        return is_string($translated) ? $translated : $key;
    }
}
