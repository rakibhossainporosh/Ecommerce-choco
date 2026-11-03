<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CouponResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
