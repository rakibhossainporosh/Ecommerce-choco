<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class StorefrontSeo
{
    /**
     * Build the tags for the current storefront request.
     *
     * @return array{
     *     title: string,
     *     document_title: string,
     *     description: string,
     *     canonical: string,
     *     robots: string,
     *     image: ?string,
     *     type: string,
     *     site_name: string,
     *     locale: string,
     *     price: ?string,
     *     currency: ?string,
     *     json_ld: ?array<string, mixed>
     * }
     */
    public static function for(Request $request): array
    {
        $siteName = self::siteName();
        $locale = app()->getLocale() === 'bn' ? 'bn_BD' : 'en_US';
        $routeName = $request->route()?->getName();

        $page = match ($routeName) {
            'home', 'category.show' => self::home($request, $siteName),
            'product.show' => self::product($request, $siteName),
            'page.about' => self::content('about', $siteName),
            'page.contact' => self::content('contact', $siteName),
            'page.faq' => self::content('faq', $siteName),
            'page.privacy-policy' => self::content('privacy', $siteName),
            'page.terms' => self::content('terms', $siteName),
            'page.return-policy' => self::content('returns', $siteName),
            'customer.login' => self::utility('login'),
            'customer.register' => self::utility('register'),
            'customer.account' => self::utility('account'),
            'checkout.index' => self::utility('checkout'),
            'checkout.success' => self::utility('success'),
            'track.order' => self::utility('track'),
            default => self::utility(null),
        };

        $title = self::plain($page['title'], 70);
        $documentTitle = $title === '' || strcasecmp($title, $siteName) === 0
            ? $siteName
            : $title.' - '.$siteName;

        return [
            'title' => $documentTitle === $siteName ? '' : $title,
            'document_title' => $documentTitle,
            'description' => self::plain($page['description'], 160),
            'canonical' => $page['canonical'],
            'robots' => $page['robots'],
            'image' => $page['image'],
            'type' => $page['type'],
            'site_name' => $siteName,
            'locale' => $locale,
            'price' => $page['price'],
            'currency' => $page['currency'],
            'json_ld' => $page['json_ld'],
        ];
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string, image: ?string, type: string, price: ?string, currency: ?string, json_ld: ?array<string, mixed>}
     */
    private static function home(Request $request, string $siteName): array
    {
        $categorySlug = trim((string) ($request->route('slug') ?? $request->query('category', 'all')));
        $search = trim((string) $request->query('search', ''));
        $sort = trim((string) $request->query('sort', 'newest'));
        $offer = $request->boolean('offer');
        $page = max(1, (int) $request->query('page', 1));
        $filtered = $search !== '' || $offer || ($sort !== '' && $sort !== 'newest') || $page > 1;

        $category = null;

        if ($categorySlug !== '' && $categorySlug !== 'all' && Schema::hasTable('categories')) {
            $category = Category::query()
                ->where('slug', $categorySlug)
                ->where('is_active', true)
                ->first(['id', 'name', 'slug', 'description']);
        }

        $tagline = self::plain((string) Setting::get('site_tagline', ''));
        $description = $tagline !== ''
            ? $tagline
            : __('storefront.meta.descriptions.home', ['store' => $siteName]);

        $title = '';
        $canonical = url('/');
        $robots = 'index, follow';

        if ($category instanceof Category) {
            $categoryName = CatalogTranslator::category($category->slug, $category->name);
            $title = $categoryName;
            $canonical = route('category.show', ['slug' => $category->slug]);
            $categoryDescription = self::plain($category->description);

            $description = $categoryDescription !== ''
                ? $categoryDescription
                : __('storefront.meta.descriptions.category', ['name' => $categoryName, 'store' => $siteName]);
        } elseif ($categorySlug !== '' && $categorySlug !== 'all') {
            $filtered = true;
        }

        if ($filtered) {
            $robots = 'noindex, follow';
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'image' => self::logo(),
            'type' => 'website',
            'price' => null,
            'currency' => null,
            'json_ld' => $robots === 'index, follow' ? self::websiteGraph($siteName, $description) : null,
        ];
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string, image: ?string, type: string, price: ?string, currency: ?string, json_ld: ?array<string, mixed>}
     */
    private static function product(Request $request, string $siteName): array
    {
        $slug = (string) $request->route('slug');
        $product = Schema::hasTable('products')
            ? Product::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->with([
                    'brand:id,name',
                    'defaultVariant:id,product_id,sku,selling_price,is_default',
                    'defaultVariant.inventory',
                    'media:id,mediable_type,mediable_id,path,is_primary,sort_order',
                ])
                ->withAvg(['reviews' => fn ($query) => $query->where('is_approved', true)], 'rating')
                ->withCount(['reviews' => fn ($query) => $query->where('is_approved', true)])
                ->first()
            : null;

        $canonical = route('product.show', ['slug' => $slug]);

        if (! $product instanceof Product) {
            return self::utility(null);
        }

        $name = CatalogTranslator::product($product->slug, 'name', $product->name);
        $title = self::plain($product->meta_title) !== '' ? self::plain($product->meta_title, 70) : $name;
        $short = CatalogTranslator::product($product->slug, 'short_description', $product->short_description);
        $description = self::plain($product->meta_description) !== ''
            ? self::plain($product->meta_description)
            : ($short !== '' ? $short : __('storefront.meta.descriptions.product', ['name' => $name, 'store' => $siteName]));

        $image = self::productImage($product);
        $variant = $product->defaultVariant;
        $price = $variant ? number_format((float) $variant->selling_price, 2, '.', '') : null;
        $currency = $price === null ? null : (string) config('currency.code', 'BDT');
        $inStock = $variant?->inventory ? $variant->inventory->quantity > 0 : true;

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => self::plain($description, 500),
            'url' => $canonical,
            'sku' => $variant?->sku ?: $product->slug,
        ];

        if ($image !== null) {
            $jsonLd['image'] = [$image];
        }

        if (filled($product->brand?->name)) {
            $jsonLd['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        if ($price !== null && $currency !== null) {
            $jsonLd['offers'] = [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => $currency,
                'price' => $price,
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ];
        }

        $reviewCount = (int) $product->reviews_count;
        $rating = round((float) $product->reviews_avg_rating, 1);

        if ($reviewCount > 0 && $rating >= 1 && $rating <= 5) {
            $jsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $rating,
                'reviewCount' => $reviewCount,
            ];
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'image' => $image,
            'type' => 'product',
            'price' => $price,
            'currency' => $currency,
            'json_ld' => $jsonLd,
        ];
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string, image: ?string, type: string, price: ?string, currency: ?string, json_ld: null}
     */
    private static function content(string $key, string $siteName): array
    {
        return [
            'title' => __('storefront.meta.'.$key),
            'description' => __('storefront.meta.descriptions.'.$key, ['store' => $siteName]),
            'canonical' => url()->current(),
            'robots' => 'index, follow',
            'image' => self::logo(),
            'type' => 'website',
            'price' => null,
            'currency' => null,
            'json_ld' => null,
        ];
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string, image: ?string, type: string, price: ?string, currency: ?string, json_ld: null}
     */
    private static function utility(?string $key): array
    {
        return [
            'title' => $key === null ? '' : (string) __('storefront.meta.'.$key),
            'description' => __('storefront.meta.descriptions.private'),
            'canonical' => url()->current(),
            'robots' => 'noindex, nofollow',
            'image' => null,
            'type' => 'website',
            'price' => null,
            'currency' => null,
            'json_ld' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function websiteGraph(string $siteName, string $description): array
    {
        $home = url('/');
        $graph = [
            [
                '@type' => 'WebSite',
                '@id' => $home.'#website',
                'name' => $siteName,
                'url' => $home,
                'description' => $description,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $home.'?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        $organization = [
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $home,
        ];
        $logo = self::logo();

        if ($logo !== null) {
            $organization['logo'] = $logo;
        }

        $graph[] = $organization;

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    private static function productImage(Product $product): ?string
    {
        $media = $product->media->firstWhere('is_primary', true) ?? $product->media->first();

        if ($media !== null && filled($media->path)) {
            return asset('storage/'.ltrim((string) $media->path, '/'));
        }

        return url('/images/storefront/product-1.jpg');
    }

    private static function logo(): ?string
    {
        $logo = Setting::get('site_logo');

        if (! is_string($logo) || $logo === '') {
            return null;
        }

        return asset('storage/'.ltrim($logo, '/'));
    }

    private static function siteName(): string
    {
        $name = (string) Setting::get('site_name', config('app.name', 'Nobo Shop'));

        return $name !== '' ? $name : (string) config('app.name', 'Nobo Shop');
    }

    private static function plain(?string $value, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        return Str::limit($text, $limit, '');
    }
}
