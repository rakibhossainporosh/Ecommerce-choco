<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Setting;
use App\Models\User;
use App\Support\LocaleManager;
use App\Support\UiText;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);
});

test('storefront uses english until a shopper or the admin chooses bangla', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('translations.header.home', 'Home')
            ->where('translations.checkout.place_order', 'Place order')
            ->where('site_settings.contact_phone', '+880 5234 567890')
        );
});

test('shopper language choice is remembered and does not change the store default', function () {
    $this->post('/locale', ['locale' => 'bn'])
        ->assertRedirect()
        ->assertCookie(LocaleManager::COOKIE, 'bn');

    expect(Setting::get('site_locale'))->toBe('en');

    $this->withCookie(LocaleManager::COOKIE, 'bn')
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'bn')
            ->where('translations.header.home', 'হোম')
            ->where('translations.checkout.place_order', 'অর্ডার করুন')
            ->where('site_settings.contact_phone', '+৮৮০ ৫২৩৪ ৫৬৭৮৯০')
        );
});

test('store default language is used when the shopper has not chosen one', function () {
    Setting::query()->where('key', 'site_locale')->update(['value' => 'bn']);
    Setting::query()->get()->each(fn (Setting $setting) => cache()->forget("setting.{$setting->key}"));

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'bn'));
});

test('a shopper language choice overrides the store default on the website only', function () {
    Setting::query()->where('key', 'site_locale')->update(['value' => 'bn']);
    cache()->forget('setting.site_locale');

    $this->withCookie(LocaleManager::COOKIE, 'en')
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

test('rejects an unsupported storefront language', function () {
    $this->post('/locale', ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('admin language control sits before the account menu', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $html = $this->actingAs($admin)->get('/admin')->assertSuccessful()->getContent();
    $switcher = strpos($html, 'admin-locale');
    $account = strpos($html, 'fi-user-menu');

    expect($switcher)->toBeInt()
        ->and($account)->toBeInt()
        ->and($switcher)->toBeLessThan($account)
        ->and($html)->toContain('name="locale"')
        ->and($html)->toContain('English')
        ->and($html)->toContain('বাংলা')
        ->and($html)->toContain('admin-locale-profile')
        ->and($html)->not->toContain('admin-locale-switch');
});

test('admin can set the language for the admin panel and the website default', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->post('/admin/site-locale', ['locale' => 'bn'])
        ->assertRedirect();

    expect(Setting::get('site_locale'))->toBe('bn');
});

test('staff cannot change the store language', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    $this->actingAs($staff)
        ->post('/admin/site-locale', ['locale' => 'bn'])
        ->assertForbidden();

    expect(Setting::get('site_locale'))->toBe('en');
});

test('admin labels and breadcrumbs use plain bangla when the store language is bangla', function () {
    app()->setLocale('bn');

    expect(UiText::line('Dashboard'))->toBe('ড্যাশবোর্ড')
        ->and(OrderResource::getBreadcrumb())->toBe('অর্ডার')
        ->and(UiText::line('Customer Since'))->toBe('গ্রাহক হয়েছেন')
        ->and(__('Shipping Address'))->toBe('ডেলিভারি ঠিকানা')
        ->and(__('All Orders'))->toBe('সব অর্ডার');
});

test('guests are sent to the admin sign in page when changing the store language', function () {
    $this->post('/admin/site-locale', ['locale' => 'bn'])
        ->assertRedirect('/admin/login');
});
