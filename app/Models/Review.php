<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'user_id',
    'rating',
    'title',
    'body',
    'is_approved',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Review $review) {
            $review->validateBusinessRules();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validateBusinessRules(): void
    {
        if ($this->rating < 1 || $this->rating > 5) {
            throw new DomainException('Rating must be between 1 and 5.');
        }

        if ($this->title !== null) {
            $this->title = trim($this->title);
        }

        if ($this->body !== null) {
            $this->body = trim($this->body);
        }
    }
}
