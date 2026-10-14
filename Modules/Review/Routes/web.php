<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\ReviewController;

Route::middleware('auth')->group(function (): void {
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});
