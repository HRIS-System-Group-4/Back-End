<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\ClockRequest;
use App\Models\User;
use Carbon\Carbon;

class ClockRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user yang punya relasi employee saja
        $users = User::whereHas('employee')->inRandomOrder()->take(5)->get();

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user dengan role employee ditemukan. Jalankan UserSeeder dan pastikan ada relasi employee.');
            return;
        }

        $i = 0;
        foreach ($users as $user) {
            ClockRequest::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $user->id,
                'check_clock_type' => rand(3, 4),
                'check_clock_time' => Carbon::createFromTime(rand(7, 9), rand(0, 59), rand(0, 59))->format('H:i:s'),
                'date'             => Carbon::now()->subDays($i++)->format('Y-m-d'),
                'proof_path'       => 'proofs/sample_proof_' . rand(1, 5) . '.pdf',
                'latitude'         => -6.2 + (rand(0, 1000) / 10000),
                'longitude'        => 106.8 + (rand(0, 1000) / 10000),
                'status'           => ['pending', 'approved', 'rejected'][rand(0, 2)],
            ]);
        }
    }
}
