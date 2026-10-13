<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Models\Company;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\Wishlist;
use Modules\Cart\Services\PricingService;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Orders\Actions\CreateOrderAction;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Events\OrderReturned;
use Modules\Orders\Jobs\AutoCompleteShippedOrdersJob;
use Modules\Orders\Jobs\ExpirePendingOrdersJob;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;
use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function prerequisiteCustomer(array $attributes = []): User
{
    return User::factory()->create([
        'company_id' => Company::factory()->create()->id,
        'company_role' => 'admin',
        ...$attributes,
    ]);
}

function prerequisiteCartItem(User $user, array $product = [], array $item = []): Cart
{
    $cart = Cart::create(['user_id' => $user->id]);
    $productModel = Product::factory()->create($product);
    $cart->items()->create(['product_id' => $productModel->id, 'quantity' => 1, ...$item]);

    return $cart->fresh(['items.product', 'items.variant']);
}

test('checkout and success routes are registered', function (): void {
    expect(route('checkout'))->toContain('/checkout')
        ->and(route('success', 'ACC-00001'))->toContain('/sukses/ACC-00001');
});

test('guest cart merges through the real HTTP login flow', function (): void {
    $product = Product::factory()->create(['stock' => 3]);
    $user = prerequisiteCustomer(['password' => Hash::make('password')]);

    $this->post('/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

    expect($user->fresh()->carts()->first()->items()->value('quantity'))->toBe(2);
    expect(Cart::query()->whereNull('user_id')->count())->toBe(0);
});

test('guest cart merges after storefront registration', function (): void {
    $product = Product::factory()->create(['stock' => 3]);

    $this->post('/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();
    $this->post('/register', [
        'name' => 'Registered Customer',
        'email' => 'registered@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/akun');

    expect(User::where('email', 'registered@example.test')->firstOrFail()->carts()->first()->items()->value('quantity'))->toBe(2);
});

test('each seeded promo is applied by the server with the React success message', function (string $code): void {
    $user = prerequisiteCustomer();
    $cart = prerequisiteCartItem($user, ['regular_price' => 1000000]);
    PromoCode::updateOrCreate(['code' => $code], ['label' => $code, 'type' => 'percent', 'value' => 10, 'min_order' => 0, 'is_active' => true]);

    $this->actingAs($user)->post('/cart/promo', ['code' => $code])->assertRedirect();

    expect($cart->fresh()->promo_code_id)->not->toBeNull()
        ->and(session('success'))->toContain("✅ Kode {$code} berhasil diterapkan!");
})->with(['SAVE10', 'ACCOMM25', 'GRATIS50K', 'TECH100K', 'NEWMEMBER']);

test('promo min order quota schedule and inactive rules reject with the React error', function (array $attributes): void {
    $user = prerequisiteCustomer();
    prerequisiteCartItem($user, ['regular_price' => 100000]);
    PromoCode::create(['code' => 'RULETEST', 'label' => 'Rule Test', 'type' => 'fixed', 'value' => 1000, ...$attributes]);

    $this->actingAs($user)->post('/cart/promo', ['code' => 'RULETEST'])
        ->assertSessionHasErrors(['code' => '❌ Kode promo tidak valid atau sudah kedaluwarsa.']);
})->with([
    [['min_order' => 1000000, 'is_active' => true]],
    [['min_order' => 0, 'max_uses' => 1, 'used_count' => 1, 'is_active' => true]],
    [['min_order' => 0, 'valid_from' => '2037-01-01 00:00:00', 'is_active' => true]],
    [['min_order' => 0, 'valid_until' => '2020-01-01 00:00:00', 'is_active' => true]],
    [['min_order' => 0, 'is_active' => false]],
]);

test('rental pricing multiplies the rental days on the server', function (): void {
    $user = prerequisiteCustomer();
    $cart = prerequisiteCartItem($user, ['regular_price' => 100000], [
        'rental_start' => '2026-10-01',
        'rental_end' => '2026-10-04',
    ]);

    $pricing = app(PricingService::class)->calculate($cart->items, null, 0);

    expect($pricing['goods_subtotal'])->toBe(300000);
});

test('wishlist renders more than fifty products', function (): void {
    $user = prerequisiteCustomer();
    Product::factory()->count(51)->create()->each(fn (Product $product) => Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]));

    $this->actingAs($user)->get('/wishlist')->assertInertia(fn ($page) => $page->where('wishlistProducts', fn ($products) => count($products) === 51));
});

test('guest wishlist redirects to login and preserves the intended URL', function (): void {
    $this->get('/wishlist')->assertRedirect('/login');
    expect(session()->get('_previous.url'))->toContain('/wishlist');
});

test('a user cannot mutate another user cart', function (): void {
    $owner = prerequisiteCustomer();
    $other = prerequisiteCustomer();
    $cart = prerequisiteCartItem($owner);
    $item = $cart->items()->first();

    $this->actingAs($other)->put("/cart/items/{$item->id}", ['quantity' => 2])->assertForbidden();
});

test('variant quantity is capped by variant stock', function (): void {
    $user = prerequisiteCustomer();
    $product = Product::factory()->create();
    $variant = Variant::factory()->create(['product_id' => $product->id, 'stock' => 2]);

    $this->actingAs($user)->post('/cart/items', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 9])->assertRedirect();

    expect($user->carts()->first()->items()->value('quantity'))->toBe(2);
});

test('cancelling an order restores stock and promo quota', function (): void {
    $user = prerequisiteCustomer();
    $product = Product::factory()->create(['stock' => 0]);
    $promo = PromoCode::factory()->create(['used_count' => 1]);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'promo_code_id' => $promo->id, 'status' => OrderStatus::PendingPayment]);
    $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000]);

    app(OrderStateMachine::class)->transition($order, OrderStatus::Cancelled);

    expect($product->fresh()->stock)->toBe(1)->and($promo->fresh()->used_count)->toBe(0);
});

