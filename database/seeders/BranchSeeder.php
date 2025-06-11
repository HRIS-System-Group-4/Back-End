<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Models\Company;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->count() < 2) {
            $this->command->warn('Seeder memerlukan minimal 2 company. Jalankan AdminSeeder terlebih dahulu.');
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
                'branch_name' => 'Yogyakarta Branch',
                'location' => 'DIY',
                'latitude' => -7.795580,
                'longitude' => 110.369490,
                'address' => 'Jl. Malioboro No. 1',
                'city' => 'Yogyakarta',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Semarang Branch',
                'location' => 'Central Java',
                'latitude' => -6.966667,
                'longitude' => 110.416664,
                'address' => 'Jl. Pandanaran No. 12',
                'city' => 'Semarang',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Medan Branch',
                'location' => 'North Sumatra',
                'latitude' => 3.595196,
                'longitude' => 98.672226,
                'address' => 'Jl. Gatot Subroto No. 20',
                'city' => 'Medan',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
            [
                'branch_name' => 'Makassar Branch',
                'location' => 'South Sulawesi',
                'latitude' => -5.147665,
                'longitude' => 119.432732,
                'address' => 'Jl. Pengayoman No. 3',
                'city' => 'Makassar',
                'country' => 'Indonesia',
                'status' => 'Active',
            ],
        ];

        // Buat 7 branch tambahan untuk memenuhi total 10
        foreach ($branches as $branch) {
            $company = $companies->random(); // Assign acak ke salah satu company
            Branch::create(array_merge($branch, [
                'id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'location_radius' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
