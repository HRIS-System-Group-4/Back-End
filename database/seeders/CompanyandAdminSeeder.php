<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Branch;

class CompanyAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // --- STEP 1: Create 5 Companies (tanpa ubah ID jika sudah ada) ---
        $companyData = [
            ['hris', 'HRIS Company', 'HRIS internal system', -6.200000, 106.816666],
            ['nosub', 'NoSub Company', 'Company tanpa subscription', -7.000000, 110.000000],
            ['alpha', 'Alpha Solutions', 'Perusahaan IT konsultan', -6.914744, 107.609810],
            ['beta', 'Beta Corp', 'Startup digital marketing', -6.175392, 106.827153],
            ['gamma', 'Gamma Group', 'Perusahaan konstruksi', -6.121435, 106.774124],
        ];

        $companies = [];
        foreach ($companyData as $data) {
            $existing = Company::where('company_username', $data[0])->first();
            if (!$existing) {
                $existing = Company::create([
                    'id' => Str::uuid(),
                    'company_username' => $data[0],
                    'company_name' => $data[1],
                    'description' => $data[2],
                    'latitude' => $data[3],
                    'longitude' => $data[4],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $companies[] = $existing;
        }

        // --- STEP 2: Create 10 Branches (acak perusahaan untuk tiap branch) ---
        $branchData = [
            ['Jakarta Head Office', 'Central Jakarta', -6.2000000, 106.8166667, 'Jl. Sudirman No. 1', 'Jakarta'],
            ['Bandung Branch', 'West Java', -6.914744, 107.609810, 'Jl. Asia Afrika No. 50', 'Bandung'],
            ['Surabaya Branch', 'East Java', -7.257472, 112.752088, 'Jl. Pemuda No. 10', 'Surabaya'],
            ['Semarang Branch', 'Central Java', -7.005145, 110.438125, 'Jl. Pandanaran No. 5', 'Semarang'],
            ['Medan Branch', 'North Sumatra', 3.595196, 98.672223, 'Jl. Gatot Subroto No. 30', 'Medan'],
            ['Denpasar Branch', 'Bali', -8.650000, 115.216667, 'Jl. Teuku Umar No. 15', 'Denpasar'],
            ['Makassar Branch', 'South Sulawesi', -5.147665, 119.432732, 'Jl. Ahmad Yani No. 20', 'Makassar'],
            ['Yogyakarta Branch', 'DI Yogyakarta', -7.801194, 110.364917, 'Jl. Malioboro No. 12', 'Yogyakarta'],
            ['Pontianak Branch', 'West Kalimantan', -0.026330, 109.342503, 'Jl. Gajah Mada No. 8', 'Pontianak'],
            ['Padang Branch', 'West Sumatra', -0.947083, 100.417181, 'Jl. Veteran No. 9', 'Padang'],
        ];

        $branches = [];
        foreach ($branchData as $data) {
            $company = $companies[array_rand($companies)];

            $branch = Branch::create([
                'id' => Str::uuid(),
                'branch_name' => $data[0],
                'location' => $data[1],
                'latitude' => $data[2],
                'longitude' => $data[3],
                'address' => $data[4],
                'city' => $data[5],
                'country' => 'Indonesia',
                'status' => 'Active',
                'company_id' => $company->id,
                'location_radius' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $branches[] = $branch;
        }

        // --- STEP 3: Create 5 Admins connected to random branches ---
        $adminData = [
            ['iqbal.makmur@company.com', 'Iqbal', 'Makmur'],
            ['tania.rahma@company.com', 'Tania', 'Rahma'],
            ['adi.putra@company.com', 'Adi', 'Putra'],
            ['nina.agustina@company.com', 'Nina', 'Agustina'],
            ['reza.maulana@company.com', 'Reza', 'Maulana'],
        ];

        foreach ($adminData as $index => $data) {
            if (User::where('email', $data[0])->exists()) continue;

            $userId = Str::uuid();
            $adminId = Str::uuid();
            $randomBranch = $branches[$index % count($branches)];

            User::create([
                'id' => $userId,
                'email' => $data[0],
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'employee_id' => 'ADM00' . ($index + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Admin::create([
                'id' => $adminId,
                'user_id' => $userId,
                'first_name' => $data[1],
                'last_name' => $data[2],
                'company_id' => $randomBranch->company_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- STEP 4: Create admin for company HRIS with specific email ---
        $hrisCompany = Company::where('company_username', 'hris')->first();
        if ($hrisCompany && !User::where('email', 'lalasipo20@gmail.com')->exists()) {
            $userId = Str::uuid();
            $adminId = Str::uuid();

            // Cari 1 branch milik company HRIS
            $hrisBranch = Branch::where('company_id', $hrisCompany->id)->first();

            User::create([
                'id' => $userId,
                'email' => 'lalasipo20@gmail.com',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'employee_id' => 'ADM999',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Admin::create([
                'id' => $adminId,
                'user_id' => $userId,
                'first_name' => 'Lala',
                'last_name' => 'Sipo',
                'company_id' => $hrisCompany->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
