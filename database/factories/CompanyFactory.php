<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_name' => $this->faker->company(),
            'company_username' => $this->faker->unique()->slug(),
            'description' => $this->faker->catchPhrase(),
            'latitude' => $this->faker->latitude(-8, -5),
            'longitude' => $this->faker->longitude(105, 111),
        ];
    }
}
