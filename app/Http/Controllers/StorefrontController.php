<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $offer = $request->boolean('offer');

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $productsQuery = Product::query()
            ->where('products.is_active', true)
            ->with([
                'categories:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'defaultVariant.inventory',
                'media',
            ])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_approved', true)]);

        if ($selectedCategory && $selectedCategory !== 'all') {
            $productsQuery->whereHas('categories', function ($q) use ($selectedCategory) {
                $q->where('slug', $selectedCategory);
            });
        }

        if (! empty($search)) {
            $this->applySearchCondition($productsQuery, $search);
        }

        if ($offer) {
            $productsQuery->whereHas('defaultVariant', function ($q) {
                $q->whereNotNull('compare_at_price')
                    ->whereColumn('compare_at_price', '>', 'selling_price');
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
            $productsQuery->latest('products.id');
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

            $image = $primaryMedia
                ? asset('storage/'.ltrim($primaryMedia->path, '/'))
                : ($imageMap[$product->slug] ?? '/images/storefront/product-1.jpg');

            $inventory = $variant?->inventory;
            $stockQuantity = $inventory ? (int) $inventory->quantity : null;
            $isInStock = $inventory ? $inventory->quantity > 0 : true;

            return [
                'id' => $product->id,
                'default_variant_id' => $variant?->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_name' => $product->categories->first()?->name ?? 'সাধারণ',
                'category_slug' => $product->categories->first()?->slug ?? 'general',
                'price' => (float) ($variant?->selling_price ?? 0),
                'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                'image' => $image,
                'short_description' => $product->short_description,
                'is_featured' => (bool) $product->is_featured,
                'rating' => round((float) ($product->reviews_avg_rating ?? 5.0), 1),
                'reviews_count' => (int) ($product->reviews_count ?? 0),
                'stock_quantity' => $stockQuantity,
                'is_in_stock' => $isInStock,
            ];
        });

        return Inertia::render('Home', [
            'categories' => $categories,
            'products' => $products,
            'filters' => [
                'category' => $selectedCategory,
                'search' => $search,
                'sort' => $sort,
                'offer' => $offer,
            ],
        ]);
    }

    /**
     * Search product suggestions for live dropdown.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return response()->json([]);
        }

        $productsQuery = Product::query()
            ->where('products.is_active', true)
            ->with([
                'categories:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'media',
            ]);

        $this->applySearchCondition($productsQuery, $search);

        $imageMap = [
            'cotton-shirt' => '/images/storefront/product-1.jpg',
            'classic-watch' => '/images/storefront/product-2.jpg',
            'ceramic-home-decor' => '/images/storefront/product-3.jpg',
            'sneakers-shoes' => '/images/storefront/product-4.jpg',
            'headphones-camera' => '/images/storefront/product-5.jpg',
            'casual-backpack' => '/images/storefront/product-1.jpg',
        ];

        $results = $productsQuery->limit(6)->get()->map(function (Product $product) use ($imageMap) {
            $variant = $product->defaultVariant;
            $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();

            $image = $imageMap[$product->slug]
                ?? ($primaryMedia ? asset('storage/'.ltrim($primaryMedia->path, '/')) : '/images/storefront/product-1.jpg');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_name' => $product->categories->first()?->name ?? 'ফ্যাশন',
                'price' => (float) ($variant?->selling_price ?? 0),
                'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                'image' => $image,
                'url' => route('product.show', ['slug' => $product->slug]),
            ];
        });

        return response()->json($results);
    }

    /**
     * Apply storefront search conditions to products query.
     */
    protected function applySearchCondition($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.slug', 'like', "%{$search}%")
                ->orWhere('products.short_description', 'like', "%{$search}%")
                ->orWhere('products.description', 'like', "%{$search}%")
                ->orWhereHas('categories', function ($catQuery) use ($search) {
                    $catQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })
                ->orWhereHas('variants', function ($varQuery) use ($search) {
                    $varQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
        });
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
                'brand:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'defaultVariant.inventory',
                'variants' => function ($q) {
                    $q->where('is_active', true)
                        ->with([
                            'inventory',
                            'variantAttributeValues.attribute:id,name,type',
                            'variantAttributeValues.attributeValue:id,name',
                        ]);
                },
                'media',
                'reviews' => function ($q) {
                    $q->where('is_approved', true)->with('user:id,name')->latest();
                },
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

        $mainImage = $primaryMedia
            ? asset('storage/'.ltrim($primaryMedia->path, '/'))
            : ($imageMap[$product->slug] ?? '/images/storefront/product-1.jpg');

        $mediaImages = $product->media->map(fn ($m) => asset('storage/'.ltrim($m->path, '/')))->all();
        if (empty($mediaImages)) {
            $mediaImages = [$mainImage];
        } elseif (! in_array($mainImage, $mediaImages)) {
            array_unshift($mediaImages, $mainImage);
        }

        $variants = $product->variants->map(function ($variant) {
            $options = $variant->variantAttributeValues->mapWithKeys(function ($vav) {
                $val = $vav->attributeValue ? $vav->attributeValue->name : ($vav->text_value ?? $vav->number_value);

                return [$vav->attribute->name => $val];
            })->toArray();

            return [
                'id' => $variant->id,
                'price' => (float) $variant->selling_price,
                'compare_at_price' => $variant->compare_at_price ? (float) $variant->compare_at_price : null,
                'stock_quantity' => $variant->inventory?->quantity ?? 0,
                'is_in_stock' => $variant->inventory ? $variant->inventory->quantity > 0 : true,
                'options' => $options,
            ];
        })->values()->toArray();

        $availableOptions = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->variantAttributeValues as $vav) {
                $attrName = $vav->attribute->name;
                $valName = $vav->attributeValue ? $vav->attributeValue->name : ($vav->text_value ?? $vav->number_value);

                if (! isset($availableOptions[$attrName])) {
                    $availableOptions[$attrName] = [];
                }
                if (! in_array($valName, $availableOptions[$attrName])) {
                    $availableOptions[$attrName][] = $valName;
                }
            }
        }

        $reviews = $product->reviews->map(function (Review $review) {
            return [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'reviewer_name' => $review->user?->name ?? 'সন্তুষ্ট গ্রাহক',
                'created_at' => $review->created_at->format('d M Y'),
            ];
        });

        $reviewsCount = $reviews->count();
        $avgRating = $reviewsCount > 0 ? round($reviews->avg('rating'), 1) : 5.0;

        $inventory = $variant?->inventory;
        $stockQuantity = $inventory ? (int) $inventory->quantity : null;
        $isInStock = $inventory ? $inventory->quantity > 0 : true;

        $formattedProduct = [
            'id' => $product->id,
            'default_variant_id' => $variant?->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category_name' => $product->categories->first()?->name ?? 'সাধারণ পণ্য',
            'category_slug' => $product->categories->first()?->slug ?? 'general',
            'brand_name' => $product->brand?->name,
            'price' => (float) ($variant?->selling_price ?? 0),
            'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
            'image' => $mainImage,
            'images' => $mediaImages,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'is_featured' => (bool) $product->is_featured,
            'stock_quantity' => $stockQuantity,
            'is_in_stock' => $isInStock,
            'rating' => $avgRating,
            'reviews_count' => $reviewsCount,
            'reviews' => $reviews,
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
        $request->merge([
            'shipping_address_line' => $request->input('shipping_address_line', $request->input('shipping_address')),
            'shipping_amount' => $request->input('shipping_amount', 60),
            'cart_items' => $request->input('cart_items', $request->input('items')),
        ]);

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

        // Stock availability verification before order placement
        foreach ($validated['cart_items'] as $item) {
            $variant = ProductVariant::with('inventory', 'product')->find($item['variant_id']);
            if ($variant && $variant->inventory && $variant->inventory->quantity < $item['quantity']) {
                $pName = $variant->product?->name ?? 'পণ্য';

                return back()->withErrors([
                    'stock' => "দুঃখিত, \"{$pName}\" পণ্যের জন্য পর্যাপ্ত স্টক নেই (বর্তমান স্টক: {$variant->inventory->quantity} টি)।",
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $orderNumber = 'ORD-'.strtoupper(uniqid());

            // Link authenticated customer or create/find by phone
            $customer = auth('customer')->user();
            if (! $customer) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $validated['customer_phone']],
                    [
                        'name' => $validated['customer_name'],
                        'is_active' => true,
                    ]
                );
            }

            $subtotal = 0;
            foreach ($validated['cart_items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                $subtotal += $variant->selling_price * $item['quantity'];
            }

            $discountAmount = 0;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })
                    ->first();

                if ($coupon && $subtotal >= $coupon->minimum_order_amount) {
                    if ($coupon->type === 'percent') {
                        $discountAmount = ($subtotal * $coupon->value) / 100;
                        if ($coupon->maximum_discount_amount) {
                            $discountAmount = min($discountAmount, $coupon->maximum_discount_amount);
                        }
                    } else {
                        $discountAmount = $coupon->value;
                    }

                    // Update usage count
                    $coupon->increment('used_count');
                }
            }

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address_line' => $validated['shipping_address_line'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_area' => $validated['shipping_city'], // fallback for area
                'shipping_amount' => $validated['shipping_amount'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'grand_total' => $subtotal - $discountAmount + $validated['shipping_amount'],
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => PaymentMethod::Cod,
            ]);

            foreach ($validated['cart_items'] as $item) {
                $product = Product::find($item['id']);
                $variant = ProductVariant::find($item['variant_id']);

                $price = $variant->selling_price;
                $lineTotal = $price * $item['quantity'];

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

            // Send order placement confirmation SMS
            try {
                app(SmsService::class)->sendOrderPlacedSms($order);
            } catch (\Throwable $e) {
                Log::error('Order SMS notification error: '.$e->getMessage());
            }

            return redirect()->route('checkout.success', ['order_number' => $orderNumber]);
        });
    }

    /**
     * Display checkout success page.
     */
    public function checkoutSuccess(Request $request): Response
    {
        $orderNumber = $request->query('order_number');

        return Inertia::render('CheckoutSuccess', [
            'order_number' => $orderNumber,
        ]);
    }

    /**
     * Handle contact form submission.
     */
    public function submitContact(Request $request)
    {
        $contactIdentifier = $request->input('email_or_phone') ?: ($request->input('email') ?: $request->input('phone'));
        $request->merge([
            'email_or_phone' => $contactIdentifier,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email_or_phone' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if (function_exists('activity')) {
            activity()
                ->withProperties($validated)
                ->log('Storefront contact form message received');
        }

        return back()->with('success', 'আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে। আমরা শীঘ্রই যোগাযোগ করবো।');
    }

    /**
     * Submit a customer review for a product.
     */
    public function submitReview(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:2000',
            'reviewer_name' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        if (! $user) {
            $reviewerName = ! empty($validated['reviewer_name']) ? $validated['reviewer_name'] : 'সন্তুষ্ট গ্রাহক';
            $user = User::firstOrCreate(
                ['email' => 'guest_review_'.md5(strtolower($reviewerName)).'@noboshop.com'],
                [
                    'name' => $reviewerName,
                    'password' => bcrypt(str()->random(16)),
                ]
            );
        }

        Review::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?: ($validated['reviewer_name'] ? "রিভিউ করেছেন: {$validated['reviewer_name']}" : 'কাস্টমার রিভিউ'),
                'body' => $validated['body'],
                'is_approved' => true,
            ]
        );

        return back()->with('success', 'আপনার রিভিউ সফলভাবে যুক্ত হয়েছে! ধন্যবাদ।');
    }

    /**
     * Apply coupon code.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric',
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (! $coupon) {
            return response()->json(['message' => 'কুপন কোডটি সঠিক নয় বা মেয়াদোত্তীর্ণ।'], 400);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'এই কুপনের ব্যবহারের সীমা শেষ হয়ে গেছে।'], 400);
        }

        if ($request->subtotal < $coupon->minimum_order_amount) {
            return response()->json(['message' => 'এই কুপনটি ব্যবহার করতে নূন্যতম '.$coupon->minimum_order_amount.' টাকার অর্ডার করতে হবে।'], 400);
        }

        $discount = 0;
        if ($coupon->type === 'percent') {
            $discount = ($request->subtotal * $coupon->value) / 100;
            if ($coupon->maximum_discount_amount && $discount > $coupon->maximum_discount_amount) {
                $discount = $coupon->maximum_discount_amount;
            }
        } else {
            $discount = $coupon->value;
        }

        return response()->json([
            'message' => 'কুপন সফলভাবে অ্যাপ্লাই করা হয়েছে!',
            'discount' => $discount,
            'code' => $coupon->code,
        ]);
    }

    /**
     * Display order tracking page.
     */
    public function trackOrder(Request $request): Response
    {
        $orderNumber = $request->query('order_number');
        $phone = $request->query('phone');

        $orderData = null;
        $error = null;

        if ($orderNumber) {
            $query = Order::query()
                ->where('order_number', $orderNumber)
                ->with([
                    'items',
                    'statusHistories' => function ($q) {
                        $q->orderBy('created_at', 'asc');
                    },
                ]);

            if ($phone) {
                $query->where('customer_phone', $phone);
            }

            $order = $query->first();

            if ($order) {
                $orderData = [
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'payment_status' => $order->payment_status->value,
                    'payment_method' => $order->payment_method->value,
                    'grand_total' => (float) $order->grand_total,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_amount' => (float) $order->shipping_amount,
                    'placed_at' => $order->created_at->format('d M Y, h:i A'),
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'shipping_address' => $order->shipping_address_line.', '.$order->shipping_city,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'name' => $item->variant_name ?? $item->product_name,
                            'quantity' => $item->quantity,
                            'price' => (float) $item->unit_price,
                            'total' => (float) $item->line_total,
                        ];
                    }),
                    'histories' => $order->statusHistories->map(function ($history) {
                        return [
                            'status' => $history->to_status->value,
                            'date' => $history->created_at->format('d M Y, h:i A'),
                            'reason' => $history->reason,
                        ];
                    }),
                ];
            } else {
                $error = 'এই অর্ডার নম্বর দিয়ে কোনো অর্ডার পাওয়া যায়নি। দয়া করে সঠিক নম্বর দিন।';
            }
        }

        return Inertia::render('OrderTrack', [
            'order' => $orderData,
            'searched_order_number' => $orderNumber,
            'searched_phone' => $phone,
            'error' => $error,
        ]);
    }
}
