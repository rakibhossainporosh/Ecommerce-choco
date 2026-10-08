<?php

namespace App\Policies;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Determine whether the user can view any shipments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shipping.view');
    }

    /**
     * Determine whether the user can view the shipment.
     */
    public function view(User $user, ?Shipment $shipment = null): bool
    {
        return $user->can('shipping.view');
    }

    /**
     * Determine whether the user can create shipments.
     */
    public function create(User $user): bool
    {
        return $user->can('shipping.create');
    }

    /**
     * Determine whether the user can update shipment details.
     */
    public function update(User $user, ?Shipment $shipment = null): bool
    {
        return $user->can('shipping.update');
    }

    /**
     * Determine whether the user can dispatch/ship the parcel.
     */
    public function ship(User $user, ?Shipment $shipment = null): bool
    {
        if (! $user->can('shipping.ship')) {
            return false;
        }

        return $shipment === null || $shipment->canBeShipped();
    }

    /**
     * Determine whether the user can mark the shipment as delivered.
     */
    public function deliver(User $user, ?Shipment $shipment = null): bool
    {
        if (! $user->can('shipping.deliver')) {
            return false;
        }

        return $shipment === null || $shipment->canBeDelivered();
    }

    /**
     * Determine whether the user can delete a shipment.
     * Only pending or cancelled consignments can be deleted; active transit and delivered shipments are immutable.
     */
    public function delete(User $user, ?Shipment $shipment = null): bool
    {
        if (! $user->can('shipping.update')) {
            return false;
        }

        if ($shipment === null) {
            return true;
        }

        return in_array($shipment->status, [ShipmentStatus::Pending, ShipmentStatus::Cancelled], true);
    }

    /**
     * Bulk deletion of shipments is prohibited.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
