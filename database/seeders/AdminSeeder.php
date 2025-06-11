<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Admin;
use App\Models\Company;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Company dengan subscription (HRIS)
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

        // 2. Company tanpa subscription
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

        // 3. Admin untuk HRIS Company
        if (!User::where('email', 'admin@hris.com')->exists()) {
            $userId1 = Str::uuid();
            $adminId1 = Str::uuid();

            User::create([
                'id' => $userId1,
                'email' => 'lalasipo20@gmail.com',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'employee_id' => 'ADM001',
            ]);

            Admin::create([
                'id' => $adminId1,
                'user_id' => $userId1,
                'first_name' => 'Iqbal',
                'last_name' => 'Makmur',
                'company_id' => $company1->id,
            ]);
        }

        // 4. Admin untuk NoSub Company
        if (!User::where('email', 'admin@nosub.com')->exists()) {
            $userId2 = Str::uuid();
            $adminId2 = Str::uuid();

            User::create([
                'id' => $userId2,
                'email' => 'admin@nosub.com',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'employee_id' => 'ADM002',
            ]);

            Admin::create([
                'id' => $adminId2,
                'user_id' => $userId2,
                'first_name' => 'Tania',
                'last_name' => 'Rahma',
                'company_id' => $company2->id,
            ]);
        }
    }
}
