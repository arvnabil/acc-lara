<?php

use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Models\Product;

uses(Tests\TestCase::class);

test('pwa manifest and service worker are published with the required scope', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['name'])->toBe('Accommerce.id')
        ->and($manifest['short_name'])->toBe('Accommerce')
        ->and($manifest['scope'])->toBe('/')
        ->and($manifest['start_url'])->toBe('/')
        ->and(file_exists(public_path('sw.js')))->toBeTrue();
});

test('service worker denylist excludes sensitive storefront and admin routes', function () {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    foreach (['/admin', '/akun', '/keranjang', '/checkout', '/pembayaran', '/sukses', '/lacak-pesanan', '/wishlist', '/loyalty', '/reviews', '/login'] as $path) {
        expect($serviceWorker)->toContain($path);
    }
});

test('storefront mutating routes use named rate limits', function () {
    expect(Route::getRoutes()->getByName('login.submit')->middleware())->toContain('throttle:login')
        ->and(Route::getRoutes()->getByName('register.submit')->middleware())->toContain('throttle:register')
        ->and(Route::getRoutes()->getByName('password.email')->middleware())->toContain('throttle:password.email')
        ->and(Route::getRoutes()->getByName('checkout.store')->middleware())->toContain('throttle:checkout')
        ->and(Route::getRoutes()->getByName('loyalty.redeem')->middleware())->toContain('throttle:loyalty.redeem');
});

test('product rich text strips scripts event handlers and javascript links', function () {
    $product = new Product;
    $product->description = '<p onerror="alert(1)">A</p><script>alert(1)</script><a href="javascript:alert(1)">B</a>';

    $description = $product->getAttribute('description');
    expect($description)->toBe('<p>A</p><a>B</a>');
    expect($description)->not->toContain('<script');
    expect($description)->not->toContain('onerror');
    expect($description)->not->toContain('javascript:');
});

test('production admin seeder refuses missing credentials', function () {
    config()->set('app.env', 'production');
    putenv('ADMIN_EMAIL');
    putenv('ADMIN_PASSWORD');

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_EMAIL and ADMIN_PASSWORD are required in production');
});
