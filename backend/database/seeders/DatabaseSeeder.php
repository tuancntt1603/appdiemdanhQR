<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tạo Cán bộ Quản trị / Giảng viên mẫu
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Quản Trị Viên Hệ Thống',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'canbo@example.com'],
            [
                'name' => 'Thầy Nguyễn Văn An (Giảng viên)',
                'password' => Hash::make('password'),
                'role' => 'can_bo',
            ]
        );
    }
}
