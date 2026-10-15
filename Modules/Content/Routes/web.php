<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\ContentController;

Route::get('/bantuan', [ContentController::class, 'help'])->name('help');
Route::get('/minta-penawaran', [ContentController::class, 'rfq'])->name('rfq');
Route::post('/minta-penawaran', [ContentController::class, 'storeRfq'])
    ->middleware('throttle:5,1')
    ->name('rfq.store');
