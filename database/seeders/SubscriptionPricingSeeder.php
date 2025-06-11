<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SubscriptionPricingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subscription_pricings')->insert([
            [
                'id' => Str::uuid(),
                'name' => 'Basic Plan',
                'price' => 50000,
                'duration_in_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Pro Plan',
                'price' => 150000,
                'duration_in_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
}
