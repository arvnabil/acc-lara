<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\InvoiceController;

Route::middleware(['auth', 'can:view_order'])->group(function (): void {
    Route::get('/admin/orders/{order}/invoice', [InvoiceController::class, 'download'])
        ->name('admin.orders.invoice');
});
