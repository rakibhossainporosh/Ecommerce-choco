<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/product/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [StorefrontController::class, 'processCheckout'])->name('checkout.process');
Route::post('/checkout/apply-coupon', [StorefrontController::class, 'applyCoupon'])->name('checkout.apply-coupon');
Route::get('/checkout/success', [StorefrontController::class, 'checkoutSuccess'])->name('checkout.success');
Route::get('/track-order', [StorefrontController::class, 'trackOrder'])->name('track.order');

// Footer Pages
Route::inertia('/about-us', 'Static/About')->name('page.about');
Route::inertia('/contact', 'Static/Contact')->name('page.contact');
Route::inertia('/return-policy', 'Static/ReturnPolicy')->name('page.return-policy');
Route::inertia('/privacy-policy', 'Static/PrivacyPolicy')->name('page.privacy-policy');
Route::inertia('/terms-conditions', 'Static/Terms')->name('page.terms');
Route::inertia('/faq', 'Static/Faq')->name('page.faq');

