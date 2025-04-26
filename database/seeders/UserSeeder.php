<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'id' => Str::uuid(),
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
            'company' => 'hris',
        ]);

        // Employee Bruh
        User::create([
            'id' => 'EMP001',
            'email' => 'amanda@gmail.com',
            'password' => Hash::make('amanda123'),
            'is_admin' => false,
            'company' => 'hris',
        ]);

        User::create([
            'id' => 'EMP002',
            'email' => 'trio@gmail.com',
            'password' => Hash::make('trio123'),
            'is_admin' => false,
            'company' => 'jti',
        ]);
    }
}
