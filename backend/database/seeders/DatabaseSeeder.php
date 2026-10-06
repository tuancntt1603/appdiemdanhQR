<?php

namespace Database\Seeders;

use App\Models\Student;
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
        // 1. Tạo hoặc cập nhật Cán bộ Quản trị hệ thống (Admin)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Quản Trị Viên Hệ Thống',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'student_id' => null,
                'is_active' => true,
            ]
        );

        // 2. Tạo hoặc cập nhật Tài khoản Giảng viên
        User::updateOrCreate(
            ['username' => 'giangvien01'],
            [
                'name' => 'Giảng viên Bộ môn',
                'email' => 'giangvien01@example.com',
                'password' => Hash::make('12345678'),
                'role' => 'lecturer',
                'student_id' => null,
                'is_active' => true,
            ]
        );

        // 3. Danh sách 12 sinh viên mẫu
        $students = [
            [
                'ma_sinh_vien' => '1671020001',
                'ho_ten' => 'Nguyễn Văn An',
                'lop' => 'CNTT16-03',
                'email' => 'nguyenvanan@gmail.com',
                'username' => '1671020001',
            ],
            [
                'ma_sinh_vien' => '1671020002',
                'ho_ten' => 'Trần Văn Bình',
                'lop' => 'CNTT16-03',
                'email' => 'tranvanbinh@gmail.com',
                'username' => '1671020002',
            ],
            [
                'ma_sinh_vien' => '1671020003',
                'ho_ten' => 'Lê Minh Cường',
                'lop' => 'CNTT16-03',
                'email' => 'leminhcuong@gmail.com',
                'username' => '1671020003',
            ],
            [
                'ma_sinh_vien' => '1671020004',
                'ho_ten' => 'Phạm Văn Dũng',
                'lop' => 'CNTT16-03',
                'email' => 'phamvandung@gmail.com',
                'username' => '1671020004',
            ],
            [
                'ma_sinh_vien' => '1671020005',
                'ho_ten' => 'Hoàng Anh Đức',
                'lop' => 'CNTT16-03',
                'email' => 'hoanganhduc@gmail.com',
                'username' => '1671020005',
            ],
            [
                'ma_sinh_vien' => '1671020006',
                'ho_ten' => 'Nguyễn Thị Hà',
                'lop' => 'CNTT16-03',
                'email' => 'nguyenthitha@gmail.com',
                'username' => '1671020006',
            ],
            [
                'ma_sinh_vien' => '1671020007',
                'ho_ten' => 'Trần Thị Hương',
                'lop' => 'CNTT16-03',
                'email' => 'tranthihuong@gmail.com',
                'username' => '1671020007',
            ],
            [
                'ma_sinh_vien' => '1671020008',
                'ho_ten' => 'Lê Thị Lan',
                'lop' => 'CNTT16-03',
                'email' => 'lethilan@gmail.com',
                'username' => '1671020008',
            ],
            [
                'ma_sinh_vien' => '1671020009',
                'ho_ten' => 'Phạm Minh Long',
                'lop' => 'CNTT16-03',
                'email' => 'phamminhlong@gmail.com',
                'username' => '1671020009',
            ],
            [
                'ma_sinh_vien' => '1671020010',
                'ho_ten' => 'Đỗ Văn Nam',
                'lop' => 'CNTT16-03',
                'email' => 'dovannam@gmail.com',
                'username' => '1671020010',
            ],
            [
                'ma_sinh_vien' => '1671020011',
                'ho_ten' => 'Nguyễn Minh Phương',
                'lop' => 'CNTT16-03',
                'email' => 'nguyenminhphuong@gmail.com',
                'username' => '1671020011',
            ],
            [
                'ma_sinh_vien' => '1671020012',
                'ho_ten' => 'Bùi Quốc Việt',
                'lop' => 'CNTT16-03',
                'email' => 'buiquocviet@gmail.com',
                'username' => '1671020012',
            ],
        ];

        foreach ($students as $st) {
            // Tạo hoặc cập nhật Student trước
            $student = Student::updateOrCreate(
                ['ma_sinh_vien' => $st['ma_sinh_vien']],
                [
                    'ho_ten' => $st['ho_ten'],
                    'lop' => $st['lop'],
                    'email' => $st['email'],
                    'khoa' => 'CNTT',
                ]
            );

            // Tạo hoặc cập nhật User liên kết với Student
            User::updateOrCreate(
                ['username' => $st['username']],
                [
                    'name' => $st['ho_ten'],
                    'student_id' => $student->id,
                    'email' => $st['email'],
                    'password' => Hash::make('12345678'),
                    'role' => 'student',
                    'is_active' => true,
                ]
            );
        }
    }
}
