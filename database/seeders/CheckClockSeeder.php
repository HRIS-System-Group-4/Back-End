<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\CheckClock;
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
            $date = Carbon::now()->subDays($i++);
            $clockInTime = Carbon::createFromTime(rand(7, 8), rand(0, 59), rand(0, 59));
            $clockOutTime = (clone $clockInTime)->addHours(8)->addMinutes(rand(0, 30));

            // Clock In
            CheckClock::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $user->id,
                'check_clock_type' => 1,
                'check_clock_time' => $clockInTime->format('H:i:s'),
                'date'             => $date->format('Y-m-d'),
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
                'date'             => $date->format('Y-m-d'),
                'proof_path'       => 'proofs/clockout_' . rand(1, 5) . '.jpg',
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ]);
        }
    }
}
