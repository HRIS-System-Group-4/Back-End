<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CheckClockSettingSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan seeder untuk admin
        $this->call([
            CompanySeeder::class,
            SubscriptionPricingSeeder::class,
            AdminSeeder::class,
            // SubscriptionSeeder::class,
            CheckClockSettingSeeder::class,
            EmployeeSeeder::class,
            ClockRequestSeeder::class,
            CheckClockSeeder::class,
            BranchSeeder::class,
        ]);
    }
}
