<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Enums\SettingType;
use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['type'])) {
            $typeValue = $data['type'] instanceof SettingType ? $data['type']->value : $data['type'];
            if ($typeValue === SettingType::Boolean->value) {
                $data['value_boolean'] = $data['value'] === 'true';
            } elseif ($typeValue === SettingType::Json->value) {
                $data['value_json'] = $data['value'];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $type = $data['type'] ?? $this->record?->type ?? SettingType::String;
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
