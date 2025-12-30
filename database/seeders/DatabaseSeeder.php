<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        // Faculty User
        User::firstOrCreate(
            ['email' => 'faculty@example.com'],
            [
                'name' => 'Test Faculty',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'faculty',
            ]
        );

        // Student User
        User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Test Student',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'student',
                'student_id' => '2024123456',
                'program_code' => 'CDIM262',
            ]
        );
    }
}
