<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayMode;
use App\Payments\PaymentManager;
use App\Services\AbandonedCartService;
use App\Support\CatalogTranslator;
use App\Support\CheckoutShipping;
use Illuminate\Http\JsonResponse;
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
        $offer = $request->boolean('offer');

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'display_name' => CatalogTranslator::category($category->slug, $category->name),
            ]);

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

        $imageMap = $this->storefrontImageMap();

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
                'display_name' => CatalogTranslator::product($product->slug, 'name', $product->name),
                'slug' => $product->slug,
                'category_name' => $product->categories->first()?->name ?? __('storefront.product.general'),
                'category_display_name' => $product->categories->first()
                    ? CatalogTranslator::category($product->categories->first()->slug, $product->categories->first()->name)
                    : __('storefront.product.general'),
                'category_slug' => $product->categories->first()?->slug ?? 'general',
                'price' => (float) ($variant?->selling_price ?? 0),
                'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                'image' => $image,
                'fallback_image' => $imageMap[$product->slug] ?? '/images/storefront/product-1.jpg',
                'short_description' => $product->short_description,
                'display_short_description' => CatalogTranslator::product($product->slug, 'short_description', $product->short_description),
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

        $imageMap = $this->storefrontImageMap();

        $results = $productsQuery->limit(6)->get()->map(function (Product $product) use ($imageMap) {
            $variant = $product->defaultVariant;
            $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();

            $image = $primaryMedia
                ? asset('storage/'.ltrim($primaryMedia->path, '/'))
                : ($imageMap[$product->slug] ?? '/images/storefront/product-1.jpg');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'display_name' => CatalogTranslator::product($product->slug, 'name', $product->name),
                'slug' => $product->slug,
                'category_name' => $product->categories->first()?->name ?? __('storefront.product.general'),
                'category_display_name' => $product->categories->first()
                    ? CatalogTranslator::category($product->categories->first()->slug, $product->categories->first()->name)
                    : __('storefront.product.general'),
                'price' => (float) ($variant?->selling_price ?? 0),
                'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
                'image' => $image,
                'fallback_image' => $imageMap[$product->slug] ?? '/images/storefront/product-1.jpg',
                'url' => route('product.show', ['slug' => $product->slug]),
            ];
        });

        return response()->json($results);
    }

    /**
     * Demo catalog image map used when uploaded media is missing or broken.
     *
     * @return array<string, string>
     */
    protected function storefrontImageMap(): array
    {
        return [
            'cotton-shirt' => '/images/storefront/product-1.jpg',
            'classic-watch' => '/images/storefront/product-2.jpg',
            'ceramic-home-decor' => '/images/storefront/product-3.jpg',
            'sneakers-shoes' => '/images/storefront/product-4.jpg',
            'headphones-camera' => '/images/storefront/product-5.jpg',
            'casual-backpack' => '/images/storefront/product-1.jpg',
        ];
    }

    /**
     * Apply storefront search conditions to products query.
     */
    protected function applySearchCondition($query, string $search)
    {
        $needles = CatalogTranslator::searchNeedles($search);

        return $query->where(function ($q) use ($needles) {
            foreach ($needles as $needle) {
                $q->orWhere(function ($inner) use ($needle) {
                    $inner->where('products.name', 'like', "%{$needle}%")
                        ->orWhere('products.slug', 'like', "%{$needle}%")
                        ->orWhere('products.short_description', 'like', "%{$needle}%")
                        ->orWhere('products.description', 'like', "%{$needle}%")
                        ->orWhereHas('categories', function ($catQuery) use ($needle) {
                            $catQuery->where('name', 'like', "%{$needle}%")
                                ->orWhere('slug', 'like', "%{$needle}%");
                        })
                        ->orWhereHas('variants', function ($varQuery) use ($needle) {
                            $varQuery->where('name', 'like', "%{$needle}%")
                                ->orWhere('sku', 'like', "%{$needle}%");
                        });
                });
            }
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

        $imageMap = $this->storefrontImageMap();

        $variant = $product->defaultVariant;
        $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();

        $fallbackImage = $imageMap[$product->slug] ?? '/images/storefront/product-1.jpg';
        $mainImage = $primaryMedia
            ? asset('storage/'.ltrim($primaryMedia->path, '/'))
            : $fallbackImage;

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
                'reviewer_name' => $review->user?->name ?? __('storefront.reviews.satisfied_customer'),
                'created_at' => $review->created_at->format('d M Y'),
            ];
        });

        $reviewsCount = $reviews->count();
        $avgRating = $reviewsCount > 0 ? round($reviews->avg('rating'), 1) : 0.0;

        // Breakdown percentages for 5, 4, 3, 2, 1 stars
        $ratingBreakdown = [];
        for ($s = 5; $s >= 1; $s--) {
            $countForStar = $reviews->where('rating', $s)->count();
            $percentage = $reviewsCount > 0 ? round(($countForStar / $reviewsCount) * 100) : 0;
            $ratingBreakdown[$s] = [
                'count' => $countForStar,
                'percentage' => $percentage,
            ];
        }

        $inventory = $variant?->inventory;
        $stockQuantity = $inventory ? (int) $inventory->quantity : null;
        $isInStock = $inventory ? $inventory->quantity > 0 : true;

        $formattedProduct = [
            'id' => $product->id,
            'default_variant_id' => $variant?->id,
            'name' => $product->name,
            'display_name' => CatalogTranslator::product($product->slug, 'name', $product->name),
            'slug' => $product->slug,
            'category_name' => $product->categories->first()?->name ?? __('storefront.product.general'),
            'category_display_name' => $product->categories->first()
                ? CatalogTranslator::category($product->categories->first()->slug, $product->categories->first()->name)
                : __('storefront.product.general'),
            'category_slug' => $product->categories->first()?->slug ?? 'general',
            'brand_name' => $product->brand?->name,
            'price' => (float) ($variant?->selling_price ?? 0),
            'compare_at_price' => $variant?->compare_at_price ? (float) $variant->compare_at_price : null,
            'image' => $mainImage,
            'fallback_image' => $fallbackImage,
            'rating' => $avgRating > 0 ? $avgRating : 5.0,
            'rating_raw' => $avgRating,
            'reviews_count' => $reviewsCount,
            'rating_breakdown' => $ratingBreakdown,
            'reviews' => $reviews,
            'images' => $mediaImages,
            'short_description' => $product->short_description,
            'display_short_description' => CatalogTranslator::product($product->slug, 'short_description', $product->short_description),
            'description' => $product->description,
            'display_description' => CatalogTranslator::product($product->slug, 'description', $product->description),
            'is_featured' => (bool) $product->is_featured,
            'stock_quantity' => $stockQuantity,
            'is_in_stock' => $isInStock,
            'rating' => $avgRating,
            'reviews_count' => $reviewsCount,
            'reviews' => $reviews,
            'variants' => $variants,
            'available_options' => $availableOptions,
        ];

        $categoryIds = $product->categories->pluck('id');

        $relatedProducts = Product::query()
            ->where('products.is_active', true)
            ->where('products.id', '!=', $product->id)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)),
            )
            ->with([
                'categories:id,name,slug',
                'defaultVariant:id,product_id,selling_price,compare_at_price,is_active',
                'defaultVariant.inventory',
                'media',
            ])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_approved', true)])
            ->latest('products.id')
            ->limit(8)
            ->get()
            ->map(function (Product $related) use ($imageMap) {
                $relatedVariant = $related->defaultVariant;
                $relatedMedia = $related->media->firstWhere('is_primary', true) ?? $related->media->first();
                $relatedFallback = $imageMap[$related->slug] ?? '/images/storefront/product-1.jpg';
                $relatedImage = $relatedMedia
                    ? asset('storage/'.ltrim($relatedMedia->path, '/'))
                    : $relatedFallback;
                $relatedInventory = $relatedVariant?->inventory;

                return [
                    'id' => $related->id,
                    'default_variant_id' => $relatedVariant?->id,
                    'name' => $related->name,
                    'display_name' => CatalogTranslator::product($related->slug, 'name', $related->name),
                    'slug' => $related->slug,
                    'category_name' => $related->categories->first()?->name ?? __('storefront.product.general'),
                    'category_display_name' => $related->categories->first()
                        ? CatalogTranslator::category($related->categories->first()->slug, $related->categories->first()->name)
                        : __('storefront.product.general'),
                    'category_slug' => $related->categories->first()?->slug ?? 'general',
                    'price' => (float) ($relatedVariant?->selling_price ?? 0),
                    'compare_at_price' => $relatedVariant?->compare_at_price ? (float) $relatedVariant->compare_at_price : null,
                    'image' => $relatedImage,
                    'fallback_image' => $relatedFallback,
                    'short_description' => $related->short_description,
                    'display_short_description' => CatalogTranslator::product($related->slug, 'short_description', $related->short_description),
                    'is_featured' => (bool) $related->is_featured,
                    'rating' => round((float) ($related->reviews_avg_rating ?? 5.0), 1),
                    'reviews_count' => (int) ($related->reviews_count ?? 0),
                    'stock_quantity' => $relatedInventory ? (int) $relatedInventory->quantity : null,
                    'is_in_stock' => $relatedInventory ? $relatedInventory->quantity > 0 : true,
                ];
            })
            ->values();

        return Inertia::render('ProductDetail', [
            'product' => $formattedProduct,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * Display checkout page.
     */
    public function checkout(PaymentManager $payments): Response
    {
        return Inertia::render('Checkout', [
            'payment_methods' => $payments->checkoutOptions(),
            'shipping_methods' => CheckoutShipping::options(),
        ]);
    }

    /**
     * Process checkout.
     */
    public function processCheckout(Request $request, AbandonedCartService $carts, PaymentManager $payments)
    {
        $request->merge([
            'shipping_address_line' => $request->input('shipping_address_line', $request->input('shipping_address')),
            'shipping_amount' => $request->input('shipping_amount', 60),
            'cart_items' => $request->input('cart_items', $request->input('items')),
            'payment_method' => strtolower((string) $request->input('payment_method', 'cod')),
        ]);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'shipping_address_line' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_method_id' => ['nullable', 'integer'],
            'shipping_amount' => 'required|numeric',
            'payment_method' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|exists:products,id',
            'cart_items.*.variant_id' => 'required|exists:product_variants,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        $gateway = $payments->checkoutGateway($validated['payment_method']);

        if (! $gateway) {
            return back()->withErrors([
                'payment_method' => __('storefront.checkout.payment_unavailable'),
            ]);
        }

        if ($gateway->mode() === PaymentGatewayMode::Redirect && blank($validated['customer_email'] ?? null)) {
            return back()->withErrors([
                'customer_email' => __('storefront.checkout.email_required_online'),
            ]);
        }

        foreach ($validated['cart_items'] as $item) {
            $variant = ProductVariant::with('inventory', 'product')->find($item['variant_id']);
            if ($variant && $variant->inventory && $variant->inventory->quantity < $item['quantity']) {
                $productName = $variant->product
                    ? CatalogTranslator::product($variant->product->slug, 'name', $variant->product->name)
                    : __('storefront.product.general');

                return back()->withErrors([
                    'stock' => __('storefront.checkout.insufficient_stock', [
                        'product' => $productName,
                        'stock' => $variant->inventory->quantity,
                    ]),
                ]);
            }
        }

        $order = DB::transaction(function () use ($validated, $request, $carts, $gateway) {
            $orderNumber = 'ORD-'.strtoupper(uniqid());

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

            if (filled($validated['customer_email'] ?? null) && blank($customer->email)) {
                $customer->email = $validated['customer_email'];
                $customer->save();
            }

            $subtotal = 0;
            foreach ($validated['cart_items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                $subtotal += $variant->selling_price * $item['quantity'];
            }

            $shippingAmount = CheckoutShipping::quote(
                $validated['shipping_city'],
                isset($validated['shipping_method_id']) ? (int) $validated['shipping_method_id'] : null,
                $subtotal,
            );

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

                    $coupon->increment('used_count');
                }
            }

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_address_line' => $validated['shipping_address_line'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_area' => $validated['shipping_city'],
                'shipping_amount' => $shippingAmount,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'grand_total' => $subtotal - $discountAmount + $shippingAmount,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => $gateway->key(),
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

            $carts->markRecovered($order);

            return $order;
        });

        $reference = isset($validated['transaction_reference'])
            ? trim(strip_tags($validated['transaction_reference']))
            : null;

        try {
            $start = $gateway->start($order->fresh(), $reference !== '' ? $reference : null);
        } catch (PaymentGatewayException $exception) {
            $this->abandonUnpaidOrder($order);

            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (PaymentException $exception) {
            report($exception);
            $this->abandonUnpaidOrder($order);

            return back()->withErrors([
                'payment' => __('storefront.checkout.payment_start_failed'),
            ]);
        }

        if (filled($start->redirectUrl)) {
            return Inertia::location($start->redirectUrl);
        }

        return redirect()->route('checkout.success', ['order_number' => $order->order_number]);
    }

    private function abandonUnpaidOrder(Order $order): void
    {
        if (! $order->canBeCancelled()) {
            return;
        }

        $order->cancel(__('storefront.checkout.payment_start_failed'));
    }

    /**
     * Display checkout success page.
     */
    public function checkoutSuccess(Request $request, PaymentManager $payments): Response
    {
        $orderNumber = $request->query('order_number');
        $notice = null;

        if (is_string($orderNumber) && $orderNumber !== '') {
            $order = Order::query()->where('order_number', $orderNumber)->first();
            $method = $order?->payment_method;
            $key = $method instanceof PaymentMethod ? $method->value : (is_string($method) ? $method : null);
            $notice = $key ? $payments->find($key)?->successNotice($order) : null;
        }

        return Inertia::render('CheckoutSuccess', [
            'order_number' => is_string($orderNumber) ? $orderNumber : null,
            'payment' => $notice,
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

        return back()->with('success', __('storefront.contact.success'));
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
            'reviewer_name' => 'required|string|max:255',
            'reviewer_email' => 'required|email|max:255',
        ], [
            'reviewer_name.required' => 'আপনার নাম প্রদান করুন।',
            'reviewer_email.required' => 'আপনার ইমেইল ঠিকানা প্রদান করুন।',
            'reviewer_email.email' => 'সঠিক ইমেইল ঠিকানা প্রদান করুন।',
            'body.required' => 'আপনার মতামত ও অভিজ্ঞতা লিখুন।',
            'rating.required' => 'একটি স্টার রেটিং নির্বাচন করুন।',
        ]);

        // Priority: Check logged-in customer guard, then web guard
        $customerUser = auth('customer')->user();
        $webUser = auth('web')->user();

        if ($customerUser) {
            // Find or create a matching User record for the customer
            $email = $customerUser->email ?: ($validated['reviewer_email'] ?? "customer_{$customerUser->id}@noboshop.local");
            $name = $customerUser->name ?: $validated['reviewer_name'];

            $user = User::firstOrCreate(
                ['email' => strtolower(trim($email))],
                [
                    'name' => trim($name),
                    'password' => bcrypt(str()->random(16)),
                ]
            );

            // Update user name if changed
            if ($user->name !== $name) {
                $user->update(['name' => $name]);
            }
        } elseif ($webUser) {
            $user = $webUser;
        } else {
            // Guest submission: find or create User by the provided reviewer_email
            $user = User::firstOrCreate(
                ['email' => strtolower(trim($validated['reviewer_email']))],
                [
                    'name' => trim($validated['reviewer_name']),
                    'password' => bcrypt(str()->random(16)),
                ]
            );

            if ($user->name !== trim($validated['reviewer_name'])) {
                $user->update(['name' => trim($validated['reviewer_name'])]);
            }
        }

        // Check if this user already reviewed this product
        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            $existingReview->update([
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?: "রিভিউ করেছেন: {$user->name}",
                'body' => $validated['body'],
                'is_approved' => false,
            ]);

            return back()->with('success', __('storefront.reviews.thanks'));
        }

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?: "রিভিউ করেছেন: {$user->name}",
            'body' => $validated['body'],
            'is_approved' => false,
        ]);

        return back()->with('success', __('storefront.reviews.thanks'));
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
            return response()->json(['message' => __('storefront.coupon.invalid')], 400);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => __('storefront.coupon.limit')], 400);
        }

        if ($request->subtotal < $coupon->minimum_order_amount) {
            return response()->json(['message' => __('storefront.coupon.minimum', ['amount' => $coupon->minimum_order_amount])], 400);
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
            'message' => __('storefront.coupon.applied'),
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
