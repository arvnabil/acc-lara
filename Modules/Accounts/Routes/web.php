<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Accounts\Http\Controllers\Storefront\AccountController;
use Modules\Accounts\Http\Controllers\Storefront\AuthController;

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
        Route::get('/checkout', fn () => Inertia::render('Catalog::CheckoutPage'))->name('checkout');
        Route::post('/akun/alamat', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
        Route::put('/akun/alamat/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
        Route::delete('/akun/alamat/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
        Route::post('/akun/alamat/{address}/utama', [AccountController::class, 'makeAddressDefault'])->name('account.addresses.default');
    });
});
