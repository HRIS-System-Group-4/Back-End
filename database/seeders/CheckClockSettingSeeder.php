<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTime;
use Illuminate\Support\Str;

class CheckClockSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        CheckClockSetting::create([
            'id' => Str::uuid(),
            'name' => 'WFO',
            'type' => 1,
        ]);
    }
}
