<?php

use App\Filament\Pages\FooterSettings;
use App\Filament\Pages\StockOverview;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Attributes\Pages\ListAttributes;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Couriers\Pages\CreateCourier;
use App\Filament\Resources\CourierShipments\Pages\CreateCourierShipment;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('bangla admin pages do not leave common english labels visible', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);
    Setting::query()->where('key', 'site_locale')->update(['value' => 'bn']);
    cache()->forget('setting.site_locale');
    config(['app.locale' => 'bn', 'app.fallback_locale' => 'en']);
    app()->setLocale('bn');
    Carbon::setLocale('bn');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $order = Order::factory()->create([
        'order_number' => 'ORD-100001',
        'customer_name' => 'করিম',
    ]);

    $html = '';

    foreach ([
        Dashboard::class,
        ListOrders::class,
        ListProducts::class,
        CreateProduct::class,
        ListCustomers::class,
        ListCategories::class,
        ListBrands::class,
        ListAttributes::class,
        ListCoupons::class,
        ListPayments::class,
        ListShipments::class,
        ListReturnRequests::class,
        ListReviews::class,
        FooterSettings::class,
        StockOverview::class,
        ListSettings::class,
        ListActivities::class,
        CreateCoupon::class,
        CreateReview::class,
        CreateCourier::class,
        CreateCourierShipment::class,
    ] as $page) {
        $html .= Livewire::test($page)->html();
    }

    $html .= Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->html();

    expect($html)
        ->toContain('সব অর্ডার')
        ->toContain('সক্রিয়')
        ->toContain('সাধারণ তথ্য')
        ->toContain('সবাই দেখতে পাবে')
        ->toContain('ট্র্যাকিং নম্বর')
        ->toContain('কমপক্ষে কত টাকার অর্ডার')
        ->toContain('তথ্যের নাম')
        ->toContain('ফাইল এখানে টেনে আনুন')
        ->toContain('দিন/মাস/বছর, সময়')
        ->toContain('অর্ডার #ORD-100001')
        ->toContain('অর্ডারের সময়')
        ->toContain('গ্রাহক: করিম')
        ->toContain('লিংকের নাম')
        ->not->toContain('Order #ORD-100001')
        ->not->toContain('Placed on')
        ->not->toContain('ইউআরএল স্লাগ')
        ->not->toContain('All Orders')
        ->not->toContain('Is public')
        ->not->toContain('Tracking number')
        ->not->toContain('Minimum order amount')
        ->not->toContain('Credential Key')
        ->not->toContain('Drag & Drop')
        ->not->toContain('mm/dd/yyyy')
        ->not->toContain('General Information')
        ->not->toContain('filament-tables::table.result_count')
        ->not->toContain('>Active<')
        ->not->toContain('product variants');

    expect(__('Are you sure you want to refund payment :number for ৳:amount?', [
        'number' => 'PAY-1',
        'amount' => '1,250.00',
    ]))->toBe('পেমেন্ট PAY-1-এর ৳1,250.00 ফেরত দেবেন?');

    $styles = Blade::render("@include('filament.styles.admin-layout')");

    expect($styles)
        ->toContain('content: "কাজ"')
        ->not->toContain('content: "Actions"');
});
