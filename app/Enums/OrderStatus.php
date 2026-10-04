<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';

    /**
     * Get all values of the enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the human-readable label for the order status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Get the list of allowed target statuses from the current status.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered => [],
            self::Cancelled => [],
            self::Returned => [],
            self::Refunded => [],
        };
    }

    /**
     * Check if transition to the target status is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Check if the order can be cancelled from this status.
     */
    public function canBeCancelled(): bool
    {
        return in_array(self::Cancelled, $this->allowedTransitions(), true);
    }

    /**
     * Determine if this status is terminal.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Cancelled, self::Returned, self::Refunded => true,
            default => false,
        };
    }
}
