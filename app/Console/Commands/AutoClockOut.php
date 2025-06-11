<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CheckClock;
use App\Models\Employee;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AutoClockOut extends Command
{
    protected $signature = 'clock:auto-clockout';
    protected $description = 'Auto clock out employees who forgot to clock out';

    public function handle()
    {
        $yesterday = Carbon::yesterday();
        $dayName = strtolower($yesterday->format('l')); // contoh: 'monday'

        // Ambil user_id yang clock in kemarin tapi belum clock out
        $usersToAutoClockOut = CheckClock::where('check_clock_type', 1)
            ->whereDate('created_at', $yesterday->toDateString())
            ->pluck('user_id')
            ->diff(
                CheckClock::where('check_clock_type', 2)
                    ->whereDate('created_at', $yesterday->toDateString())
                    ->pluck('user_id')
            );

        $employees = Employee::with(['checkClockSetting.settingTimes'])->whereIn('user_id', $usersToAutoClockOut)->get();

        $count = 0;

        foreach ($employees as $employee) {
            $settingTime = $employee->checkClockSetting->settingTimes
                ->firstWhere('day', $dayName);

            if (!$settingTime) continue;

            $clockOutTime = Carbon::parse($yesterday->format('Y-m-d') . ' ' . $settingTime->clock_out);

            CheckClock::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $employee->user_id,
                'check_clock_type' => 2,
                'check_clock_time' => $settingTime->clock_out,
                'created_at'       => $clockOutTime,
                'updated_at'       => now(),
                'proof_path'       => null,
            ]);

            $count++;
        }

        // $this->info("Auto clock out completed for {$count} employees.");
    }
}
