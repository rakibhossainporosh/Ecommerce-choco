<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use DomainException;

class InvalidOrderTransitionException extends DomainException
{
    public function __construct(
        string $message,
        public readonly ?OrderStatus $fromStatus = null,
        public readonly ?OrderStatus $toStatus = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Exception when transition between statuses is not permitted.
     */
    public static function cannotTransition(OrderStatus $from, OrderStatus $to): self
    {
        return new self(
            message: "Cannot transition order from {$from->value} to {$to->value}.",
            fromStatus: $from,
            toStatus: $to,
        );
    }

    /**
     * Exception when an order cannot be cancelled from its current status.
     */
    public static function cannotCancel(OrderStatus $from): self
    {
        return new self(
            message: "Cannot cancel order in {$from->value} status.",
            fromStatus: $from,
            toStatus: OrderStatus::Cancelled,
        );
    }

    /**
     * Exception when cancellation reason is missing or empty.
     */
    public static function missingCancellationReason(?OrderStatus $from = null): self
    {
        return new self(
            message: 'Cancellation reason cannot be empty.',
            fromStatus: $from,
            toStatus: OrderStatus::Cancelled,
        );
    }

    /**
     * Exception when attempting a transition on an unsaved order.
     */
    public static function unsavedOrder(): self
    {
        return new self('Cannot transition an unsaved order.');
    }
}
