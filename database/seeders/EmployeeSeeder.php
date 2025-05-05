<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\CheckClockSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $checkClockSetting = CheckClockSetting::first();
        if (!$checkClockSetting) {
            throw new \Exception('CheckClockSetting belum tersedia.');
        }

        // Ambil company_id berdasarkan nama perusahaan (contoh: 'hris')
        $company = Company::where('company_username', 'hris')->first();
        if (!$company) {
            throw new \Exception('Company belum tersedia.');
        }

        // Seeder untuk EMP001 secara manual jika sudah ada user-nya
        $emp001 = User::where('id', 'EMP001')->first();
        if ($emp001 && !Employee::where('user_id', $emp001->id)->exists()) {
            Employee::create([
                'id' => Str::uuid(),
                'user_id' => $emp001->id,
                'company_id' => $company->id,
                'first_name' => 'Amanda',
                'last_name' => 'Manopo',
                'employment_type' => 'contract',
                'gender' => 'P',
                'address' => 'Jl. Mawar No. 1',
                'ck_settings_id' => $checkClockSetting->id,
            ]);
        }

        // Nama acak
        $firstNames = ['Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Rudi', 'Lina', 'Agus', 'Maya'];
        $lastNames = ['Santoso', 'Wahyudi', 'Saputra', 'Permata', 'Pratama', 'Wijaya', 'Rahma', 'Utama', 'Putri'];
        $employmentTypes = ['contract', 'honorer', 'magang'];

        for ($i = 3; $i <= 10; $i++) {
            $number = str_pad($i, 1, '0', STR_PAD_LEFT);
            $userId = 'EMP' . $number;

            $user = User::where('id', $userId)->first();
            if (!$user) {
                $user = User::create([
                    'id' => $userId,
                    'email' => "employee{$i}@example.com", // email unik
                    'password' => Hash::make('password123'),
                    'is_admin' => false,
                ]);
            }

            if (!Employee::where('user_id', $user->id)->exists()) {
                Employee::create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name' => $lastNames[array_rand($lastNames)],
                    'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                    'gender' => $i % 2 == 0 ? 'L' : 'P',
                    'address' => 'Alamat acak ke-' . $i,
                    'ck_settings_id' => $checkClockSetting->id,
                ]);
            }
        }
    }
}
