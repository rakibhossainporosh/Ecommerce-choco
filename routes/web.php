<?php

use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Http\Controllers\Admin\SiteLocaleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\CustomerWishlistController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StorefrontSeoController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/robots.txt', [StorefrontSeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [StorefrontSeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category.show');
Route::get('/search/suggestions', [StorefrontController::class, 'suggestions'])->name('search.suggestions');
Route::get('/product/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::post('/product/{slug}/reviews', [StorefrontController::class, 'submitReview'])->middleware('throttle:15,1')->name('product.review.submit');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [StorefrontController::class, 'processCheckout'])->middleware('throttle:15,1')->name('checkout.process');
Route::post('/cart/remember', [CartController::class, 'remember'])->middleware('throttle:30,1')->name('cart.remember');
Route::get('/cart/recover/{token}', [CartController::class, 'recover'])->where('token', '[A-Za-z0-9]{40}')->name('cart.recover');
Route::post('/checkout/apply-coupon', [StorefrontController::class, 'applyCoupon'])->middleware('throttle:20,1')->name('checkout.apply-coupon');
Route::get('/checkout/success', [StorefrontController::class, 'checkoutSuccess'])->name('checkout.success');
Route::get('/payments/{gateway}/{outcome}', [PaymentReturnController::class, 'returned'])
    ->where('gateway', '[a-z0-9_]{2,30}')
    ->where('outcome', 'success|fail|cancel')
    ->name('payments.return');
Route::post('/payments/{gateway}/ipn', [PaymentReturnController::class, 'ipn'])
    ->where('gateway', '[a-z0-9_]{2,30}')
    ->middleware('throttle:60,1')
    ->name('payments.ipn');
Route::get('/track-order', [StorefrontController::class, 'trackOrder'])->middleware('throttle:20,1')->name('track.order');

// Customer Authentication & Account Portal
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('customer.login.submit');
Route::post('/login/otp', [CustomerAuthController::class, 'sendLoginOtp'])->middleware('throttle:5,1')->name('customer.login.otp.send');
Route::post('/login/otp/verify', [CustomerAuthController::class, 'verifyLoginOtp'])->middleware('throttle:10,1')->name('customer.login.otp.verify');
Route::get('/forgot-password', [CustomerAuthController::class, 'showForgotPassword'])->name('customer.password.forgot');
Route::post('/forgot-password', [CustomerAuthController::class, 'sendPasswordResetCode'])->middleware('throttle:5,1')->name('customer.password.forgot.submit');
Route::get('/reset-password', [CustomerAuthController::class, 'showResetPassword'])->name('customer.password.reset');
Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword'])->middleware('throttle:10,1')->name('customer.password.reset.submit');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1')->name('customer.register.submit');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
Route::get('/account', [CustomerAuthController::class, 'showAccount'])->name('customer.account');
Route::post('/account/profile', [CustomerAuthController::class, 'updateProfile'])->middleware('throttle:15,1')->name('customer.profile.update');
Route::post('/account/addresses', [CustomerAddressController::class, 'store'])->middleware('throttle:15,1')->name('customer.addresses.store');
Route::put('/account/addresses/{address}', [CustomerAddressController::class, 'update'])->middleware('throttle:15,1')->name('customer.addresses.update');
Route::delete('/account/addresses/{address}', [CustomerAddressController::class, 'destroy'])->middleware('throttle:15,1')->name('customer.addresses.destroy');
Route::post('/account/addresses/{address}/default', [CustomerAddressController::class, 'makeDefault'])->middleware('throttle:15,1')->name('customer.addresses.default');
Route::get('/account/wishlist', [CustomerWishlistController::class, 'index'])->middleware('throttle:60,1')->name('customer.wishlist.index');
Route::post('/account/wishlist/toggle', [CustomerWishlistController::class, 'toggle'])->middleware('throttle:30,1')->name('customer.wishlist.toggle');
Route::post('/account/wishlist/sync', [CustomerWishlistController::class, 'sync'])->middleware('throttle:20,1')->name('customer.wishlist.sync');
Route::get('/account/orders/{order}/invoice', [CustomerInvoiceController::class, 'show'])->middleware('throttle:30,1')->name('customer.orders.invoice');
Route::post('/account/orders/{order}/returns', [CustomerReturnController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('customer.orders.returns.store');
Route::delete('/account/returns/{returnRequest}', [CustomerReturnController::class, 'destroy'])
    ->middleware('throttle:10,1')
    ->name('customer.returns.destroy');
Route::post('/account/notifications/{notification}/read', [CustomerAuthController::class, 'markNotificationRead'])
    ->middleware('throttle:30,1')
    ->name('customer.notifications.read');
Route::post('/account/notifications/read-all', [CustomerAuthController::class, 'markAllNotificationsRead'])
    ->middleware('throttle:15,1')
    ->name('customer.notifications.read-all');

// Footer & Static Pages
Route::inertia('/about-us', 'Static/About')->name('page.about');
Route::inertia('/contact', 'Static/Contact')->name('page.contact');
Route::post('/contact', [StorefrontController::class, 'submitContact'])->middleware('throttle:15,1')->name('contact.submit');
Route::inertia('/return-policy', 'Static/ReturnPolicy')->name('page.return-policy');
Route::inertia('/privacy-policy', 'Static/PrivacyPolicy')->name('page.privacy-policy');
Route::inertia('/terms-conditions', 'Static/Terms')->name('page.terms');
Route::inertia('/faq', 'Static/Faq')->name('page.faq');

Route::post('/admin/site-locale', [SiteLocaleController::class, 'update'])
    ->middleware(['auth'])
    ->name('admin.site-locale.update');

// Admin Printable Order Invoice
Route::get('/admin/orders/{order}/invoice', [OrderInvoiceController::class, 'show'])
    ->middleware(['auth'])
    ->name('admin.orders.invoice');

Route::get('/admin/orders/{order}/packing-slip', [OrderInvoiceController::class, 'packingSlip'])
    ->middleware(['auth'])
    ->name('admin.orders.packing-slip');
