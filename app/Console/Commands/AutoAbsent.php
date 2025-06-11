<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CheckClock;
use App\Models\Employee;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AutoAbsent extends Command
{
    protected $signature = 'clock:auto-absent';
    protected $description = 'Auto absent employees who did not clock in by their scheduled clock out time';

    public function handle()
    {
        $now = Carbon::now();
        $today = strtolower($now->format('l'));

        $employees = Employee::with(['checkClockSetting.settingTimes'])->get();

        $absentCount = 0;

        foreach ($employees as $employee) {
            $settingTime = $employee->checkClockSetting->settingTimes
                ->firstWhere('day', $today);

            if (!$settingTime) continue;

            $clockOutTime = Carbon::parse($settingTime->clock_out);

            if ($now->gte($clockOutTime)) {
                $hasClockIn = CheckClock::where('user_id', $employee->user_id)
                    ->where('check_clock_type', 1) // clock in
                    ->whereDate('created_at', $now->toDateString())
                    ->exists();

                $hasAutoAbsent = CheckClock::where('user_id', $employee->user_id)
                    ->where('check_clock_type', 3) // auto absent
                    ->whereDate('created_at', $now->toDateString())
                    ->exists();

                if (!$hasClockIn && !$hasAutoAbsent) {
                    CheckClock::create([
                        'id'               => (string) Str::uuid(),
                        'user_id'          => $employee->user_id,
                        'check_clock_type' => 5, // 3 = auto absent
                        'check_clock_time' => '00:00:00',
                        'created_at'       => $clockOutTime,
                        'updated_at'       => now(),
                        'proof_path'       => null,
                    ]);

                    $absentCount++;
                }
            }
        }

        $this->info("Auto absent completed for {$absentCount} employee(s).");
    }
}
