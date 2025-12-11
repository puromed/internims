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
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'student',
            ]
        );

        User::firstOrCreate(
            ['email' => 'faculty@example.com'],
            [
                'name' => ' Test Faculty ',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'faculty',
            ]
        );
    }
}
