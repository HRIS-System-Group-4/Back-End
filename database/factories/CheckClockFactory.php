<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckClock>
 */
class CheckClockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = now()->subDays(rand(0, 14))->setTime(8, 0)->addMinutes(rand(0, 30));
        $clockOut = (clone $clockIn)->addHours(8);

        return [
            'id' => (string) Str::uuid(),
            'user_id' => \App\Models\User::factory(),
            'check_clock_type' => $this->faker->randomElement([1, 2]),
            'check_clock_time' => $clockIn->format('H:i:s'),
            'date' => $clockIn->format('Y-m-d'),
            'proof_path' => 'proofs/' . $this->faker->word() . '.jpg',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }
}
