<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\CheckClockSetting;

class CheckClockSettingSeeder extends Seeder
{
    public function run(): void
    {
        CheckClockSetting::create([
            'id' => Str::uuid(),
            'name' => 'Default Setting',
            'type' => 1,
        ]);
    }
}
