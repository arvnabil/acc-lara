<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\RfqRequest;
class RfqRequestFactory extends Factory { protected $model = RfqRequest::class; public function definition(): array { return ['name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => fake()->numerify('08##########'), 'company_name' => fake()->company(), 'message' => fake()->optional()->paragraph(), 'status' => 'new']; } }
