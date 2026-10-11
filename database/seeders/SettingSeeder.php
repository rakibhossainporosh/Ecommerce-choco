<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General
            [
                'key' => 'site_name',
                'label' => 'Site Name',
                'value' => 'Nobo Shop',
                'type' => SettingType::String,
                'group' => 'general',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'site_tagline',
                'label' => 'Site Tagline',
                'value' => 'আপনার প্রতিদিনের পছন্দ',
                'type' => SettingType::String,
                'group' => 'general',
                'is_public' => true,
                'is_system' => false,
            ],
            // Footer Brand & Description
            [
                'key' => 'footer_about',
                'label' => 'Footer About Description',
                'value' => 'আপনার প্রতিদিনের পছন্দ — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।',
                'type' => SettingType::String,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            // Social Media
            [
                'key' => 'facebook_url',
                'label' => 'Facebook URL',
                'value' => 'https://facebook.com',
                'type' => SettingType::String,
                'group' => 'social',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'twitter_url',
                'label' => 'Twitter / X URL',
                'value' => 'https://twitter.com',
                'type' => SettingType::String,
                'group' => 'social',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'instagram_url',
                'label' => 'Instagram URL',
                'value' => 'https://instagram.com',
                'type' => SettingType::String,
                'group' => 'social',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'youtube_url',
                'label' => 'YouTube URL',
                'value' => 'https://youtube.com',
                'type' => SettingType::String,
                'group' => 'social',
                'is_public' => true,
                'is_system' => false,
            ],
            // Footer Column 2: Useful Links
            [
                'key' => 'footer_col2_title',
                'label' => 'Footer Column 2 Title',
                'value' => 'প্রয়োজনীয় লিংক',
                'type' => SettingType::String,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'footer_col2_links',
                'label' => 'Footer Column 2 Links',
                'value' => json_encode([
                    ['label' => 'আমাদের সম্পর্কে', 'url' => '/about-us'],
                    ['label' => 'যোগাযোগ', 'url' => '/contact'],
                    ['label' => 'অর্ডার ট্র্যাকিং', 'url' => '/track-order'],
                    ['label' => 'অফার সমূহ', 'url' => '/?offer=1#featured'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => SettingType::Json,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            // Footer Column 3: Customer Service
            [
                'key' => 'footer_col3_title',
                'label' => 'Footer Column 3 Title',
                'value' => 'গ্রাহক সেবা',
                'type' => SettingType::String,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'footer_col3_links',
                'label' => 'Footer Column 3 Links',
                'value' => json_encode([
                    ['label' => 'রিটার্ন পলিসি', 'url' => '/return-policy'],
                    ['label' => 'প্রাইভেসি পলিসি', 'url' => '/privacy-policy'],
                    ['label' => 'শর্তাবলী', 'url' => '/terms-conditions'],
                    ['label' => 'সাধারণ জিজ্ঞাসা (FAQ)', 'url' => '/faq'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => SettingType::Json,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            // Footer Column 4: Contact Info
            [
                'key' => 'footer_col4_title',
                'label' => 'Footer Column 4 Title',
                'value' => 'যোগাযোগ',
                'type' => SettingType::String,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'contact_address',
                'label' => 'Contact Address',
                'value' => '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ',
                'type' => SettingType::String,
                'group' => 'contact',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'contact_phone',
                'label' => 'Contact Phone',
                'value' => '+৮৮০ ৫২৩৪ ৫৬৭৮৯০',
                'type' => SettingType::String,
                'group' => 'contact',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'contact_email',
                'label' => 'Contact Email',
                'value' => 'support@noboshop.com',
                'type' => SettingType::String,
                'group' => 'contact',
                'is_public' => true,
                'is_system' => false,
            ],
            // Footer Bottom: Copyright & Payment Methods
            [
                'key' => 'footer_copyright',
                'label' => 'Footer Copyright Text',
                'value' => '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।',
                'type' => SettingType::String,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'footer_payment_methods',
                'label' => 'Footer Payment Methods',
                'value' => json_encode(['bKash', 'Nagad', 'VISA'], JSON_UNESCAPED_UNICODE),
                'type' => SettingType::Json,
                'group' => 'footer',
                'is_public' => true,
                'is_system' => false,
            ],
            // Shipping rates
            [
                'key' => 'shipping_inside_dhaka',
                'label' => 'Shipping Inside Dhaka',
                'value' => '60',
                'type' => SettingType::Float,
                'group' => 'shipping',
                'is_public' => true,
                'is_system' => false,
            ],
            [
                'key' => 'shipping_outside_dhaka',
                'label' => 'Shipping Outside Dhaka',
                'value' => '120',
                'type' => SettingType::Float,
                'group' => 'shipping',
                'is_public' => true,
                'is_system' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
            Cache::forget("setting.{$setting['key']}");
        }
    }
}
