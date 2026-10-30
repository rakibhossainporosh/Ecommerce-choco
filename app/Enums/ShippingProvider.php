<?php

namespace App\Enums;

enum ShippingProvider: string
{
    case Steadfast = 'steadfast';
    case Pathao = 'pathao';
    case RedX = 'redx';
    case Paperfly = 'paperfly';
    case ECourier = 'ecourier';
    case Sundarban = 'sundarban';
    case InHouse = 'in_house';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Steadfast => 'Steadfast Courier',
            self::Pathao => 'Pathao Courier',
            self::RedX => 'RedX Delivery',
            self::Paperfly => 'Paperfly',
            self::ECourier => 'eCourier',
            self::Sundarban => 'Sundarban Courier',
            self::InHouse => 'In-House / Own Fleet',
        };
    }

    /**
     * Generate a public tracking URL for the consignment if available.
     */
    public function trackingUrl(?string $trackingCode): ?string
    {
        if (blank($trackingCode)) {
            return null;
        }

        $code = urlencode(trim($trackingCode));

        return match ($this) {
            self::Steadfast => "https://steadfast.com.bd/t/{$code}",
            self::Pathao => "https://merchant.pathao.com/tracking?consignment_id={$code}",
            self::RedX => "https://redx.com.bd/track-order?trackingId={$code}",
            self::Paperfly => "https://paperfly.com.bd/tracking.php?tracking_id={$code}",
            self::ECourier => "https://ecourier.com.bd/track?tracking={$code}",
            default => null,
        };
    }
}
