<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = ReviewResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];
}
