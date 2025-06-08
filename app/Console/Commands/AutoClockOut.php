<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CheckClock;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AutoClockOut extends Command
{
    protected $signature = 'clock:auto-clockout';
    protected $description = 'Auto clock out employees who forgot to clock out';

    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();

        // Ambil user_id yang clock in kemarin, tapi belum clock out
        $usersToAutoClockOut = CheckClock::where('check_clock_type', 1)
            ->whereDate('created_at', $yesterday)
            ->pluck('user_id')
            ->diff(
                CheckClock::where('check_clock_type', 2)
                    ->whereDate('created_at', $yesterday)
                    ->pluck('user_id')
            );

        $usersToAutoClockOut->each(function ($userId) use ($yesterday) {
            CheckClock::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $userId,
                'check_clock_type' => 2,
                'check_clock_time' => '00:00:00',
                'created_at'       => Carbon::parse($yesterday . ' 23:59:59'),
                'updated_at'       => now(),
                'proof_path'       => null,
            ]);
        });

        $this->info('Auto clock out completed for ' . $usersToAutoClockOut->count() . ' employees.');
    }
}
