<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCustomer extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CustomerResource::class;

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
}
