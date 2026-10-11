<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
class OrderItemFactory extends Factory { protected $model = OrderItem::class; public function definition(): array { return ['order_id' => Order::factory(), 'product_id' => Product::factory(), 'product_name' => fake()->words(3, true), 'unit_price' => 100000, 'quantity' => 1, 'subtotal' => 100000]; } }
