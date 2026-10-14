<?php

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Http\Controllers\RedeemController;

Route::middleware(['auth', 'throttle:loyalty.redeem'])->post('/loyalty/redeem', [RedeemController::class, 'store'])->name('loyalty.redeem');
