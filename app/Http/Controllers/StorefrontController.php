<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'default_variant_id' => $variant?->id,
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

    /**
     * Display product detail page.
     */
    public function show(string $slug): Response
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'categories:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'variants' => function ($q) {
                    $q->where('is_active', true)
                      ->with([
                          'variantAttributeValues.attribute:id,name,type',
                          'variantAttributeValues.attributeValue:id,name'
                      ]);
                },
                'media',
            ])
            ->firstOrFail();

        $imageMap = [
            'cotton-shirt' => '/images/storefront/product-1.jpg',
            'classic-watch' => '/images/storefront/product-2.jpg',
            'ceramic-home-decor' => '/images/storefront/product-3.jpg',
            'sneakers-shoes' => '/images/storefront/product-4.jpg',
            'headphones-camera' => '/images/storefront/product-5.jpg',
            'casual-backpack' => '/images/storefront/product-1.jpg',
        ];

        $variant = $product->defaultVariant;
        $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();
        
        $image = $imageMap[$product->slug]
            ?? ($primaryMedia ? asset('storage/'.ltrim($primaryMedia->path, '/')) : '/images/storefront/product-1.jpg');

        $variants = $product->variants->map(function ($variant) {
            $options = $variant->variantAttributeValues->mapWithKeys(function ($vav) {
                $val = $vav->attributeValue ? $vav->attributeValue->name : ($vav->text_value ?? $vav->number_value);
                return [$vav->attribute->name => $val];
            })->toArray();

            return [
                'id' => $variant->id,
                'price' => (float) $variant->selling_price,
                'compare_at_price' => $variant->compare_at_price ? (float) $variant->compare_at_price : null,
                'options' => $options,
            ];
        })->values()->toArray();

        $availableOptions = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->variantAttributeValues as $vav) {
                $attrName = $vav->attribute->name;
                $valName = $vav->attributeValue ? $vav->attributeValue->name : ($vav->text_value ?? $vav->number_value);
                
                if (!isset($availableOptions[$attrName])) {
                    $availableOptions[$attrName] = [];
                }
                if (!in_array($valName, $availableOptions[$attrName])) {
                    $availableOptions[$attrName][] = $valName;
                }
            }
        }

        $formattedProduct = [
            'id' => $product->id,
            'default_variant_id' => $variant?->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category_name' => $product->categories->first()?->name ?? 'ফ্যাশন',
            'category_slug' => $product->categories->first()?->slug ?? 'fashion',
            'price' => (float) ($variant?->selling_price ?? 0),
            'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
            'image' => $image,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'is_featured' => (bool) $product->is_featured,
            'variants' => $variants,
            'available_options' => $availableOptions,
        ];

        return Inertia::render('ProductDetail', [
            'product' => $formattedProduct,
        ]);
    }

    /**
     * Display checkout page.
     */
    public function checkout(): Response
    {
        return Inertia::render('Checkout');
    }

    /**
     * Process checkout.
     */
    public function processCheckout(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address_line' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_amount' => 'required|numeric',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|exists:products,id',
            'cart_items.*.variant_id' => 'required|exists:product_variants,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $orderNumber = 'ORD-' . strtoupper(uniqid());

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address_line' => $validated['shipping_address_line'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_amount' => $validated['shipping_amount'],
                'status' => \App\Enums\OrderStatus::Pending,
                'payment_status' => \App\Enums\PaymentStatus::Unpaid,
                'payment_method' => \App\Enums\PaymentMethod::Cod,
            ]);

            $subtotal = 0;
            foreach ($validated['cart_items'] as $item) {
                $product = Product::find($item['id']);
                $variant = ProductVariant::find($item['variant_id']);
                
                $price = $variant->selling_price;
                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant->name ?? $product->name,
                    'sku' => $variant->sku,
                    'unit_price' => $price,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ]);
            }

            $order->subtotal = $subtotal;
            $order->grand_total = $subtotal + $validated['shipping_amount'];
            $order->save();

            return redirect()->route('home')->with('success', 'আপনার অর্ডারটি সফলভাবে প্লেস করা হয়েছে! অর্ডার নম্বর: ' . $orderNumber);
        });
    }
}
