<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTime;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CheckClockSettingSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['name' => 'WFO', 'type' => 1],
            ['name' => 'WFA', 'type' => 2],
            ['name' => 'WFC', 'type' => 3],
        ];

        foreach ($types as $type) {
            $settingId = (string) Str::uuid();

            DB::table('check_clock_settings')->insert([
                'id' => $settingId,
                'name' => $type['name'],
                'type' => $type['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Masukkan jam kerja Senin - Jumat
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            foreach ($days as $day) {
                DB::table('check_clock_setting_times')->insert([
                    'id' => (string) Str::uuid(),
                    'ck_settings_id' => $settingId,
                    'day' => $day,
                    'clock_in' => '08:00',
                    'clock_out' => '17:00',
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                    'late_tolerance' => 15,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
