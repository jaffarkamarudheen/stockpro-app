<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Default Admin & Operator Users
        User::firstOrCreate(
            ['email' => 'admin@stockpro.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'can_access_admin' => true,
                'can_access_app' => true,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@stockpro.com'],
            [
                'name' => 'Store Operator',
                'password' => Hash::make('operator123'),
                'role' => 'operator',
                'can_access_admin' => false,
                'can_access_app' => true,
                'is_active' => true,
            ]
        );
    }
}
