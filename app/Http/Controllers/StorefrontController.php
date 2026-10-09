<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    /**
     * Display the storefront home page.
     */
    public function index(Request $request): Response
    {
        $selectedCategory = $request->query('category', 'all');
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'newest');

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with([
                'categories:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'media',
            ]);

        if ($selectedCategory && $selectedCategory !== 'all') {
            $productsQuery->whereHas('categories', function ($q) use ($selectedCategory) {
                $q->where('slug', $selectedCategory);
            });
        }

        if (! empty($search)) {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($sort === 'price_asc') {
            $productsQuery->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->where('product_variants.is_default', true)
                ->orderBy('product_variants.selling_price', 'asc')
                ->select('products.*');
        } elseif ($sort === 'price_desc') {
            $productsQuery->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->where('product_variants.is_default', true)
                ->orderBy('product_variants.selling_price', 'desc')
                ->select('products.*');
        } else {
            $productsQuery->latest('id');
        }

        $imageMap = [
            'cotton-shirt' => '/images/storefront/product-1.jpg',
            'classic-watch' => '/images/storefront/product-2.jpg',
            'ceramic-home-decor' => '/images/storefront/product-3.jpg',
            'sneakers-shoes' => '/images/storefront/product-4.jpg',
            'headphones-camera' => '/images/storefront/product-5.jpg',
            'casual-backpack' => '/images/storefront/product-1.jpg',
        ];

        $products = $productsQuery->get()->map(function (Product $product) use ($imageMap) {
            $variant = $product->defaultVariant;
            $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();

            $image = $imageMap[$product->slug]
                ?? ($primaryMedia ? asset('storage/'.ltrim($primaryMedia->path, '/')) : '/images/storefront/product-1.jpg');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_name' => $product->categories->first()?->name ?? 'ফ্যাশন',
                'category_slug' => $product->categories->first()?->slug ?? 'fashion',
                'price' => (float) ($variant?->selling_price ?? 0),
                'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                'image' => $image,
                'short_description' => $product->short_description,
                'is_featured' => (bool) $product->is_featured,
            ];
        });

        return Inertia::render('Home', [
            'categories' => $categories,
            'products' => $products,
            'filters' => [
                'category' => $selectedCategory,
                'search' => $search,
                'sort' => $sort,
            ],
        ]);
    }
}
