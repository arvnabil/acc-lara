<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
class OrderFactory extends Factory { protected $model = Order::class; public function definition(): array { return ['number' => fake()->unique()->numerify('ORD###############'), 'user_id' => User::factory(), 'client_id' => Client::factory(), 'status' => 'pending_payment', 'goods_subtotal' => 100000, 'grand_total' => 100000, 'shipping_address' => ['address' => fake()->address()]]; } }




