<?php

namespace App\Translation;

use Illuminate\Translation\Translator;

/**
 * Prevents UI label keys from resolving to whole language-file arrays.
 *
 * On case-insensitive filesystems (Windows), __('Payments') can load
 * lang/{locale}/payments.php as a group with a null item and return the
 * entire file array. Filament then crashes when a Stat/column label must be a string.
 */
class SafeTranslator extends Translator
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        if (! is_array($line)) {
            return $line;
        }

        if (! is_string($key) || str_contains($key, '.') || str_contains($key, '::')) {
            return $line;
        }

        return $this->makeReplacements($key, $replace);
    }
}
