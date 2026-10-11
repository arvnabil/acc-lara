<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Catalog\Http\Controllers\CatalogController;

Route::get('/login', fn () => Inertia::render('Catalog::LoginPage'))->name('login');

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/produk/{slug}', [CatalogController::class, 'show'])->name('product.show');
Route::get('/cari', [CatalogController::class, 'search'])->name('search');
Route::get('/kategori', [CatalogController::class, 'categories'])->name('categories');
