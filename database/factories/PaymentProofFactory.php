<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\PaymentProof;
class PaymentProofFactory extends Factory { protected $model = PaymentProof::class; public function definition(): array { return ['order_id' => Order::factory(), 'file_path' => 'payment-proofs/'.fake()->uuid().'.jpg']; } }
