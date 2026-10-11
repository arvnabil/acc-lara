<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounts\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;
    public function definition(): array { return ['name' => fake()->company(), 'npwp' => fake()->unique()->numerify('##.###.###.#-###.###'), 'email' => fake()->companyEmail(), 'is_verified' => true]; }
}
