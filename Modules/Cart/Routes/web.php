<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\ShippingController;

Route::middleware(['auth'])->group(function (): void {
    Route::get('/shipping/biteship/areas', [ShippingController::class, 'areas'])->name('shipping.biteship.areas');
    Route::get('/shipping/destinations', [ShippingController::class, 'destinations'])->name('shipping.destinations');
    Route::get('/shipping/quote', [ShippingController::class, 'quote'])->name('shipping.quote');
});
