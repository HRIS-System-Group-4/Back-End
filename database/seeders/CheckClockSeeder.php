<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\CheckClock;
use App\Models\ClockRequest;
use App\Models\CheckClockSettingTime;
use Carbon\Carbon;

class CheckClockSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::has('employee')->inRandomOrder()->take(10)->get();

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user dengan employee ditemukan. Pastikan EmployeeSeeder sudah dijalankan.');
            return;
        }

        $i = 0;
        foreach ($users as $user) {
            $date = Carbon::now()->subDays($i++)->format('Y-m-d');
            $dayName = Carbon::parse($date)->format('l');

            $hasLeave = ClockRequest::where('user_id', $user->id)
                ->where('date', $date)
                ->whereIn('check_clock_type', [3, 4]) // Sick Leave / Annual Leave
                ->exists();

            if ($hasLeave) {
                // $this->command->line("Lewatkan {$user->name} karena sedang cuti/sakit pada {$date}.");
                // continue;
            }

            $setting = CheckClockSettingTime::where('day', $dayName)->first();

            $baseClockIn = $setting ? Carbon::createFromFormat('H:i:s', $setting->clock_in) : Carbon::createFromTime(8, 0, 0);
            $toleranceMinutes = $setting ? $setting->late_tolerance : 0;

            $clockInTime = (clone $baseClockIn)->addMinutes(rand(0, $toleranceMinutes + 15));

            $clockOutTime = (clone $clockInTime)->addHours(8)->addMinutes(rand(0, 10));

            CheckClock::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $user->id,
                'check_clock_type' => 1,
                'check_clock_time' => $clockInTime->format('H:i:s'),
                'date'             => $date,
                'proof_path'       => 'proofs/clockin_' . rand(1, 5) . '.jpg',
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ]);

            // Clock Out
            CheckClock::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $user->id,
                'check_clock_type' => 2,
                'check_clock_time' => $clockOutTime->format('H:i:s'),
                'date'             => $date,
                'proof_path'       => 'proofs/clockout_' . rand(1, 5) . '.jpg',
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ]);
        }
    }
}