test('expiring pending orders restores stock and is idempotent', function (): void {
    $user = prerequisiteCustomer();
    $product = Product::factory()->create(['stock' => 0]);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::PendingPayment, 'created_at' => now()->subDays(2)]);
    $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000]);

    app(ExpirePendingOrdersJob::class)->handle(app(OrderStateMachine::class));
    app(ExpirePendingOrdersJob::class)->handle(app(OrderStateMachine::class));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)->and($product->fresh()->stock)->toBe(1);
});

test('returning an order restores stock and emits OrderReturned', function (): void {
    Event::fake([OrderReturned::class]);
    $user = prerequisiteCustomer();
    $product = Product::factory()->create(['stock' => 0]);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Completed]);
    $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000]);

    app(OrderStateMachine::class)->transition($order, OrderStatus::Returned);

    Event::assertDispatched(OrderReturned::class);
    expect($product->fresh()->stock)->toBe(1);
});

test('created orders have unique sequential ACC numbers', function (): void {
    $user = prerequisiteCustomer();
    $action = app(CreateOrderAction::class);
    $first = $action->execute(prerequisiteCartItem($user, ['stock' => 2]), ['shipping_method' => 'pickup', 'shipping_address' => ['recipient' => 'Nabil'], 'payment_method' => 'qris']);
    $second = $action->execute(prerequisiteCartItem($user, ['stock' => 2]), ['shipping_method' => 'pickup', 'shipping_address' => ['recipient' => 'Nabil'], 'payment_method' => 'qris']);

    expect($first->number)->toBe('ACC-'.str_pad((string) $first->id, 5, '0', STR_PAD_LEFT))
        ->and($second->id)->toBe($first->id + 1)
        ->and($second->number)->not->toBe($first->number);
});

test('checkout ignores a forged shipping cost from the client', function (): void {
    $user = prerequisiteCustomer();
    prerequisiteCartItem($user, ['stock' => 2]);

    $this->actingAs($user)->post('/checkout', [
        'shipping_address' => ['recipient' => 'Nabil', 'phone' => '0812', 'province' => 'Banten', 'city' => 'Tangerang', 'address' => 'Jalan Test', 'postal_code' => '15111'],
        'payment_method' => 'qris',
        'shipping_method' => 'pickup',
        'shipping_cost' => 99999999,
    ])->assertRedirect();

    expect(Order::latest('id')->value('shipping_cost'))->toBe(0);
});

test('empty checkout redirects to cart and guest checkout redirects to login', function (): void {
    $this->get('/checkout')->assertRedirect('/login');

    $user = prerequisiteCustomer();

    $this->actingAs($user)->get('/checkout')->assertRedirect('/keranjang');
});

test('voucher dipakai saat checkout dan dikembalikan saat order dibatalkan', function (): void {
    $user = prerequisiteCustomer();
    $product = Product::factory()->create(['stock' => 2, 'regular_price' => 100000]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
    Voucher::factory()->create(['company_id' => $user->company_id, 'code' => 'VOUCHER-CANCEL', 'type' => 'fixed', 'discount_value' => 10000, 'min_order' => 0]);

    $this->actingAs($user)->post('/cart/promo', ['code' => 'VOUCHER-CANCEL'])->assertRedirect();
    $response = $this->actingAs($user)->post('/checkout', ['shipping_method' => 'pickup', 'shipping_address' => ['recipient' => 'Nabil', 'phone' => '0812', 'province' => 'Banten', 'city' => 'Tangerang', 'address' => 'Jalan Test', 'postal_code' => '15111'], 'payment_method' => 'qris']);
    $order = Order::query()->latest('id')->firstOrFail();
    $voucher = Voucher::where('code', 'VOUCHER-CANCEL')->firstOrFail();

    $response->assertRedirect(route('payment', $order->number));
    expect($order->voucher_id)->toBe($voucher->id)->and($voucher->fresh()->used_at)->not->toBeNull();
    app(OrderStateMachine::class)->transition($order, OrderStatus::Cancelled);

    expect($voucher->fresh()->used_at)->toBeNull();
});

test('voucher dikembalikan saat pesanan kedaluwarsa', function (): void {
    $user = prerequisiteCustomer();
    $product = Product::factory()->create(['stock' => 2, 'regular_price' => 100000]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
    $voucher = Voucher::factory()->create(['company_id' => $user->company_id, 'code' => 'VOUCHER-EXPIRE', 'type' => 'fixed', 'discount_value' => 10000, 'min_order' => 0]);

    $this->actingAs($user)->post('/cart/promo', ['code' => $voucher->code])->assertRedirect();
    $this->actingAs($user)->post('/checkout', ['shipping_method' => 'pickup', 'shipping_address' => ['recipient' => 'Nabil', 'phone' => '0812', 'province' => 'Banten', 'city' => 'Tangerang', 'address' => 'Jalan Test', 'postal_code' => '15111'], 'payment_method' => 'qris'])->assertRedirect();
    $order = Order::query()->latest('id')->firstOrFail();
    Order::query()->whereKey($order->id)->update(['created_at' => now()->subDays(2)]);

    app(ExpirePendingOrdersJob::class)->handle(app(OrderStateMachine::class));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)->and($voucher->fresh()->used_at)->toBeNull();
});

test('auto completing shipped orders is idempotent', function (): void {
    $user = prerequisiteCustomer();
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Shipped, 'shipped_at' => now()->subDays(8)]);

    app(AutoCompleteShippedOrdersJob::class)->handle(app(OrderStateMachine::class));
    app(AutoCompleteShippedOrdersJob::class)->handle(app(OrderStateMachine::class));

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});
