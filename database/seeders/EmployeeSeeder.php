<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\{
    Employee,
    User,
    Company,
    Branch,
    CheckClockSetting
};

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $checkClockSetting = CheckClockSetting::first();
        if (!$checkClockSetting) {
            throw new \RuntimeException('CheckClockSetting belum tersedia.');
        }

        $company = Company::where('company_username', 'hris')->first();
        if (!$company) {
            throw new \RuntimeException('Company "hris" belum tersedia.');
        }

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'branch_name' => 'HQ'],
            [
                'id'       => (string) Str::uuid(),
                'address'  => 'Jl. Jenderal Sudirman No. 1',
                'city'     => 'Jakarta',
                'country'  => 'Indonesia',
                'location' => 'Jakarta',
            ]
        );

        // Seeder karyawan pertama spesifik
        $this->seedSingleEmployee(
            empCode: 'EMP001',
            first: 'Amanda',
            last: 'Manopo',
            gender: 'P',
            companyId: $company->id,
            ckSettingId: $checkClockSetting->id,
            branchId: $branch->id
        );

        // Seeder karyawan acak
        $firstNames      = ['Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Rudi', 'Lina', 'Agus', 'Maya'];
        $lastNames       = ['Santoso', 'Wahyudi', 'Saputra', 'Permata', 'Pratama', 'Wijaya', 'Rahma', 'Utama', 'Putri'];
        $employmentTypes = ['PKWT', 'pegawai tetap', 'contract', 'honorer', 'magang'];
        $grades          = ['Junior', 'Staff', 'Senior', 'Lead'];

        for ($i = 2; $i <= 10; $i++) {
            $empCode = 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['employee_id' => $empCode],
                [
                    'id'         => (string) Str::uuid(),
                    'employee_id' => $empCode,
                    'email'      => "employee{$i}@example.com",
                    'password'   => Hash::make('password123'),
                    'is_admin'   => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'id'              => (string) Str::uuid(),
                    'company_id'      => $company->id,
                    'ck_settings_id'  => $checkClockSetting->id,
                    'branch_id'       => $branch->id,
                    'first_name'      => $firstNames[array_rand($firstNames)],
                    'last_name'       => $lastNames[array_rand($lastNames)],
                    'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                    'gender'          => $i % 2 === 0 ? 'L' : 'P',
                    'address'         => "Alamat acak ke-{$i}",
                    'phone_number'    => '08' . random_int(100000000, 999999999),
                    'birth_date'      => Carbon::now()->subYears(random_int(20, 35))->subDays(random_int(0, 364)),
                    'grade'           => $grades[array_rand($grades)],
                    'avatar_path'     => null,
                    'nik'             => $this->generateNik(), // tambah nik
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }
    private function generateNik(): string
    {
        // Generate random 16 digit angka string
        return str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
    }


    private function seedSingleEmployee(
        string $empCode,
        string $first,
        string $last,
        string $gender,
        string $companyId,
        string $ckSettingId,
        string $branchId
    ): void {
        $user = User::firstOrCreate(
            ['employee_id' => $empCode],
            [
                'id'         => (string) Str::uuid(),
                'email'      => strtolower($first) . '@example.com',
                'employee_id' => $empCode,
                'password'   => Hash::make('password123'),
                'is_admin'   => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'id'              => (string) Str::uuid(),
                'company_id'      => $companyId,
                'ck_settings_id'  => $ckSettingId,
                'branch_id'       => $branchId,
                'first_name'      => $first,
                'last_name'       => $last,
                'employment_type' => 'contract',
                'gender'          => $gender,
                'address'         => 'Jl. Mawar No. 1',
                'phone_number'    => '081234567890',
                'birth_date'      => '1995-05-01',
                'grade'           => 'Staff',
                'avatar_path'     => null,
                'nik'             => '1234567890123456',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );
    }
}
