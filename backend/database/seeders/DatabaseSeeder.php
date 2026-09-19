<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\PracticeSession;
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
        // 1. Tạo Cán bộ Quản trị / Giảng viên / Sinh viên demo theo 3 vai trò
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Quản Trị Viên Hệ Thống',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $lecturer = User::updateOrCreate(
            ['email' => 'canbo@example.com'],
            [
                'name' => 'Thầy Nguyễn Văn An (Giảng viên)',
                'password' => Hash::make('password'),
                'role' => 'can_bo',
            ]
        );

        // Tài khoản sinh viên demo để đăng nhập kiểm tra phân quyền
        $studentUser = User::updateOrCreate(
            ['email' => 'sv001@example.com'],
            [
                'name' => 'SV001',
                'password' => Hash::make('password'),
                'role' => 'sinh_vien',
            ]
        );

        // 2. Tạo Xưởng thực hành mẫu
        $workshop1 = Workshop::updateOrCreate(
            ['ten_xuong' => 'Xưởng thực hành CNTT & Phần mềm'],
            ['dia_diem' => 'Tòa nhà C - Phòng C301']
        );

        $workshop2 = Workshop::updateOrCreate(
            ['ten_xuong' => 'Xưởng Mạng máy tính & Viễn thông'],
            ['dia_diem' => 'Tòa nhà B - Phòng B102']
        );

        $workshop3 = Workshop::updateOrCreate(
            ['ten_xuong' => 'Xưởng Điện tử & Nhúng IoT cơ sở'],
            ['dia_diem' => 'Tòa nhà A - Phòng A205']
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

        // 4. Tạo các buổi thực hành mẫu (hôm nay và các ngày trong tuần)
        $today = Carbon::today();

        $sessionToday1 = PracticeSession::create([
            'ten_buoi' => 'Buổi 1: Cấu hình Mạng LAN & VLAN',
            'mon_hoc' => 'Mạng Máy Tính Cơ Bản',
            'lop' => 'D21CNTT01',
            'workshop_id' => $workshop1->id,
            'user_id' => $lecturer->id,
            'ngay_hoc' => $today->toDateString(),
            'gio_bat_dau' => '07:30',
            'gio_ket_thuc' => '11:30',
            'trang_thai' => 'dang_dien_ra',
            'ghi_chu' => 'Sinh viên mang theo laptop và tài liệu bài thực hành số 1',
        ]);

        $sessionToday2 = PracticeSession::create([
            'ten_buoi' => 'Buổi 1: Lập trình Socket TCP/IP',
            'mon_hoc' => 'Lập trình Mạng Nâng Cao',
            'lop' => 'D21CNTT02',
            'workshop_id' => $workshop2->id,
            'user_id' => $lecturer->id,
            'ngay_hoc' => $today->toDateString(),
            'gio_bat_dau' => '13:00',
            'gio_ket_thuc' => '17:00',
            'trang_thai' => 'sap_dien_ra',
            'ghi_chu' => 'Thực hành kết nối client-server',
        ]);

        $sessionPast = PracticeSession::create([
            'ten_buoi' => 'Buổi chuẩn bị & An toàn phòng xưởng',
            'mon_hoc' => 'Nội quy xưởng thực hành',
            'lop' => 'D21CNTT01',
            'workshop_id' => $workshop1->id,
            'user_id' => $lecturer->id,
            'ngay_hoc' => $today->copy()->subDays(2)->toDateString(),
            'gio_bat_dau' => '07:30',
            'gio_ket_thuc' => '11:30',
            'trang_thai' => 'da_ket_thuc',
            'ghi_chu' => 'Phổ biến quy chế thực tập xưởng',
        ]);

        // 5. Tạo dữ liệu điểm danh mẫu gắn liền: Sinh viên + Lớp + Buổi thực hành + Xưởng
        // 2 sinh viên lớp D21CNTT01 đã check-in và check-out hoàn thành hôm nay
        Attendance::create([
            'student_id' => $createdStudents[0]->id, // SV001
            'workshop_id' => $workshop1->id,
            'practice_session_id' => $sessionToday1->id,
            'check_in' => $today->copy()->setTime(7, 30, 0),
            'check_out' => $today->copy()->setTime(11, 25, 0),
            'status' => 'hoan_thanh',
        ]);

        Attendance::create([
            'student_id' => $createdStudents[1]->id, // SV002
            'workshop_id' => $workshop1->id,
            'practice_session_id' => $sessionToday1->id,
            'check_in' => $today->copy()->setTime(7, 40, 0),
            'check_out' => $today->copy()->setTime(11, 30, 0),
            'status' => 'hoan_thanh',
        ]);

        // 1 sinh viên lớp D21CNTT01 mới check_in đang ở xưởng
        Attendance::create([
            'student_id' => $createdStudents[2]->id, // SV003
            'workshop_id' => $workshop1->id,
            'practice_session_id' => $sessionToday1->id,
            'check_in' => $today->copy()->setTime(7, 50, 0),
            'check_out' => null,
            'status' => 'muon',
        ]);

        // 1 sinh viên check-in đúng giờ
        Attendance::create([
            'student_id' => $createdStudents[9]->id, // SV010 (D21CNTT01)
            'workshop_id' => $workshop1->id,
            'practice_session_id' => $sessionToday1->id,
            'check_in' => $today->copy()->setTime(7, 32, 0),
            'check_out' => null,
            'status' => 'dung_gio',
        ]);

        // Tạo dữ liệu các ngày trước gắn với sessionPast để có biểu đồ sinh động
        for ($s = 0; $s < 3; $s++) {
            Attendance::create([
                'student_id' => $createdStudents[$s]->id,
                'workshop_id' => $workshop1->id,
                'practice_session_id' => $sessionPast->id,
                'check_in' => $today->copy()->subDays(2)->setTime(7, 30 + rand(0, 20), 0),
                'check_out' => $today->copy()->subDays(2)->setTime(11, 15 + rand(0, 20), 0),
                'status' => 'hoan_thanh',
            ]);
        }
    }
}
