<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPricing;

class SubscriptionSeeder extends Seeder
{
    // public function run(): void
    // {
    //     $company = Company::where('company_username', 'hris')->first();
    //     $pricing = SubscriptionPricing::where('name', 'Basic Plan')->first();

    //     if ($company && $pricing) {
    //         Subscription::firstOrCreate([
    //             'company_id' => $company->id,
    //             'subscription_pricing_id' => $pricing->id,
    //         ], [
    //             'id' => Str::uuid(),
    //             'start_date' => now(),
    //             'end_date' => now()->addDays($pricing->duration_in_days),
    //             'is_active' => true,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //     }
    // }
}
