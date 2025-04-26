<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\CheckClockSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $checkClockSetting = CheckClockSetting::first();
        if (!$checkClockSetting) {
            throw new \Exception('CheckClockSetting belum tersedia.');
        }

        $emp001 = User::where('id', 'EMP001')->first();
        if ($emp001) {
            Employee::create([
                'id' => Str::uuid(),
                'user_id' => $emp001->id,
                'first_name' => 'Amanda',
                'last_name' => 'Manopo',
                'gender' => 'P',
                'address' => 'Jl. Mawar No. 1',
                'ck_settings_id' => $checkClockSetting->id,
            ]);
        }

        $emp002 = User::where('id', 'EMP002')->first();
        if ($emp002) {
            Employee::create([
                'id' => Str::uuid(),
                'user_id' => $emp002->id,
                'first_name' => 'Tryo',
                'last_name' => 'Bagus',
                'gender' => 'L',
                'address' => 'Jl. Kumis Kucing No. 666',
                'ck_settings_id' => $checkClockSetting->id,
            ]);
        }

        $firstNames = ['Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Rudi', 'Lina', 'Agus', 'Maya'];
        $lastNames = ['Santoso', 'Wahyudi', 'Saputra', 'Permata', 'Pratama', 'Wijaya', 'Rahma', 'Utama', 'Putri'];

        for ($i = 3; $i <= 10; $i++) {
            $number = str_pad($i, 3, '0', STR_PAD_LEFT);
            $userId = 'EMP' . $number;

            $user = User::where('id', $userId)->first();
            if (!$user) {
                $user = User::create([
                    'id' => $userId,
                    'password' => Hash::make('password123'),
                    'is_admin' => false,
                    'company' => 'hris',
                ]);
            }

            Employee::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'first_name' => $firstNames[array_rand($firstNames)],
                'last_name' => $lastNames[array_rand($lastNames)],
                'gender' => $i % 2 == 0 ? 'L' : 'P',
                'address' => 'Alamat acak ke-' . $i,
                'ck_settings_id' => $checkClockSetting->id,
            ]);
        }
    }
}
