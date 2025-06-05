<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan company tersedia
        $company = DB::table('company')->where('company_username', 'hris')->first();

        if (!$company) {
            $companyId = Str::uuid();
            DB::table('company')->insert([
                'id' => $companyId,
                'company_name' => 'HRIS Company',
                'company_username' => 'hris',
                'description' => 'HRIS internal system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $company->id;
        }

        // Buat user admin
        $userId = Str::uuid();
        $adminId = Str::uuid();

        if (!User::where('email', 'admin@hris.com')->exists()) {
            User::create([
                'id' => $userId,
                'email' => 'lalasipo20@gmail.com',
                'password' => Hash::make('password123'), // ganti sesuai kebutuhan
                'is_admin' => true,
                'employee_id'  => 'ADM001',
            ]);

            Admin::create([
                'id' => $adminId,
                'user_id' => $userId,
                'first_name' => 'Iqbal',
                'last_name' => 'Makmur',
                'company_id' => $companyId,
            ]);
        }
    }
}
