<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
    {
        // Membuat akun Admin otomatis
        User::create([
            'name' => 'Admin Optik',
            'email' => 'admin@alhazen.com',
            'password' => Hash::make('admin'), // Ganti dengan password yang Anda inginkan
            'role' => 'admin', // Sesuai dengan kolom role di tabel users kita
        ]);
    }
}
