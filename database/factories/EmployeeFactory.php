<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
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
            'user_id' => \App\Models\User::factory(),
            'ck_settings_id' => \App\Models\CheckClockSetting::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'employment_type' => $this->faker->randomElement(['PKWT', 'pegawai tetap', 'contract']),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'address' => $this->faker->address(),
            'branch_id' => \App\Models\Branch::factory(),
        ];
    }
}
