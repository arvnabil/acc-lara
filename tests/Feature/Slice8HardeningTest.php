<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Models\Product;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('pwa manifest and service worker are published with the required scope', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['name'])->toBe('Accommerce.id')
        ->and($manifest['short_name'])->toBe('Accommerce')
        ->and($manifest['id'])->toBe('/')
        ->and($manifest['scope'])->toBe('/')
        ->and($manifest['start_url'])->toBe('/')
        ->and(file_exists(public_path('sw.js')))->toBeTrue();
});

test('pwa build registers the root service worker and does not use the build scope', function () {
    $appBundle = collect(glob(public_path('build/assets/app-*.js')) ?: [])
        ->map(fn (string $path): string => file_get_contents($path))
        ->implode("\n");

    expect($appBundle)->toContain('/sw.js')
        ->not->toContain('/build/sw.js')
        ->and(file_get_contents(resource_path('views/app.blade.php')))->toContain('<link rel="manifest" href="/manifest.webmanifest">');
});

test('service worker denylist excludes sensitive storefront and admin routes', function () {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    foreach (['admin', 'livewire', 'login', 'register', 'lupa-password', 'reset-password', 'akun', 'keranjang', 'checkout', 'pembayaran', 'sukses', 'lacak-pesanan', 'wishlist', 'loyalty', 'reviews', 'cart'] as $segment) {
        expect($serviceWorker)->toContain($segment);
    }

    expect($serviceWorker)->not->toContain('NavigationRoute')
        ->and($serviceWorker)->not->toContain('createHandlerBoundToURL')
        ->and($serviceWorker)->toContain('setCatchHandler')
        ->and($serviceWorker)->toContain('/offline.html');
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
    app()->detectEnvironment(fn () => 'production');
    putenv('ADMIN_EMAIL=');
    putenv('ADMIN_PASSWORD=');

    try {
        expect(fn () => (new AdminUserSeeder)->run())
            ->toThrow(RuntimeException::class, 'ADMIN_EMAIL and ADMIN_PASSWORD are required in production');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
        putenv('APP_ENV=testing');
    }
});

test('admin without MFA is redirected to the required setup page', function () {
    $admin = User::factory()->create();
    $admin->saveAppAuthenticationSecret(null);
    Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
    $admin->assignRole('administrator');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect('/admin/multi-factor-authentication/set-up');
});
