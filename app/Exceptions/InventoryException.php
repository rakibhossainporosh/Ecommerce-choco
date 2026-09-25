<?php

namespace App\Exceptions;

use DomainException;

class InventoryException extends DomainException
{
    /**
     * Exception for invalid adjustment quantity (must be > 0).
     */
    public static function invalidQuantity(string $message = 'Adjustment quantity must be greater than zero.'): self
    {
        return new self($message);
    }

    /**
     * Exception for insufficient stock during adjustment out.
     */
    public static function insufficientStock(int $requested, int $available): self
    {
        return new self("Insufficient stock. Cannot adjust out {$requested} units because only {$available} units are available.");
    }

    /**
     * Exception when opening stock is attempted on an inventory with existing non-zero quantity.
     */
    public static function openingStockAlreadyInitialized(): self
    {
        return new self('Opening stock has already been initialized for this inventory.');
    }

    /**
     * Exception when opening stock is attempted on an inventory with existing movement history.
     */
    public static function openingStockHasMovements(): self
    {
        return new self('Opening stock cannot be initialized after previous adjustment history.');
    }

    /**
     * Exception for negative opening stock quantity.
     */
    public static function openingStockInvalidQuantity(): self
    {
        return new self('Opening stock quantity must be greater than or equal to zero.');
    }

    /**
     * Exception when attempting mutation on an unsaved inventory record.
     */
    public static function unsavedInventory(): self
    {
        return new self('Cannot perform inventory mutations on an unsaved inventory record.');
    }
}
