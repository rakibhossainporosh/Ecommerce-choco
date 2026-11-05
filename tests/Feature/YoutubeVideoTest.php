<?php

use App\Models\Product;
use App\Support\YoutubeVideo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

test('youtube links are normalized to a watch url', function () {
    $product = Product::factory()->create([
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?si=tracking',
    ]);

    expect($product->fresh()->youtube_url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
});

test('a non youtube link cannot be saved on a product', function () {
    $product = Product::factory()->create();

    expect(fn () => $product->update([
        'youtube_url' => 'https://example.com/watch?v=dQw4w9WgXcQ',
    ]))->toThrow(ValidationException::class);

    expect($product->fresh()->youtube_url)->toBeNull();
});

test('product detail exposes a safe embed and a youtube watch link', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'youtube_url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    ]);

    $this->get('/product/'.$product->slug)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ProductDetail', false)
            ->where('product.youtube.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->where('product.youtube.watch_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );
});

test('product detail omits the video when no link is set', function () {
    $product = Product::factory()->create(['is_active' => true]);

    expect(YoutubeVideo::embedUrl($product->youtube_url))->toBeNull();

    $this->get('/product/'.$product->slug)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('product.youtube', null)
        );
});
