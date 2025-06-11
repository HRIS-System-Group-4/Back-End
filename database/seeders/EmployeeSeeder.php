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

        // Data referensi untuk randomisasi
        $firstNames      = ['Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Rudi', 'Lina', 'Agus', 'Maya'];
        $lastNames       = ['Santoso', 'Wahyudi', 'Saputra', 'Permata', 'Pratama', 'Wijaya', 'Rahma', 'Utama', 'Putri'];
        $employmentTypes = ['PKWT', 'pegawai tetap', 'contract', 'honorer', 'magang'];
        $grades          = ['Junior', 'Staff', 'Senior', 'Lead'];
        $jobTitles       = ['Developer', 'HRD', 'Marketing', 'Finance', 'Admin'];
        $banks           = ['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB'];
        $spTypes         = ['SP1', 'SP2', 'SP3'];

        $companies = Company::all();
        if ($companies->isEmpty()) {
            throw new \RuntimeException('Tidak ada company yang tersedia.');
        }

        $empIndex = 1;

        foreach ($companies as $company) {
            $branches = Branch::where('company_id', $company->id)->get();

            if ($branches->isEmpty()) {
                // Jika company belum punya branch, buat satu
                $branches = collect([
                    Branch::create([
                        'id'         => (string) Str::uuid(),
                        'company_id' => $company->id,
                        'branch_name' => 'Default Branch',
                        'address'    => 'Alamat Default',
                        'city'       => 'Jakarta',
                        'country'    => 'Indonesia',
                        'location'   => 'Jakarta',
                    ])
                ]);
            }

            // Spesial untuk company dengan username 'hris' dan branch 'HQ'
            if ($company->company_username === 'hris') {
                $hqBranch = $branches->firstWhere('branch_name', 'HQ') ?? $branches->first();

                $this->seedSingleEmployee(
                    empCode: 'EMP' . str_pad($empIndex++, 3, '0', STR_PAD_LEFT),
                    first: 'Amanda',
                    last: 'Manopo',
                    gender: 'P',
                    companyId: $company->id,
                    ckSettingId: $checkClockSetting->id,
                    branchId: $hqBranch->id
                );
            }

            // Tambahkan 5 karyawan acak untuk tiap company
            for ($i = 0; $i < 10; $i++) {
                $empCode = 'EMP' . str_pad($empIndex++, 3, '0', STR_PAD_LEFT);
                $first   = $firstNames[array_rand($firstNames)];
                $last    = $lastNames[array_rand($lastNames)];
                $gender  = $empIndex % 2 === 0 ? 'L' : 'P';

                $user = User::firstOrCreate(
                    ['employee_id' => $empCode],
                    [
                        'id'         => (string) Str::uuid(),
                        'employee_id' => $empCode,
                        'email'      => strtolower($first) . $empIndex . '@example.com',
                        'password'   => Hash::make('password123'),
                        'is_admin'   => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'id'                  => (string) Str::uuid(),
                        'company_id'          => $company->id,
                        'ck_settings_id'      => $checkClockSetting->id,
                        'branch_id'           => $branches->random()->id,
                        'first_name'          => $first,
                        'last_name'           => $last,
                        'employment_type'     => $employmentTypes[array_rand($employmentTypes)],
                        'gender'              => $gender,
                        'address'             => "Alamat {$first} {$last}",
                        'phone_number'        => '08' . random_int(100000000, 999999999),
                        'birth_date'          => Carbon::now()->subYears(random_int(20, 35))->subDays(random_int(0, 364)),
                        'birth_place'         => 'Jakarta',
                        'grade'               => $grades[array_rand($grades)],
                        'job_title'           => $jobTitles[array_rand($jobTitles)],
                        'sp_type'             => $spTypes[array_rand($spTypes)],
                        'bank_name'           => $banks[array_rand($banks)],
                        'bank_account_no'     => (string) random_int(1000000000, 9999999999),
                        'bank_account_owner'  => "{$first} {$last}",
                        'avatar_path'         => null,
                        'nik'                 => $this->generateNik(),
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]
                );
            }
        }
    }

    private function generateNik(): string
    {
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
                'id'                  => (string) Str::uuid(),
                'company_id'          => $companyId,
                'ck_settings_id'      => $ckSettingId,
                'branch_id'           => $branchId,
                'first_name'          => $first,
                'last_name'           => $last,
                'employment_type'     => 'contract',
                'gender'              => $gender,
                'address'             => 'Jl. Mawar No. 1',
                'phone_number'        => '081234567890',
                'birth_date'          => '1995-05-01',
                'birth_place'         => 'Jakarta',
                'grade'               => 'Staff',
                'job_title'           => 'Admin',
                'sp_type'             => 'SP1',
                'bank_name'           => 'BCA',
                'bank_account_no'     => '1234567890',
                'bank_account_owner'  => "{$first} {$last}",
                'avatar_path'         => null,
                'nik'                 => '1234567890123456',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]
        );
    }
}
