<?php

namespace App\Http\Controllers;

use App\Models\WishlistItem;
use App\Support\CatalogTranslator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerWishlistController extends Controller
{
    public function index(): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return response()->json(['items' => []], 401);
        }

        return response()->json([
            'items' => $this->presentItems($customer->id),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $existing = WishlistItem::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
        } else {
            WishlistItem::query()->create([
                'customer_id' => $customer->id,
                'product_id' => $validated['product_id'],
            ]);
            $inWishlist = true;
        }

        return response()->json([
            'in_wishlist' => $inWishlist,
            'items' => $this->presentItems($customer->id),
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $productIds = collect($validated['product_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($productIds as $productId) {
            WishlistItem::query()->firstOrCreate([
                'customer_id' => $customer->id,
                'product_id' => $productId,
            ]);
        }

        return response()->json([
            'items' => $this->presentItems($customer->id),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentItems(int $customerId): array
    {
        $imageMap = [
            'cotton-shirt' => '/images/storefront/product-1.jpg',
            'classic-watch' => '/images/storefront/product-2.jpg',
            'ceramic-home-decor' => '/images/storefront/product-3.jpg',
            'sneakers-shoes' => '/images/storefront/product-4.jpg',
            'headphones-camera' => '/images/storefront/product-5.jpg',
            'casual-backpack' => '/images/storefront/product-6.jpg',
        ];

        return WishlistItem::query()
            ->where('customer_id', $customerId)
            ->with([
                'product.categories:id,name,slug',
                'product.defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'product.defaultVariant.inventory',
                'product.media',
            ])
            ->latest('id')
            ->get()
            ->map(function (WishlistItem $item) use ($imageMap) {
                $product = $item->product;

                if (! $product || ! $product->is_active) {
                    return null;
                }

                $variant = $product->defaultVariant;
                $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();
                $image = $primaryMedia
                    ? asset('storage/'.ltrim($primaryMedia->path, '/'))
                    : ($imageMap[$product->slug] ?? '/images/storefront/product-1.jpg');

                return [
                    'id' => $product->id,
                    'name' => CatalogTranslator::product($product->slug, 'name', $product->name),
                    'display_name' => CatalogTranslator::product($product->slug, 'name', $product->name),
                    'price' => (float) ($variant?->selling_price ?? 0),
                    'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                    'image' => $image,
                    'category_name' => $product->categories->first()?->name,
                    'category_display_name' => $product->categories->first()
                        ? CatalogTranslator::category($product->categories->first()->slug, $product->categories->first()->name)
                        : null,
                    'is_in_stock' => $variant?->inventory ? $variant->inventory->quantity > 0 : true,
                    'slug' => $product->slug,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
