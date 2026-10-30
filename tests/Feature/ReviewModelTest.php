<?php

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('review can be created', function () {
    $review = Review::factory()->create([
        'rating' => 5,
        'title' => 'Great product!',
    ]);

    expect($review)
        ->toBeInstanceOf(Review::class)
        ->rating->toBe(5)
        ->title->toBe('Great product!')
        ->product_id->toBeInt()
        ->user_id->toBeInt();
});

test('review belongs to product and user', function () {
    $review = Review::factory()->create();

    expect($review->product)->toBeInstanceOf(Product::class)
        ->and($review->user)->toBeInstanceOf(User::class);
});

test('user can only review a product once', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});
