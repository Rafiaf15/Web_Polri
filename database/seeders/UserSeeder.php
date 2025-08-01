<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama terlebih dahulu untuk menghindari duplikasi
        \App\Models\User::truncate();

        // Buat user admin default
        \App\Models\User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'administrator',
            'created_at' => now(),
            'last_login' => now()
        ]);

        // Buat user tambahan untuk testing
        \App\Models\User::create([
            'name' => 'User Test',
            'username' => 'user',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'created_at' => now(),
            'last_login' => now()
        ]);
    }
} 