<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/product/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [StorefrontController::class, 'processCheckout'])->name('checkout.process');

