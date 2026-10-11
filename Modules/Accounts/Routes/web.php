<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Accounts\Http\Controllers\Storefront\AccountController;
use Modules\Accounts\Http\Controllers\Storefront\AuthController;
use Modules\Cart\Http\Controllers\CartController;
use Modules\Cart\Http\Controllers\WishlistController;
use Modules\Orders\Http\Controllers\OrderController;

Route::middleware('web')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/lupa-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password.email')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.submit');

    Route::middleware('auth')->group(function () {
        Route::get('/akun', [AccountController::class, 'show'])->name('account');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
        Route::get('/lacak-pesanan', [OrderController::class, 'track'])->name('orders.track');
        Route::post('/checkout', [OrderController::class, 'store'])->name('checkout.store');
        Route::get('/pembayaran/{number}', [OrderController::class, 'payment'])->name('payment');
        Route::post('/pembayaran/{number}/saya-sudah-bayar', [OrderController::class, 'paid'])->name('payment.paid');
        Route::get('/sukses/{number}', [OrderController::class, 'success'])->name('success');
        Route::post('/akun/alamat', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
        Route::put('/akun/alamat/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
        Route::delete('/akun/alamat/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
        Route::post('/akun/alamat/{address}/utama', [AccountController::class, 'makeAddressDefault'])->name('account.addresses.default');
    });

    Route::get('/keranjang', [CartController::class, 'show'])->name('cart');
    Route::post('/cart/items', [CartController::class, 'add'])->name('cart.items.add');
    Route::put('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.items.remove');
    Route::post('/cart/promo', [CartController::class, 'applyPromo'])->name('cart.promo.apply');
    Route::delete('/cart/promo', [CartController::class, 'removePromo'])->name('cart.promo.remove');
});
