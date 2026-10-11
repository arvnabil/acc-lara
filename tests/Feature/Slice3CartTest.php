<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cart\Models\Cart;
use Modules\Catalog\Models\Product;
use Modules\Cart\Services\PricingService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('pricing estimates points after fixed discount', function (): void {
    $product = Product::factory()->create(['regular_price' => 10000000, 'sale_price' => null, 'stock' => 2]);
    $cart = Cart::factory()->create();
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $pricing = app(PricingService::class)->calculate($cart->items()->with(['product', 'variant'])->get(), null, 0);

    expect($pricing['goods_subtotal'])->toBe(10000000)
        ->and($pricing['estimated_points'])->toBe(1000);
});

test('cart item quantity is capped by product stock', function (): void {
    $product = Product::factory()->create(['stock' => 2]);
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->post('/cart/items', ['product_id' => $product->id, 'quantity' => 9])->assertRedirect();

    expect($user->carts()->first()->items()->first()->quantity)->toBe(2);
});
