<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Enums\SettingType;
use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = $data['type'] ?? SettingType::String;
        $typeValue = $type instanceof SettingType ? $type->value : $type;

        if ($typeValue === SettingType::Boolean->value && isset($data['value_boolean'])) {
            $data['value'] = filter_var($data['value_boolean'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        } elseif ($typeValue === SettingType::Json->value && isset($data['value_json'])) {
            $data['value'] = $data['value_json'];
        }

        unset($data['value_boolean'], $data['value_json']);

        return $data;
    }
}
