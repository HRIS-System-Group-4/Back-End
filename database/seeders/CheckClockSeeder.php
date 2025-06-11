<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\CheckClock;
use App\Models\CheckClockSettingTime;
use App\Models\ClockRequest;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CheckClockSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::take(5)->get();

        if ($companies->isEmpty()) {
            $this->command->warn('Tidak ada company ditemukan.');
            return;
        }

        $totalRecordsPerCompany = 10; // total 5 * 10 = 50 records (25 users, 25x2 clock)
        $recordCounter = 0;

        foreach ($companies as $company) {
            $users = User::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })->inRandomOrder()->get();

            if ($users->isEmpty()) {
                $this->command->warn("Tidak ada user dengan employee untuk company: {$company->company_username}");
                continue;
            }

            $count = 0;
            while ($count < $totalRecordsPerCompany) {
                $user = $users->random();

                $randomDaysAgo = rand(0, 13); // Maksimal 14 hari terakhir
                $date = Carbon::now()->subDays($randomDaysAgo)->format('Y-m-d');
                $dayName = Carbon::parse($date)->format('l');

                $hasLeave = ClockRequest::where('user_id', $user->id)
                    ->where('date', $date)
                    ->whereIn('check_clock_type', [3, 4])
                    ->exists();

                if ($hasLeave) {
                    continue;
                }

                $setting = CheckClockSettingTime::where('day', $dayName)->first();

                $baseClockIn = $setting ? Carbon::createFromFormat('H:i:s', $setting->clock_in) : Carbon::createFromTime(8, 0, 0);
                $toleranceMinutes = $setting ? $setting->late_tolerance : 0;

                $clockInTime = (clone $baseClockIn)->addMinutes(rand(0, $toleranceMinutes + 15));
                $clockOutTime = (clone $clockInTime)->addHours(8)->addMinutes(rand(0, 10));

                // Hindari duplikat untuk user dan tanggal yang sama
                $alreadyExists = CheckClock::where('user_id', $user->id)->where('date', $date)->exists();
                if ($alreadyExists) {
                    continue;
                }

                // Clock In
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

                $count++;
                $recordCounter++;
            }

            // $this->command->info("Berhasil membuat {$count} data CheckClock untuk company: {$company->company_username}");
        }

        // $this->command->info("Total CheckClock record yang berhasil dibuat: $recordCounter (x2 = " . ($recordCounter * 2) . " entries termasuk clock in dan out)");
    }
}
