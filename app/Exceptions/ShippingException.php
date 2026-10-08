<?php

namespace App\Exceptions;

use App\Enums\ShipmentStatus;
use DomainException;

class ShippingException extends DomainException
{
    public static function invalidStatusTransition(ShipmentStatus|string $from, ShipmentStatus|string $to): self
    {
        $fromLabel = $from instanceof ShipmentStatus ? $from->label() : (string) $from;
        $toLabel = $to instanceof ShipmentStatus ? $to->label() : (string) $to;

        return new self("Cannot transition shipment from '{$fromLabel}' to '{$toLabel}'.");
    }

    public static function cannotShipCancelledOrder(string $orderNumber): self
    {
        return new self("Cannot create or dispatch a shipment for cancelled order #{$orderNumber}.");
    }

    public static function shipmentAlreadyDelivered(string $shipmentNumber): self
    {
        return new self("Shipment #{$shipmentNumber} has already been marked as delivered.");
    }

    public static function shipmentAlreadyTerminal(string $shipmentNumber, string $status): self
    {
        return new self("Shipment #{$shipmentNumber} is in terminal status '{$status}' and cannot be altered.");
    }

    public static function trackingCodeRequired(): self
    {
        return new self('A tracking number or consignment code is required to dispatch the shipment with external couriers.');
    }
}
