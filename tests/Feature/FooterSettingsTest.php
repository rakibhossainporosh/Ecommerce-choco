<?php

use App\Filament\Pages\FooterSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->staff = User::factory()->create();
    $this->staff->assignRole('Staff');
});

test('storefront receives all dynamic footer settings', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('site_settings', fn (Assert $settings) => $settings
            ->where('site_name', 'Nobo Shop')
            ->where('footer_col2_title', 'প্রয়োজনীয় লিংক')
            ->where('footer_col3_title', 'গ্রাহক সেবা')
            ->where('footer_col4_title', 'যোগাযোগ')
            ->where('contact_phone', '+৮৮০ ৫২৩৪ ৫৬৭৮৯০')
            ->where('contact_email', 'support@noboshop.com')
            ->where('contact_address', '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ')
            ->has('footer_col2_links', 4)
            ->has('footer_col3_links', 4)
            ->has('footer_payment_methods', 3)
            ->etc()
        )
    );
});

test('admin can access footer settings page and save new values', function () {
    $this->actingAs($this->admin);

    Livewire::test(FooterSettings::class)
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'site_name' => 'Nobo Shop',
            'footer_col2_title' => 'প্রয়োজনীয় লিংক',
        ])
        ->fillForm([
            'site_name' => 'মডেল শপ',
            'footer_about' => 'কাস্টম ফুটার বিবরণ টেক্সট',
            'footer_col2_title' => 'জরুরি লিংক',
            'contact_phone' => '+8801999999999',
            'contact_email' => 'custom@shop.com',
            'contact_address' => 'ঢাকা, বাংলাদেশ',
            'footer_copyright' => '© {year} Model Shop All Rights Reserved.',
            'footer_payment_methods' => ['bKash', 'Rocket', 'Upay'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('site_name'))->toBe('মডেল শপ')
        ->and(Setting::get('footer_about'))->toBe('কাস্টম ফুটার বিবরণ টেক্সট')
        ->and(Setting::get('footer_col2_title'))->toBe('জরুরি লিংক')
        ->and(Setting::get('contact_phone'))->toBe('+8801999999999')
        ->and(Setting::get('footer_payment_methods'))->toBe(['bKash', 'Rocket', 'Upay']);

    // Check storefront reflects updated values
    $response = $this->get('/');
    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('site_settings.site_name', 'মডেল শপ')
        ->where('site_settings.footer_about', 'কাস্টম ফুটার বিবরণ টেক্সট')
        ->where('site_settings.footer_col2_title', 'জরুরি লিংক')
        ->where('site_settings.contact_phone', '+8801999999999')
        ->where('site_settings.footer_payment_methods', ['bKash', 'Rocket', 'Upay'])
    );
});

test('unauthorized user cannot access footer settings page', function () {
    $this->actingAs($this->staff);

    Livewire::test(FooterSettings::class)
        ->assertForbidden();
});
