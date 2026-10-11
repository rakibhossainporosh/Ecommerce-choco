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
                if (! Schema::hasTable('settings')) {
                    return [
                        'site_name' => 'Nobo Shop',
                        'site_tagline' => 'আপনার প্রতিদিনের পছন্দ',
                        'contact_phone' => '+৮৮০ ১২৩৪ ৫৬৭৮৯০',
                        'contact_email' => 'support@noboshop.com',
                        'contact_address' => '১২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ',
                        'facebook_url' => '#',
                        'twitter_url' => '#',
                        'instagram_url' => '#',
                        'youtube_url' => '#',
                        'shipping_inside_dhaka' => 60.0,
                        'shipping_outside_dhaka' => 120.0,
                    ];
                }

                return [
                    'site_name' => Setting::get('site_name', 'Nobo Shop'),
                    'site_tagline' => Setting::get('site_tagline', 'আপনার প্রতিদিনের পছন্দ'),
                    'contact_phone' => Setting::get('contact_phone', '+৮৮০ ১২৩৪ ৫৬৭৮৯০'),
                    'contact_email' => Setting::get('contact_email', 'support@noboshop.com'),
                    'contact_address' => Setting::get('contact_address', '১২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ'),
                    'facebook_url' => Setting::get('facebook_url', '#'),
                    'twitter_url' => Setting::get('twitter_url', '#'),
                    'instagram_url' => Setting::get('instagram_url', '#'),
                    'youtube_url' => Setting::get('youtube_url', '#'),
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
