<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\QrToken;
use App\Models\Student;
use App\Models\User;
use App\Models\Workshop;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tạo Cán bộ Quản trị / Giảng viên demo
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Cán bộ Quản lý Xưởng',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'canbo@example.com'],
            [
                'name' => 'Thầy Nguyễn Văn An (Cán bộ coi xưởng)',
                'password' => Hash::make('password'),
                'role' => 'can_bo',
            ]
        );

        // 2. Tạo Xưởng thực hành mẫu
        $workshop1 = Workshop::updateOrCreate(
            ['ten_xuong' => 'Xưởng thực hành CNTT'],
            ['dia_diem' => 'Tòa nhà C - Phòng C301']
        );

        $workshop2 = Workshop::updateOrCreate(
            ['ten_xuong' => 'Xưởng thực hành Điện - Điện tử'],
            ['dia_diem' => 'Tòa nhà B - Phòng B102']
        );

        // 3. Tạo 10 Sinh viên Demo theo yêu cầu (SV001 -> SV010)
        $studentsData = [
            ['ma_sinh_vien' => 'SV001', 'ho_ten' => 'Nguyễn Văn Hùng', 'email' => 'sv001@example.com', 'lop' => 'D21CNTT01', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV002', 'ho_ten' => 'Trần Thị Mai', 'email' => 'sv002@example.com', 'lop' => 'D21CNTT01', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV003', 'ho_ten' => 'Lê Minh Khôi', 'email' => 'sv003@example.com', 'lop' => 'D21CNTT01', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV004', 'ho_ten' => 'Phạm Hoàng Long', 'email' => 'sv004@example.com', 'lop' => 'D21CNTT02', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV005', 'ho_ten' => 'Vũ Hải Yến', 'email' => 'sv005@example.com', 'lop' => 'D21CNTT02', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV006', 'ho_ten' => 'Đỗ Tuấn Anh', 'email' => 'sv006@example.com', 'lop' => 'D21CNTT02', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV007', 'ho_ten' => 'Bùi Thu Trang', 'email' => 'sv007@example.com', 'lop' => 'D21CNTT03', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV008', 'ho_ten' => 'Ngô Quốc Bảo', 'email' => 'sv008@example.com', 'lop' => 'D21CNTT03', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV009', 'ho_ten' => 'Dương Thúy Nga', 'email' => 'sv009@example.com', 'lop' => 'D21CNTT03', 'khoa' => 'Công nghệ Thông tin'],
            ['ma_sinh_vien' => 'SV010', 'ho_ten' => 'Hoàng Đức Trọng', 'email' => 'sv010@example.com', 'lop' => 'D21CNTT01', 'khoa' => 'Công nghệ Thông tin'],
        ];

        $createdStudents = [];
        foreach ($studentsData as $item) {
            $student = Student::updateOrCreate(
                ['ma_sinh_vien' => $item['ma_sinh_vien']],
                $item
            );
            $createdStudents[] = $student;

            // Tạo sẵn 1 token có hiệu lực 90s cho mỗi sinh viên để test ngay
            QrToken::create([
                'student_id' => $student->id,
                'token' => Str::random(40),
                'expires_at' => Carbon::now()->addSeconds(90),
            ]);
        }

        // 4. Tạo dữ liệu điểm danh demo trong tuần qua và hôm nay
        $today = Carbon::today();

        // 3 sinh viên đã check_in và check_out hôm nay (hoàn thành)
        Attendance::create([
            'student_id' => $createdStudents[0]->id,
            'workshop_id' => $workshop1->id,
            'check_in' => $today->copy()->setTime(7, 30, 0),
            'check_out' => $today->copy()->setTime(11, 25, 0),
            'status' => 'hoan_thanh',
        ]);

        Attendance::create([
            'student_id' => $createdStudents[1]->id,
            'workshop_id' => $workshop1->id,
            'check_in' => $today->copy()->setTime(7, 40, 0),
            'check_out' => $today->copy()->setTime(11, 30, 0),
            'status' => 'hoan_thanh',
        ]);

        // 2 sinh viên mới check_in đang ở trong xưởng thực hành
        Attendance::create([
            'student_id' => $createdStudents[2]->id,
            'workshop_id' => $workshop1->id,
            'check_in' => $today->copy()->setTime(8, 0, 0),
            'check_out' => null,
            'status' => 'dung_gio',
        ]);

        Attendance::create([
            'student_id' => $createdStudents[3]->id,
            'workshop_id' => $workshop1->id,
            'check_in' => $today->copy()->setTime(8, 15, 0),
            'check_out' => null,
            'status' => 'dung_gio',
        ]);

        // Tạo dữ liệu các ngày trước để có biểu đồ sinh động
        for ($day = 1; $day <= 5; $day++) {
            $pastDate = $today->copy()->subDays($day);
            // Bỏ qua chủ nhật
            if ($pastDate->isSunday()) continue;

            for ($s = 0; $s < 8; $s++) {
                Attendance::create([
                    'student_id' => $createdStudents[$s]->id,
                    'workshop_id' => $workshop1->id,
                    'check_in' => $pastDate->copy()->setTime(7, 30 + rand(0, 30), 0),
                    'check_out' => $pastDate->copy()->setTime(11, 15 + rand(0, 30), 0),
                    'status' => 'hoan_thanh',
                ]);
            }
        }
    }
}
