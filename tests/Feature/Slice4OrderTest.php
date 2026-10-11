<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Models\Company;
use Modules\Cart\Models\Cart;
use Modules\Catalog\Models\Product;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;

uses(Tests\TestCase::class, RefreshDatabase::class);

function orderCustomer(): User
{
    return User::factory()->create(['company_id' => Company::factory()->create()->id, 'company_role' => 'admin']);
}

test('checkout creates order snapshot and reduces stock', function (): void {
    $user = orderCustomer();
    $product = Product::factory()->create(['regular_price' => 125000, 'sale_price' => null, 'stock' => 5]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

    $response = $this->actingAs($user)->post('/checkout', [
        'shipping_address' => ['recipient' => 'Nabil', 'phone' => '0812', 'province' => 'Banten', 'city' => 'Tangerang', 'address' => 'Jalan Test', 'postal_code' => '15111'],
        'payment_method' => 'qris',
        'shipping_method' => 'pickup',
        'shipping_cost' => 0,
    ]);

    $order = Order::firstOrFail();
    $response->assertRedirect(route('payment', $order->number));
    expect($order->number)->toBe('ACC-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT))
        ->and($order->items()->first()->unit_price)->toBe(125000)
        ->and($product->fresh()->stock)->toBe(3)
        ->and($cart->fresh()->items)->toBeEmpty();
});

test('checkout rejects insufficient stock without creating an order', function (): void {
    $user = orderCustomer();
    $product = Product::factory()->create(['stock' => 1]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

    $this->actingAs($user)->post('/checkout', [
        'shipping_address' => ['recipient' => 'Nabil', 'phone' => '0812', 'province' => 'Banten', 'city' => 'Tangerang', 'address' => 'Jalan Test', 'postal_code' => '15111'],
        'payment_method' => 'qris',
        'shipping_method' => 'kurir-khusus',
    ])->assertStatus(422);

    expect(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(1);
});

test('order transition rejects illegal status and payment confirmation changes status only', function (): void {
    $user = orderCustomer();
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::PendingPayment]);

    expect(fn () => app(OrderStateMachine::class)->transition($order, OrderStatus::Completed))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $this->actingAs($user)->post(route('payment.paid', $order->number))->assertRedirect(route('success', $order->number));
    expect($order->fresh()->status)->toBe(OrderStatus::AwaitingVerification);
});

test('order cannot be viewed by another user', function (): void {
    $owner = orderCustomer();
    $other = orderCustomer();
    $order = Order::factory()->create(['user_id' => $owner->id, 'company_id' => $owner->company_id]);

    $this->actingAs($other)->get(route('payment', $order->number))->assertNotFound();
});
