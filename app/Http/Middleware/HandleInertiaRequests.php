<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Setting;
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
                    ->get(['id', 'name', 'slug']);
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
                        'contact_phone' => '+৮৮০ ৫২৩৪ ৫৬৭৮৯০',
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
                        'footer_copyright' => '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।',
                        'footer_payment_methods' => $defaultPaymentMethods,
                        'shipping_inside_dhaka' => 60.0,
                        'shipping_outside_dhaka' => 120.0,
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

                return [
                    'site_name' => Setting::get('site_name', 'Nobo Shop'),
                    'site_tagline' => Setting::get('site_tagline', 'আপনার প্রতিদিনের পছন্দ'),
                    'footer_about' => Setting::get('footer_about', 'আপনার প্রতিদিনের পছন্দ — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।'),
                    'contact_phone' => Setting::get('contact_phone', '+৮৮০ ৫২৩৪ ৫৬৭৮৯০'),
                    'contact_email' => Setting::get('contact_email', 'support@noboshop.com'),
                    'contact_address' => Setting::get('contact_address', '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ'),
                    'facebook_url' => Setting::get('facebook_url', 'https://facebook.com'),
                    'twitter_url' => Setting::get('twitter_url', 'https://twitter.com'),
                    'instagram_url' => Setting::get('instagram_url', 'https://instagram.com'),
                    'youtube_url' => Setting::get('youtube_url', 'https://youtube.com'),
                    'footer_col2_title' => Setting::get('footer_col2_title', 'প্রয়োজনীয় লিংক'),
                    'footer_col2_links' => $col2Links,
                    'footer_col3_title' => Setting::get('footer_col3_title', 'গ্রাহক সেবা'),
                    'footer_col3_links' => $col3Links,
                    'footer_col4_title' => Setting::get('footer_col4_title', 'যোগাযোগ'),
                    'footer_copyright' => Setting::get('footer_copyright', '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।'),
                    'footer_payment_methods' => $paymentMethods,
                    'shipping_inside_dhaka' => (float) Setting::get('shipping_inside_dhaka', 60),
                    'shipping_outside_dhaka' => (float) Setting::get('shipping_outside_dhaka', 120),
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
}
