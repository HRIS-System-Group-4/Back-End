<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Update atau insert company dengan username 'hris'
        DB::table('company')->updateOrInsert(
            ['company_username' => 'hris'],
            [
                'id' => Str::uuid(),
                'company_name' => 'Bebas Tech Corp',
                'description' => 'Perusahaan bebas yang digunakan untuk testing sistem',
                'latitude' => '-6.210000',
                'longitude' => '106.820000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Tambahkan company kedua jika belum ada
        DB::table('company')->updateOrInsert(
            ['company_username' => 'alpha'],
            [
                'id' => Str::uuid(),
                'company_name' => 'Alpha Solutions',
                'description' => 'Perusahaan IT konsultan',
                'latitude' => '-6.914744',
                'longitude' => '107.609810',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Tambahkan company ketiga jika belum ada
        DB::table('company')->updateOrInsert(
            ['company_username' => 'beta'],
            [
                'id' => Str::uuid(),
                'company_name' => 'Beta Logistics',
                'description' => 'Perusahaan logistik dan distribusi nasional',
                'latitude' => '-7.257472',
                'longitude' => '112.752088',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
