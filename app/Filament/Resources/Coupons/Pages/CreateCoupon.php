<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CouponResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];
}
