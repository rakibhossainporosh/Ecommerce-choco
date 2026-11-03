<?php

use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Http\Controllers\Admin\SiteLocaleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/', [StorefrontController::class, 'index'])->name('home');
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
Route::get('/track-order', [StorefrontController::class, 'trackOrder'])->name('track.order');

// Customer Authentication & Account Portal
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('customer.login.submit');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1')->name('customer.register.submit');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
Route::get('/account', [CustomerAuthController::class, 'showAccount'])->name('customer.account');
Route::post('/account/profile', [CustomerAuthController::class, 'updateProfile'])->middleware('throttle:15,1')->name('customer.profile.update');
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
