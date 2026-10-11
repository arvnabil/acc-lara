<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Product;
class CartItemFactory extends Factory { protected $model = CartItem::class; public function definition(): array { return ['cart_id' => Cart::factory(), 'product_id' => Product::factory(), 'quantity' => fake()->numberBetween(1, 5)]; } }
