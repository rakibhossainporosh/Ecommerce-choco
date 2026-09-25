<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryException;
use Closure;
use Database\Factories\InventoryFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'product_variant_id',
    'quantity',
    'low_stock_threshold',
])]
class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 0,
        'low_stock_threshold' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Get the product variant that owns this inventory record.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the movement ledger entries for this inventory record.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_id');
    }

    /**
     * Alias for movements relation.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->movements();
    }

    /**
     * Determine if the inventory is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Determine if the inventory is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->low_stock_threshold;
    }

    /**
     * Determine if the inventory is in stock.
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Create and optionally initialize an inventory record for a product variant.
     *
     * @throws InventoryException
     * @throws DomainException
     */
    public static function createForVariant(
        ProductVariant $variant,
        int $openingQuantity = 0,
        int $lowStockThreshold = 0,
        ?string $reason = null,
        ?string $note = null,
        ?User $createdBy = null
    ): self {
        if (! $variant->exists) {
            throw new DomainException('Cannot create inventory for an unsaved product variant.');
        }

        return DB::transaction(function () use ($variant, $openingQuantity, $lowStockThreshold, $reason, $note, $createdBy): self {
            /** @var self $inventory */
            $inventory = static::create([
                'product_variant_id' => $variant->getKey(),
                'quantity' => 0,
                'low_stock_threshold' => max(0, $lowStockThreshold),
            ]);

            if ($openingQuantity > 0) {
                $inventory->openStock(
                    quantity: $openingQuantity,
                    reason: $reason ?? 'Initial opening stock',
                    note: $note,
                    createdBy: $createdBy
                );
            }

            return $inventory;
        });
    }

    /**
     * Initialize opening stock for the inventory.
     *
     * @throws InventoryException
     */
    public function openStock(
        int $quantity,
        ?string $reason = null,
        ?string $note = null,
        ?User $createdBy = null
    ): InventoryMovement {
        if ($quantity < 0) {
            throw InventoryException::openingStockInvalidQuantity();
        }

        return $this->mutateStock(
            type: InventoryMovementType::Opening,
            quantity: $quantity,
            reason: $reason,
            note: $note,
            createdBy: $createdBy,
            validateAndCalculate: function (self $lockedInventory) use ($quantity): int {
                if ($lockedInventory->quantity !== 0) {
                    throw InventoryException::openingStockAlreadyInitialized();
                }

                if ($lockedInventory->movements()->exists()) {
                    throw InventoryException::openingStockHasMovements();
                }

                return $quantity;
            }
        );
    }

    /**
     * Alias for openStock.
     *
     * @throws InventoryException
     */
    public function opening(
        int $quantity,
        ?string $reason = null,
        ?string $note = null,
        ?User $createdBy = null
    ): InventoryMovement {
        return $this->openStock($quantity, $reason, $note, $createdBy);
    }

    /**
     * Adjust stock inward (increase quantity).
     *
     * @throws InventoryException
     */
    public function adjustIn(
        int $quantity,
        ?string $reason = null,
        ?string $note = null,
        ?User $createdBy = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw InventoryException::invalidQuantity('Adjustment in quantity must be greater than zero.');
        }

        return $this->mutateStock(
            type: InventoryMovementType::AdjustmentIn,
            quantity: $quantity,
            reason: $reason,
            note: $note,
            createdBy: $createdBy,
            validateAndCalculate: function (self $lockedInventory) use ($quantity): int {
                return $lockedInventory->quantity + $quantity;
            }
        );
    }

    /**
     * Adjust stock outward (decrease quantity).
     *
     * @throws InventoryException
     */
    public function adjustOut(
        int $quantity,
        ?string $reason = null,
        ?string $note = null,
        ?User $createdBy = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw InventoryException::invalidQuantity('Adjustment out quantity must be greater than zero.');
        }

        return $this->mutateStock(
            type: InventoryMovementType::AdjustmentOut,
            quantity: $quantity,
            reason: $reason,
            note: $note,
            createdBy: $createdBy,
            validateAndCalculate: function (self $lockedInventory) use ($quantity): int {
                if ($lockedInventory->quantity < $quantity) {
                    throw InventoryException::insufficientStock($quantity, $lockedInventory->quantity);
                }

                return $lockedInventory->quantity - $quantity;
            }
        );
    }

    /**
     * Execute an atomic, locked stock mutation inside a database transaction.
     *
     * @param  Closure(self): int  $validateAndCalculate
     *
     * @throws InventoryException
     */
    protected function mutateStock(
        InventoryMovementType $type,
        int $quantity,
        ?string $reason,
        ?string $note,
        ?User $createdBy,
        Closure $validateAndCalculate
    ): InventoryMovement {
        if (! $this->exists) {
            throw InventoryException::unsavedInventory();
        }

        return DB::transaction(function () use ($type, $quantity, $reason, $note, $createdBy, $validateAndCalculate): InventoryMovement {
            /** @var self $lockedInventory */
            $lockedInventory = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $quantityBefore = $lockedInventory->quantity;
            $quantityAfter = $validateAndCalculate($lockedInventory);

            $lockedInventory->quantity = $quantityAfter;
            $lockedInventory->save();

            $cleanReason = $reason !== null ? (trim($reason) !== '' ? trim($reason) : null) : null;
            $cleanNote = $note !== null ? (trim($note) !== '' ? trim($note) : null) : null;

            /** @var InventoryMovement $movement */
            $movement = $lockedInventory->movements()->create([
                'product_variant_id' => $lockedInventory->product_variant_id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $cleanReason,
                'note' => $cleanNote,
                'created_by' => $createdBy?->id,
            ]);

            // Synchronize caller's in-memory model state
            $this->quantity = $lockedInventory->quantity;
            $this->updated_at = $lockedInventory->updated_at;
            $this->syncOriginal(['quantity', 'updated_at']);

            return $movement;
        });
    }
}
