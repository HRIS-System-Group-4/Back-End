<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('company')->where('company_username', 'hris')->exists()) {
            DB::table('company')->insert([
                'id' => Str::uuid(),
                'company_name' => 'HRIS Company',
                'company_username' => 'hris',
                'description' => 'HRIS internal system',
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
