<?php

namespace App\Enums;

enum SettingType: string
{
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
        return match ($this) {
            self::String => 'String / Text',
            self::Boolean => 'Boolean (True/False)',
            self::Integer => 'Integer',
            self::Float => 'Float / Decimal',
            self::Json => 'JSON',
        };
    }
}
