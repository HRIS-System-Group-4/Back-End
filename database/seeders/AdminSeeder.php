<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Branch;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. HRIS Company
        $company1 = Company::firstOrCreate(
            ['company_username' => 'hris'],
            [
                'id' => Str::uuid(),
                'company_name' => 'HRIS Company',
                'description' => 'HRIS internal system',
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. NoSub Company
        $company2 = Company::firstOrCreate(
            ['company_username' => 'nosub'],
            [
                'id' => Str::uuid(),
                'company_name' => 'NoSub Company',
                'description' => 'Company tanpa subscription',
                'latitude' => '-7.000000',
                'longitude' => '110.000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Ambil semua branch untuk assign admin ke branch yang valid
        $branches = Branch::all();
        if ($branches->count() < 5) {
            $this->command->warn('Minimal butuh 5 branch untuk assign semua admin.');
            return;
        }

        $adminData = [
            [
                'email' => 'lalasipo20@gmail.com',
                'first_name' => 'Iqbal',
                'last_name' => 'Makmur',
                'company' => $company1,
                'employee_id' => 'ADM001',
            ],
            [
                'email' => 'admin@nosub.com',
                'first_name' => 'Tania',
                'last_name' => 'Rahma',
                'company' => $company2,
                'employee_id' => 'ADM002',
            ],
            [
                'email' => 'admin3@example.com',
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'company' => $company1,
                'employee_id' => 'ADM003',
            ],
            [
                'email' => 'admin4@example.com',
                'first_name' => 'Sari',
                'last_name' => 'Dewi',
                'company' => $company2,
                'employee_id' => 'ADM004',
            ],
            [
                'email' => 'admin5@example.com',
                'first_name' => 'Rizky',
                'last_name' => 'Ananda',
                'company' => $company1,
                'employee_id' => 'ADM005',
            ],
        ];

        foreach ($adminData as $index => $data) {
            if (!User::where('email', $data['email'])->exists()) {
                $userId = Str::uuid();
                $adminId = Str::uuid();

                User::create([
                    'id' => $userId,
                    'email' => $data['email'],
                    'password' => Hash::make('password123'),
                    'is_admin' => true,
                    'employee_id' => $data['employee_id'],
                ]);

                Admin::create([
                    'id' => $adminId,
                    'user_id' => $userId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'company_id' => $data['company']->id,
                    'branch_id' => $branches[$index]->id, // Assign branch ke admin
                ]);
            }
        }
    }
}
