<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Models\Client;

class ClientFactory extends Factory
{
    protected $model = Client::class;
    public function definition(): array { return ['name' => fake()->company(), 'npwp' => fake()->unique()->numerify('##.###.###.#-###.###'), 'email' => fake()->companyEmail(), 'is_verified' => true]; }
}




