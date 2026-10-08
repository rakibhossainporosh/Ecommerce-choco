<?php

use App\Models\Review;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('rating must be at least 1', function () {
    expect(fn () => Review::factory()->create([
        'rating' => 0,
    ]))->toThrow(DomainException::class, 'Rating must be between 1 and 5.');
});

test('rating cannot exceed 5', function () {
    expect(fn () => Review::factory()->create([
        'rating' => 6,
    ]))->toThrow(DomainException::class, 'Rating must be between 1 and 5.');
});

test('title and body are trimmed', function () {
    $review = Review::factory()->create([
        'title' => '   Great    ',
        'body' => '   Nice.   ',
    ]);

    expect($review->title)->toBe('Great')
        ->and($review->body)->toBe('Nice.');
});
