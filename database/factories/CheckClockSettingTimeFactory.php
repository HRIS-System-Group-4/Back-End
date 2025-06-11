<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckClockSettingTime>
 */
class CheckClockSettingTimeFactory extends Factory
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
            'ck_settings_id' => \App\Models\CheckClockSetting::factory(),
            'day' => $this->faker->dayOfWeek(),
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'late_tolerance' => 15,
        ];
    }
}
