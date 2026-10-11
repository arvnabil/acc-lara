<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatusHistory;
class OrderStatusHistoryFactory extends Factory { protected $model = OrderStatusHistory::class; public function definition(): array { return ['order_id' => Order::factory(), 'to_status' => 'pending_payment', 'created_at' => now()]; } }
