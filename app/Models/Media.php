<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable([
    'mediable_type',
    'mediable_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'width',
    'height',
    'alt_text',
    'sort_order',
    'is_primary',
])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * Supported owner model classes for polymorphic media.
     *
     * @var array<int, class-string<Model>>
     */
    public const SUPPORTED_OWNERS = [
        Product::class,
        ProductVariant::class,
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_primary' => false,
    ];

    /**
     * Determine if a given model instance is a supported media owner.
     */
    public static function isSupportedOwnerModel(mixed $owner): bool
    {
        return $owner instanceof Product || $owner instanceof ProductVariant;
    }

    /**
     * Determine if a given class/morph type is a supported media owner.
     */
    public static function isSupportedOwnerType(?string $type): bool
    {
        if ($type === null || $type === '') {
            return false;
        }

        return in_array($type, self::SUPPORTED_OWNERS, true)
            || is_subclass_of($type, Product::class)
            || is_subclass_of($type, ProductVariant::class);
    }

    /**
     * Determine if this media instance has a supported owner type.
     */
    public function isSupportedOwner(): bool
    {
        return self::isSupportedOwnerType($this->mediable_type);
    }

    /**
     * Validate that this media record has a supported owner type and valid existing owner.
     *
     * @throws DomainException
     */
    public function validateSupportedOwner(): void
    {
        if (! $this->isSupportedOwner()) {
            throw new DomainException("Unsupported mediable type: [{$this->mediable_type}]. Only Product and ProductVariant are supported.");
        }

        $owner = $this->getOwner();
        if (! $owner) {
            throw new DomainException("Media [{$this->id}] references a non-existent {$this->mediable_type} [{$this->mediable_id}].");
        }
    }

    /**
     * Retrieve the owner model instance even if soft-deleted.
     */
    public function getOwner(): ?Model
    {
        if (! $this->isSupportedOwner()) {
            return null;
        }

        /** @var class-string<Model> $type */
        $type = $this->mediable_type;

        if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
            return $type::withTrashed()->find($this->mediable_id);
        }

        return $type::find($this->mediable_id);
    }

    /**
     * Determine if this media record is owned by the given model.
     */
    public function isOwnedBy(Model $owner): bool
    {
        return $this->mediable_type === $owner->getMorphClass()
            && (int) $this->mediable_id === (int) $owner->getKey();
    }

    /**
     * Validate that this media record is owned by the specified model.
     *
     * @throws DomainException
     */
    public function validateOwnership(Model $owner): void
    {
        if (! self::isSupportedOwnerModel($owner)) {
            throw new DomainException('Unsupported media owner model: ['.get_class($owner).']. Only Product and ProductVariant are supported.');
        }

        if (! $this->isOwnedBy($owner)) {
            throw new DomainException("Media [{$this->id}] does not belong to {$owner->getMorphClass()} [{$owner->getKey()}].");
        }
    }

    /**
     * Create a media record explicitly for a supported owner.
     * Automatically marks as primary if it is the owner's first media record.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws DomainException|InvalidArgumentException
     */
    public static function createForOwner(Model $owner, array $attributes): static
    {
        if (! self::isSupportedOwnerModel($owner)) {
            throw new DomainException('Unsupported media owner model: ['.get_class($owner).']. Only Product and ProductVariant are supported.');
        }

        if (! $owner->exists) {
            throw new InvalidArgumentException('Cannot create media for an unsaved owner.');
        }

        return DB::transaction(function () use ($owner, $attributes): static {
            // Lock the owner row with lockForUpdate() to serialize creation for this specific owner
            // and eliminate race conditions when owner has zero media rows.
            $ownerQuery = $owner->newQuery();
            if (in_array(SoftDeletes::class, class_uses_recursive($owner), true)) {
                $ownerQuery->withTrashed();
            }
            $lockedOwner = $ownerQuery->whereKey($owner->getKey())->lockForUpdate()->first();

            if (! $lockedOwner) {
                throw new DomainException("Owner [{$owner->getMorphClass()}:{$owner->getKey()}] does not exist.");
            }

            $existingMedia = static::query()
                ->where('mediable_type', $owner->getMorphClass())
                ->where('mediable_id', $owner->getKey())
                ->lockForUpdate()
                ->get();

            $count = $existingMedia->count();

            if ($count === 0) {
                // First image must automatically become primary
                $attributes['is_primary'] = true;
                if (! isset($attributes['sort_order']) || (int) $attributes['sort_order'] === 0) {
                    $attributes['sort_order'] = 1;
                }
            } else {
                $requestedPrimary = (bool) ($attributes['is_primary'] ?? false);
                if ($requestedPrimary) {
                    static::query()
                        ->where('mediable_type', $owner->getMorphClass())
                        ->where('mediable_id', $owner->getKey())
                        ->where('is_primary', true)
                        ->update(['is_primary' => false]);
                    $attributes['is_primary'] = true;
                } else {
                    $attributes['is_primary'] = false;
                }

                if (! isset($attributes['sort_order']) || (int) $attributes['sort_order'] === 0) {
                    $maxSortOrder = $existingMedia->max('sort_order') ?? 0;
                    $attributes['sort_order'] = $maxSortOrder + 1;
                }
            }

            $attributes['mediable_type'] = $owner->getMorphClass();
            $attributes['mediable_id'] = $owner->getKey();

            return static::create($attributes);
        });
    }

    /**
     * Explicit domain operation to set this media record as primary.
     * Atomically clears any existing primary media belonging to the same owner.
     *
     * @throws DomainException
     */
    public function setPrimary(): static
    {
        $this->validateSupportedOwner();

        if (! $this->exists) {
            throw new DomainException('Cannot set an unsaved media record as primary.');
        }

        return DB::transaction(function (): static {
            $siblings = static::query()
                ->where('mediable_type', $this->mediable_type)
                ->where('mediable_id', $this->mediable_id)
                ->lockForUpdate()
                ->get();

            $otherPrimaryCount = $siblings->where('id', '!=', $this->id)->where('is_primary', true)->count();
            $thisInDb = $siblings->firstWhere('id', $this->id);

            if ($thisInDb && $thisInDb->is_primary && $otherPrimaryCount === 0) {
                $this->is_primary = true;

                return $this;
            }

            static::query()
                ->where('mediable_type', $this->mediable_type)
                ->where('mediable_id', $this->mediable_id)
                ->where('id', '!=', $this->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            static::query()
                ->where('id', $this->id)
                ->update(['is_primary' => true]);

            $this->is_primary = true;

            return $this;
        });
    }

    /**
     * Safely delete the media record.
     * When deleting a primary media, atomically resets all remaining media to is_primary = false
     * and promotes the remaining media with the lowest sort_order to repair any corrupted states.
     *
     * NOTE: DB transactions are not filesystem transactions. Physical file cleanup
     * will be handled in a later lifecycle phase.
     *
     * @throws DomainException
     */
    public function deleteSafely(): bool
    {
        $this->validateSupportedOwner();

        if (! $this->exists) {
            throw new DomainException('Cannot delete an unsaved media record.');
        }

        return DB::transaction(function (): bool {
            static::query()
                ->where('mediable_type', $this->mediable_type)
                ->where('mediable_id', $this->mediable_id)
                ->lockForUpdate()
                ->get();

            $wasPrimary = (bool) $this->is_primary;

            $deleted = (bool) $this->delete();

            if ($wasPrimary && $deleted) {
                // Set ALL remaining media for this owner to is_primary = false
                // to repair any corrupted/duplicate primary states
                static::query()
                    ->where('mediable_type', $this->mediable_type)
                    ->where('mediable_id', $this->mediable_id)
                    ->update(['is_primary' => false]);

                // Select the remaining media with sort_order ASC, id ASC
                $nextPrimary = static::query()
                    ->where('mediable_type', $this->mediable_type)
                    ->where('mediable_id', $this->mediable_id)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();

                // If one remains, promote exactly that media to primary
                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true]);
                }
            }

            return $deleted;
        });
    }

    /**
     * Domain setter for alt_text.
     * System-managed metadata cannot be mutated through this API.
     */
    public function updateAltText(?string $altText): static
    {
        $this->validateSupportedOwner();

        $this->update(['alt_text' => $altText]);

        return $this;
    }

    /**
     * Get the owning mediable model (Product or ProductVariant).
     *
     * @return MorphTo<Model, $this>
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the public/external URL for this media file via Laravel's Storage abstraction.
     *
     * Future-ready for S3/R2/CDN by relying strictly on Storage::disk($this->disk)->url($this->path).
     * Does not hard-code /storage/, asset(), or filesystem paths.
     */
    public function url(): string
    {
        if (blank($this->path)) {
            return '';
        }

        $diskName = $this->disk ?: config('filesystems.default', 'public');

        return Storage::disk($diskName)->url($this->path);
    }

    /**
     * Get URL as dynamic model attribute.
     */
    public function getUrlAttribute(): string
    {
        return $this->url();
    }

    /**
     * Count how many other media records in the database reference the exact same disk and path.
     */
    public function countOtherReferences(): int
    {
        if (blank($this->disk) || blank($this->path)) {
            return 0;
        }

        return static::query()
            ->where('disk', $this->disk)
            ->where('path', $this->path)
            ->where('id', '!=', $this->getKey() ?? 0)
            ->count();
    }

    /**
     * Determine if another media record shares the exact same disk and path.
     */
    public function hasOtherReferences(): bool
    {
        return $this->countOtherReferences() > 0;
    }

    /**
     * Determine if this media file has zero other database references and is a safe cleanup candidate.
     */
    public function isCleanupCandidate(): bool
    {
        return ! $this->hasOtherReferences();
    }

    /**
     * Check whether a specific disk and path combination is actively referenced by any Media record.
     *
     * @param  int|null  $exceptMediaId  Optional media ID to exclude from reference check
     */
    public static function isPathReferenced(string $disk, string $path, ?int $exceptMediaId = null): bool
    {
        if (blank($disk) || blank($path)) {
            return false;
        }

        $query = static::query()
            ->where('disk', $disk)
            ->where('path', $path);

        if ($exceptMediaId !== null) {
            $query->where('id', '!=', $exceptMediaId);
        }

        return $query->exists();
    }

    /**
     * Safely purge a physical file from storage if and only if NO Media records reference it.
     *
     * ARCHITECTURAL INVARIANT:
     * Database state and physical storage cleanup are separate concerns.
     * This method must NEVER be invoked inside a database transaction.
     *
     * @return bool True if physical deletion occurred; false if retained due to active references or missing file.
     */
    public static function deletePhysicalFileIfUnreferenced(string $disk, string $path): bool
    {
        if (blank($disk) || blank($path)) {
            return false;
        }

        // Shared-file protection: Never delete if any Media record still references this disk/path
        if (static::isPathReferenced($disk, $path)) {
            return false;
        }

        $storageDisk = Storage::disk($disk);

        if ($storageDisk->exists($path)) {
            return $storageDisk->delete($path);
        }

        return false;
    }

    /**
     * Safely purge this media item's physical file from storage if no other records reference it.
     * Must be called AFTER the database record has been deleted and committed.
     */
    public function purgePhysicalFile(): bool
    {
        if (blank($this->disk) || blank($this->path)) {
            return false;
        }

        return static::deletePhysicalFileIfUnreferenced($this->disk, $this->path);
    }

    /**
     * Generate a secure, owner-scoped storage path with an opaque unique filename.
     * Format: {owner-type-plural}/{owner-id}/{uuid}.{extension}
     *
     * @param  Model  $owner  Supported owner model (Product or ProductVariant)
     * @param  string  $extension  Validated file extension (e.g. 'jpg', 'png', 'webp')
     */
    public static function generateStoragePath(Model $owner, string $extension): string
    {
        $prefix = $owner instanceof ProductVariant ? 'variants' : 'products';
        $ownerId = $owner->getKey();
        $uuid = (string) Str::uuid();
        $cleanExt = strtolower(ltrim($extension, '.'));

        return "{$prefix}/{$ownerId}/{$uuid}.{$cleanExt}";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }
}
