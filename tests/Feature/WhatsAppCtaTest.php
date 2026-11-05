<?php

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

test('storefront shares normalized whatsapp number from settings', function () {
    Cache::forget('setting.whatsapp_number');
    Cache::forget('setting.whatsapp_message');
    Cache::forget('setting.contact_phone');

    Setting::updateOrCreate(
        ['key' => 'whatsapp_number'],
        [
            'label' => 'WhatsApp number',
            'value' => '+880 1711-223344',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ]
    );

    Setting::updateOrCreate(
        ['key' => 'whatsapp_message'],
        [
            'label' => 'WhatsApp default message',
            'value' => 'Need help with shipping',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ]
    );

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('site_settings.whatsapp_number', '8801711223344')
            ->where('site_settings.whatsapp_message', 'Need help with shipping')
        );
});
