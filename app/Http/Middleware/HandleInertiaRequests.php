<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Setting;
use App\Support\CatalogTranslator;
use App\Support\Ga4;
use App\Support\LocaleManager;
use App\Support\LocalizedContent;
use App\Support\MetaPixel;
use App\Support\ReturnPolicySettings;
use App\Support\StorefrontOccasion;
use App\Support\StorefrontTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => fn (): string => app()->getLocale(),
            'meta_pixel' => fn (): array => MetaPixel::share(),
            'ga4' => fn (): array => Ga4::share(),
            'seo' => function () use ($request): ?array {
                $seo = $request->attributes->get('storefront_seo');

                return is_array($seo) ? $seo : null;
            },
            'locales' => fn (): array => LocaleManager::options(),
            'translations' => fn (): array => $this->storefrontTranslations(),
            'global_categories' => function () {
                if (! Schema::hasTable('categories')) {
                    return [];
                }

                return Category::with(['children' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order')->select('id', 'parent_id', 'name', 'slug')->with(['children' => function ($q2) {
                        $q2->where('is_active', true)->orderBy('sort_order')->select('id', 'parent_id', 'name', 'slug');
                    }]);
                }])
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Category $category): array => $this->presentCategory($category))
                    ->all();
            },
            'site_settings' => function () {
                $defaultCol2Links = [
                    ['label' => 'আমাদের সম্পর্কে', 'url' => '/about-us'],
                    ['label' => 'যোগাযোগ', 'url' => '/contact'],
                    ['label' => 'অর্ডার ট্র্যাকিং', 'url' => '/track-order'],
                    ['label' => 'অফার সমূহ', 'url' => '/?offer=1#featured'],
                ];

                $defaultCol3Links = [
                    ['label' => 'রিটার্ন পলিসি', 'url' => '/return-policy'],
                    ['label' => 'প্রাইভেসি পলিসি', 'url' => '/privacy-policy'],
                    ['label' => 'শর্তাবলী', 'url' => '/terms-conditions'],
                    ['label' => 'সাধারণ জিজ্ঞাসা (FAQ)', 'url' => '/faq'],
                ];

                $defaultPaymentMethods = ['bKash', 'Nagad', 'VISA'];

                if (! Schema::hasTable('settings')) {
                    return [
                        'site_name' => 'Nobo Shop',
                        'site_tagline' => 'আপনার প্রতিদিনের পছন্দ',
                        'footer_about' => 'আপনার প্রতিদিনের পছন্দ — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।',
                        'contact_phone' => LocalizedContent::digits('+৮৮০ ৫২৩৪ ৫৬৭৮৯০'),
                        'contact_phone_tel' => LocalizedContent::asciiDigits('+৮৮০ ৫২৩৪ ৫৬৭৮৯০'),
                        'contact_email' => 'support@noboshop.com',
                        'contact_address' => '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ',
                        'facebook_url' => 'https://facebook.com',
                        'twitter_url' => 'https://twitter.com',
                        'instagram_url' => 'https://instagram.com',
                        'youtube_url' => 'https://youtube.com',
                        'footer_col2_title' => 'প্রয়োজনীয় লিংক',
                        'footer_col2_links' => $defaultCol2Links,
                        'footer_col3_title' => 'গ্রাহক সেবা',
                        'footer_col3_links' => $defaultCol3Links,
                        'footer_col4_title' => 'যোগাযোগ',
                        'footer_payment_methods' => $defaultPaymentMethods,
                        'shipping_inside_dhaka' => 60.0,
                        'shipping_outside_dhaka' => 120.0,
                        'return_window_days' => ReturnPolicySettings::windowDays(),
                        'theme' => 'warm',
                    ];
                }

                $col2Links = Setting::get('footer_col2_links');
                if (! is_array($col2Links)) {
                    $col2Links = $defaultCol2Links;
                }

                $col3Links = Setting::get('footer_col3_links');
                if (! is_array($col3Links)) {
                    $col3Links = $defaultCol3Links;
                }

                $paymentMethods = Setting::get('footer_payment_methods');
                if (! is_array($paymentMethods)) {
                    $paymentMethods = $defaultPaymentMethods;
                }

                $logo = Setting::get('site_logo');
                $favicon = Setting::get('site_favicon');

                $defaults = LocalizedContent::settingDefaults();
                $contactPhone = (string) Setting::get('contact_phone', '+৮৮০ ৫২৩৪ ৫৬৭৮৯০');

                return [
                    'site_name' => Setting::get('site_name', 'Nobo Shop'),
                    'site_tagline' => LocalizedContent::text((string) Setting::get('site_tagline', $defaults['site_tagline']['bn']), $defaults['site_tagline']),
                    'site_logo' => $logo ? asset('storage/'.ltrim($logo, '/')) : null,
                    'site_favicon' => $favicon ? asset('storage/'.ltrim($favicon, '/')) : null,
                    'footer_about' => LocalizedContent::text((string) Setting::get('footer_about', $defaults['footer_about']['bn']), $defaults['footer_about']),
                    'contact_phone' => LocalizedContent::digits($contactPhone),
                    'contact_phone_tel' => LocalizedContent::asciiDigits($contactPhone),
                    'contact_email' => Setting::get('contact_email', 'support@noboshop.com'),
                    'contact_address' => LocalizedContent::text((string) Setting::get('contact_address', $defaults['contact_address']['bn']), $defaults['contact_address']),
                    'facebook_url' => Setting::get('facebook_url', 'https://facebook.com'),
                    'twitter_url' => Setting::get('twitter_url', 'https://twitter.com'),
                    'instagram_url' => Setting::get('instagram_url', 'https://instagram.com'),
                    'youtube_url' => Setting::get('youtube_url', 'https://youtube.com'),
                    'footer_col2_title' => LocalizedContent::text((string) Setting::get('footer_col2_title', $defaults['footer_col2_title']['bn']), $defaults['footer_col2_title']),
                    'footer_col2_links' => LocalizedContent::links($col2Links, LocalizedContent::linkDefaults()),
                    'footer_col3_title' => LocalizedContent::text((string) Setting::get('footer_col3_title', $defaults['footer_col3_title']['bn']), $defaults['footer_col3_title']),
                    'footer_col3_links' => LocalizedContent::links($col3Links, LocalizedContent::linkDefaults()),
                    'footer_col4_title' => LocalizedContent::text((string) Setting::get('footer_col4_title', $defaults['footer_col4_title']['bn']), $defaults['footer_col4_title']),
                    'footer_payment_methods' => $paymentMethods,
                    'shipping_inside_dhaka' => (float) Setting::get('shipping_inside_dhaka', 60),
                    'shipping_outside_dhaka' => (float) Setting::get('shipping_outside_dhaka', 120),
                    'return_window_days' => ReturnPolicySettings::windowDays(),
                    'occasion' => StorefrontOccasion::present(),
                    'theme' => StorefrontTheme::key(),
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ];
            },
            'customer' => function () {
                $customer = auth('customer')->user();

                return $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ] : null;
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'display_name' => CatalogTranslator::category($category->slug, $category->name),
            'children' => $category->children
                ->map(fn (Category $child): array => $this->presentCategory($child))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function storefrontTranslations(): array
    {
        $path = lang_path(app()->getLocale().'/storefront.php');

        if (! is_file($path)) {
            $path = lang_path('en/storefront.php');
        }

        $lines = require $path;

        return is_array($lines) ? $lines : [];
    }
}
