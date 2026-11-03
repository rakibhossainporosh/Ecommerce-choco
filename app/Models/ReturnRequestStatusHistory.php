<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Database\Factories\ReturnRequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'return_request_id',
    'changed_by',
    'from_status',
    'to_status',
    'reason',
    'created_at',
])]
class ReturnRequestStatusHistory extends Model
{
    /** @use HasFactory<ReturnRequestStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'return_request_id' => 'integer',
            'changed_by' => 'integer',
            'from_status' => ReturnRequestStatus::class,
            'to_status' => ReturnRequestStatus::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('ReturnRequestStatusHistory records are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('ReturnRequestStatusHistory records are immutable and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
