<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class StorefrontSeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /cart/',
            'Disallow: /track-order',
            'Disallow: /payments/',
            'Disallow: /search/',
            '',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => null],
        ];

        if (Schema::hasTable('categories')) {
            Category::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (Category $category) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('category.show', ['slug' => $category->slug]),
                        'lastmod' => $category->updated_at?->toAtomString(),
                    ];
                });
        }

        if (Schema::hasTable('products')) {
            Product::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (Product $product) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('product.show', ['slug' => $product->slug]),
                        'lastmod' => $product->updated_at?->toAtomString(),
                    ];
                });
        }

        foreach ([
            route('page.about'),
            route('page.contact'),
            route('page.faq'),
            route('page.privacy-policy'),
            route('page.terms'),
            route('page.return-policy'),
        ] as $loc) {
            $urls[] = ['loc' => $loc, 'lastmod' => null];
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $xml[] = '<url>';
            $xml[] = '<loc>'.e($url['loc']).'</loc>';

            if (is_string($url['lastmod']) && $url['lastmod'] !== '') {
                $xml[] = '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            $xml[] = '</url>';
        }

        $xml[] = '</urlset>';

        return response(implode('', $xml), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
