<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'user',
                'email' => 'patient@example.com',
                'password' => bcrypt('password123'),
                'phone' => '0123056789',
                'role' => 'patient',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'assistant',
                'email' => 'admin@example.com',
                'password' => bcrypt('password123'),
                'phone' => '0123416789',
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'doctor',
                'email' => 'doctor@example.com',
                'password' => bcrypt('password124'),
                'phone' => '0123456789',
                'role' => 'doctor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
