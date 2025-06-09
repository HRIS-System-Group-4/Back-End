<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Branch;
use App\Models\Company;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::inRandomOrder()->first();

        if (!$company) {
            $this->command->warn('Tidak ada company ditemukan. Jalankan CompanySeeder terlebih dahulu.');
            return;
        }

        $branches = [
            [
                'branch_name' => 'Jakarta Head Office',
                'location' => 'Central Jakarta',
                'latitude' => -6.2000000,
                'longitude' => 106.8166667,
                'address' => 'Jl. Sudirman No. 1',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Bandung Branch',
                'location' => 'West Java',
                'latitude' => -6.914744,
                'longitude' => 107.609810,
                'address' => 'Jl. Asia Afrika No. 50',
                'city' => 'Bandung',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Surabaya Branch',
                'location' => 'East Java',
                'latitude' => -7.257472,
                'longitude' => 112.752088,
                'address' => 'Jl. Pemuda No. 10',
                'city' => 'Surabaya',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Medan Branch',
                'location' => 'North Sumatra',
                'latitude' => 3.595195,
                'longitude' => 98.672222,
                'address' => 'Jl. Gatot Subroto No. 12',
                'city' => 'Medan',
                'country' => 'Indonesia',
                'status' => 'Inactive',
            ],
            [
                'branch_name' => 'Denpasar Branch',
                'location' => 'Bali',
                'latitude' => -8.670458,
                'longitude' => 115.212629,
                'address' => 'Jl. Raya Puputan No. 9',
                'city' => 'Denpasar',
                'country' => 'Indonesia',
                'status' => 'Inactive',
            ],
        ];

        foreach ($branches as $data) {
            Branch::create(array_merge($data, [
                'id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'location_radius' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Seeder untuk 5 Branch berhasil dijalankan.');
    }
}
