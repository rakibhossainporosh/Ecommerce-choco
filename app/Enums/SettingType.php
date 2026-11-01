<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SettingType: string implements HasLabel
{
    use HasTranslatedLabel;

    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Json = 'json';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('enums.setting_type.'.$this->value);
    }
}
