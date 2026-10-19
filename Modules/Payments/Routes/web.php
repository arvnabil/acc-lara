<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\OrderController;

Route::post('/payments/midtrans/notification', [OrderController::class, 'midtransNotification'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payment.midtrans.notification');

Route::middleware('auth')->group(function (): void {
    Route::get('/pembayaran/{number}', [OrderController::class, 'payment'])->name('payment');
    Route::post('/pembayaran/{number}/midtrans', [OrderController::class, 'midtrans'])->name('payment.midtrans');
    Route::post('/pembayaran/{number}/saya-sudah-bayar', [OrderController::class, 'paid'])->name('payment.paid');
});
