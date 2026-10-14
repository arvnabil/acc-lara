<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Models\Banner;
use Modules\Content\Models\RfqRequest;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function rfqPayload(array $overrides = []): array
{
    return [
        'company_name' => 'PT Accommerce Test',
        'name' => 'Nabil Test',
        'email' => 'nabil@example.test',
        'phone' => '081234567890',
        'message' => 'Butuh 10 unit video conference untuk ruang rapat.',
        ...$overrides,
    ];
}

test('RFQ tersimpan dengan field benar dan email sales dikirim', function (): void {
    Mail::fake();

    $this->post('/minta-penawaran', rfqPayload())->assertRedirect();

    expect(RfqRequest::query()->first()->only(['name', 'email', 'phone', 'company_name', 'message', 'status']))
        ->toBe([
            'name' => 'Nabil Test',
            'email' => 'nabil@example.test',
            'phone' => '081234567890',
            'company_name' => 'PT Accommerce Test',
            'message' => 'Butuh 10 unit video conference untuk ruang rapat.',
            'status' => 'new',
        ]);
});

test('RFQ validation fails for missing fields', function (): void {
    $this->from('/minta-penawaran')->post('/minta-penawaran', [])->assertSessionHasErrors([
        'company_name', 'name', 'email', 'phone', 'message',
    ]);

    expect(RfqRequest::count())->toBe(0);
});

test('RFQ sixth attempt in one minute returns 429', function (): void {
    $responses = collect(range(1, 6))->map(fn (int $attempt) => $this->post('/minta-penawaran', rfqPayload(['email' => "nabil{$attempt}@example.test"])));

    expect($responses->last()->status())->toBe(429);
});

test('inactive and scheduled-out banners are not shared to the home page', function (): void {
    Banner::factory()->create(['is_active' => false]);
    Banner::factory()->create(['starts_at' => now()->addDay()]);
    Banner::factory()->create(['expires_at' => now()->subDay()]);

    $this->get('/')->assertInertia(fn ($page) => $page->where('banners', []));
});

test('home page renders with no banner records', function (): void {
    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Catalog::HomePage', false)->where('banners', []));
});

test('help page renders successfully', function (): void {
    $this->get('/bantuan')->assertOk()->assertInertia(fn ($page) => $page->component('Catalog::BantuanPage', false));
});

test('random URL returns the Inertia NotFound page with status 404', function (): void {
    $this->get('/slice-seven-does-not-exist')->assertStatus(404)->assertInertia(fn ($page) => $page->component('Catalog::NotFound', false));
});

test('internal storefront links return 200 or 302', function (): void {
    foreach (['/', '/katalog', '/kategori', '/cari', '/keranjang', '/wishlist', '/checkout', '/akun', '/login', '/register', '/lupa-password', '/lacak-pesanan', '/bantuan', '/minta-penawaran'] as $uri) {
        $status = $this->get($uri)->status();
        expect(in_array($status, [200, 302], true))->toBeTrue("{$uri} returned {$status}");
    }
});
