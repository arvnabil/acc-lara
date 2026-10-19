<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Models\Client;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Review\Models\Review;
class ReviewFactory extends Factory { protected $model = Review::class; public function definition(): array { return ['order_id' => Order::factory(), 'order_item_id' => OrderItem::factory(), 'product_id' => Product::factory(), 'user_id' => User::factory(), 'client_id' => Client::factory(), 'rating' => fake()->numberBetween(1, 5), 'body' => fake()->paragraph(), 'is_approved' => true]; } }




