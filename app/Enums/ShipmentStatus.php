<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Packed = 'packed';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case FailedDelivery = 'failed_delivery';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable status label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Dispatch',
            self::Packed => 'Packed / Ready to Ship',
            self::InTransit => 'In Transit / Shipped',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::FailedDelivery => 'Delivery Attempt Failed',
            self::Returned => 'Returned to Origin',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the color identifier for Filament badges.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Packed => 'info',
            self::InTransit => 'primary',
            self::OutForDelivery => 'warning',
            self::Delivered => 'success',
            self::FailedDelivery, self::Cancelled => 'danger',
            self::Returned => 'purple',
        };
    }

    /**
     * Get list of allowed target transitions.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Packed, self::InTransit, self::Cancelled],
            self::Packed => [self::InTransit, self::Cancelled],
            self::InTransit => [self::OutForDelivery, self::Delivered, self::FailedDelivery, self::Returned],
            self::OutForDelivery => [self::Delivered, self::FailedDelivery, self::Returned],
            self::FailedDelivery => [self::OutForDelivery, self::InTransit, self::Delivered, self::Returned],
            self::Delivered, self::Returned, self::Cancelled => [],
        };
    }

    /**
     * Check if transition to target status is permitted.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Check if the shipment status is final/terminal.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled], true);
    }
}
