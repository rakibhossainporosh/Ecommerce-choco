<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Couriers\CourierResource;
use App\Models\Courier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCourier extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CourierResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['credentials'] = [
            'api_key' => null,
            'secret_key' => null,
        ];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $incoming = $data['credentials'] ?? [];

        if (! is_array($incoming)) {
            unset($data['credentials']);

            return $data;
        }

        $incoming = array_filter(
            $incoming,
            fn (mixed $value): bool => filled($value)
        );

        if ($incoming === []) {
            unset($data['credentials']);

            return $data;
        }

        /** @var Courier $record */
        $record = $this->getRecord();

        $data['credentials'] = array_merge($record->credentials ?? [], $incoming);

        return $data;
    }
}
