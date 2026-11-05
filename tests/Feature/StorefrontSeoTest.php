<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the home page publishes indexable seo and open graph tags', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('name="description"', false)
        ->assertSee('name="robots" content="index, follow"', false)
        ->assertSee('property="og:type" content="website"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('SearchAction', false);
});

test('search results stay out of the index', function () {
    $this->get('/?search=shirt')
        ->assertSuccessful()
        ->assertSee('name="robots" content="noindex, follow"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, follow');
});

test('a category page uses the category name and stays indexable', function () {
    $category = Category::factory()->create([
        'name' => 'Cotton Shirts',
        'description' => 'Breathable cotton shirts for everyday wear.',
    ]);

    $this->get('/category/'.$category->slug)
        ->assertSuccessful()
        ->assertSee('Cotton Shirts', false)
        ->assertSee('Breathable cotton shirts for everyday wear.', false)
        ->assertSee('name="robots" content="index, follow"', false)
        ->assertSee('/category/'.$category->slug, false);
});

test('a product page uses the saved meta title and description', function () {
    $product = Product::factory()->create([
        'name' => 'Plain Shirt',
        'meta_title' => 'Premium Cotton Shirt',
        'meta_description' => 'Soft cotton shirt for everyday wear.',
        'is_active' => true,
    ]);

    $this->get('/product/'.$product->slug)
        ->assertSuccessful()
        ->assertSee('Premium Cotton Shirt', false)
        ->assertSee('Soft cotton shirt for everyday wear.', false)
        ->assertSee('property="og:type" content="product"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('https://schema.org', false)
        ->assertSee('name="robots" content="index, follow"', false);
});

test('checkout and account pages are not indexed', function () {
    $this->get('/checkout')
        ->assertSuccessful()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->get('/login')
        ->assertSuccessful()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

test('robots and the sitemap list public pages only', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertSee('Disallow: /checkout')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap:');

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee('/product/'.$product->slug, false)
        ->assertSee('/about-us', false)
        ->assertDontSee('/checkout');
});
