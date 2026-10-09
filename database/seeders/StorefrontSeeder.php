<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorefrontSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create or get standard unit
            $unit = Unit::firstOrCreate(
                ['code' => 'pc'],
                [
                    'name' => 'Piece',
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );

            // 2. Create Brand
            $brand = Brand::firstOrCreate(
                ['slug' => 'nobo-shop'],
                [
                    'name' => 'Nobo Shop',
                    'is_active' => true,
                ]
            );

            // 3. Create Categories exactly matching the screenshot
            $categoriesData = [
                ['name' => 'ফ্যাশন', 'slug' => 'fashion', 'sort_order' => 1],
                ['name' => 'এক্সেসরিজ', 'slug' => 'accessories', 'sort_order' => 2],
                ['name' => 'হোম', 'slug' => 'home', 'sort_order' => 3],
                ['name' => 'ইলেকট্রনিক্স', 'slug' => 'electronics', 'sort_order' => 4],
                ['name' => 'ব্যাগ', 'slug' => 'bags', 'sort_order' => 5],
            ];

            $categoryMap = [];
            foreach ($categoriesData as $cat) {
                $category = Category::firstOrCreate(
                    ['slug' => $cat['slug']],
                    [
                        'name' => $cat['name'],
                        'is_active' => true,
                        'sort_order' => $cat['sort_order'],
                    ]
                );
                $categoryMap[$cat['slug']] = $category;
            }

            // 4. Products matching the screenshot
            $productsData = [
                [
                    'name' => 'কটন শার্ট',
                    'slug' => 'cotton-shirt',
                    'category' => 'fashion',
                    'price' => 1250,
                    'compare_at_price' => 1550,
                    'sku' => 'NOBO-FSH-001',
                    'image' => '/images/storefront/product-1.jpg',
                    'short_desc' => 'প্রিমিয়াম ১০০% সুতি কটন শার্ট ও ক্যাজুয়াল ব্যাকপ্যাক সেট।',
                    'is_featured' => true,
                ],
                [
                    'name' => 'ক্লাসিক ঘড়ি',
                    'slug' => 'classic-watch',
                    'category' => 'accessories',
                    'price' => 2450,
                    'compare_at_price' => null,
                    'sku' => 'NOBO-ACC-002',
                    'image' => '/images/storefront/product-2.jpg',
                    'short_desc' => 'জেনুইন লেদার স্ট্র্যাপ সহ মিনিমালিস্ট অ্যানালগ ওয়াচ।',
                    'is_featured' => true,
                ],
                [
                    'name' => 'সিরামিক কাপ ও ল্যাম্প',
                    'slug' => 'ceramic-home-decor',
                    'category' => 'home',
                    'price' => 450,
                    'compare_at_price' => null,
                    'sku' => 'NOBO-HOM-003',
                    'image' => '/images/storefront/product-3.jpg',
                    'short_desc' => 'হ্যান্ডক্রাফটেড সিরামিক ডিজাইন যা ঘরের সৌন্দর্য বাড়িয়ে তোলে।',
                    'is_featured' => true,
                ],
                [
                    'name' => 'স্নিকার্স',
                    'slug' => 'sneakers-shoes',
                    'category' => 'fashion',
                    'price' => 2150,
                    'compare_at_price' => null,
                    'sku' => 'NOBO-FSH-004',
                    'image' => '/images/storefront/product-4.jpg',
                    'short_desc' => 'আরামদায়ক ও ট্রেন্ডি হোয়াইট ক্যাজুয়াল স্নিকার্স।',
                    'is_featured' => true,
                ],
                [
                    'name' => 'হেডফোন ও ক্যামেরা',
                    'slug' => 'headphones-camera',
                    'category' => 'electronics',
                    'price' => 4590,
                    'compare_at_price' => 4990,
                    'sku' => 'NOBO-ELE-005',
                    'image' => '/images/storefront/product-5.jpg',
                    'short_desc' => 'অ্যাকোস্টিক হাই-ফাই সাউন্ড ও ওয়্যারলেস ব্লুটুথ কানেক্টিভিটি।',
                    'is_featured' => true,
                ],
                [
                    'name' => 'ক্যাজুয়াল ব্যাকপ্যাক',
                    'slug' => 'casual-backpack',
                    'category' => 'bags',
                    'price' => 3290,
                    'compare_at_price' => null,
                    'sku' => 'NOBO-BAG-006',
                    'image' => '/images/storefront/product-1.jpg',
                    'short_desc' => 'টেকসই জেনুইন লেদার ও ট্রাভেল ফ্রেন্ডলি ব্যাকপ্যাক।',
                    'is_featured' => true,
                ],
            ];

            foreach ($productsData as $pData) {
                $product = Product::firstOrCreate(
                    ['slug' => $pData['slug']],
                    [
                        'name' => $pData['name'],
                        'brand_id' => $brand->id,
                        'short_description' => $pData['short_desc'],
                        'description' => $pData['short_desc'],
                        'is_active' => true,
                        'is_featured' => $pData['is_featured'],
                    ]
                );

                // Assign category
                if (isset($categoryMap[$pData['category']])) {
                    $product->categories()->syncWithoutDetaching([$categoryMap[$pData['category']]->id]);
                }

                // Default Variant
                $variant = ProductVariant::firstOrCreate(
                    ['sku' => $pData['sku']],
                    [
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                        'name' => $pData['name'].' - Standard',
                        'cost_price' => $pData['price'] * 0.6,
                        'selling_price' => $pData['price'],
                        'compare_at_price' => $pData['compare_at_price'],
                        'unit_quantity' => '1.000',
                        'is_default' => true,
                        'is_active' => true,
                    ]
                );

                // Inventory
                Inventory::firstOrCreate(
                    ['product_variant_id' => $variant->id],
                    [
                        'quantity' => 100,
                        'low_stock_threshold' => 5,
                    ]
                );

                // Product Media
                if ($product->media()->count() === 0) {
                    Media::create([
                        'mediable_type' => Product::class,
                        'mediable_id' => $product->id,
                        'disk' => 'public',
                        'path' => str_replace('/storage/', '', $pData['image']),
                        'original_name' => basename($pData['image']),
                        'mime_type' => 'image/jpeg',
                        'size' => 102400,
                        'alt_text' => $pData['name'],
                        'sort_order' => 1,
                        'is_primary' => true,
                    ]);
                }
            }
        });
    }
}
