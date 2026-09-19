<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\PracticeSession;
use App\Models\QrToken;
use App\Models\Student;
use App\Models\User;
use App\Models\Workshop;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Workshop $workshop;
    protected PracticeSession $practiceSession;
    protected User $admin;
    protected User $canBo;
    protected User $sinhVien;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $this->canBo = User::create([
            'name' => 'Giảng viên Test',
            'email' => 'canbo_test@example.com',
            'password' => bcrypt('password'),
            'role' => 'can_bo'
        ]);

        $this->sinhVien = User::create([
            'name' => 'SV999',
            'email' => 'sv999@example.com',
            'password' => bcrypt('password'),
            'role' => 'sinh_vien'
        ]);

        $this->workshop = Workshop::create([
            'ten_xuong' => 'Xưởng Mạng Test',
            'dia_diem' => 'Phòng 301'
        ]);

        $this->student = Student::create([
            'ma_sinh_vien' => 'SV999',
            'ho_ten' => 'Nguyễn Văn Test',
            'email' => 'sv999@example.com',
            'lop' => 'D21CNTT01',
            'khoa' => 'CNTT'
        ]);

        $now = Carbon::now();
        $this->practiceSession = PracticeSession::create([
            'ten_buoi' => 'Buổi Test Thực Hành',
            'mon_hoc' => 'Mạng Máy Tính',
            'lop' => 'D21CNTT01',
            'workshop_id' => $this->workshop->id,
            'user_id' => $this->canBo->id,
            'ngay_hoc' => $now->toDateString(),
            'gio_bat_dau' => $now->copy()->subMinutes(15)->format('H:i'),
            'gio_ket_thuc' => $now->copy()->addHours(2)->format('H:i'),
            'trang_thai' => 'dang_dien_ra'
        ]);
    }

    /**
     * Test sinh QR Token 90 giây thành công
     */
    public function test_generate_qr_token_valid_90s(): void
    {
        $response = $this->postJson('/api/qr/generate', [
            'student_id' => $this->student->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'expires_in_seconds' => 90
                ]
            ]);

        $this->assertDatabaseHas('qr_tokens', [
            'student_id' => $this->student->id
        ]);
    }

    /**
     * Test quét QR điểm danh vào và điểm danh ra (Check-in / Check-out)
     */
    public function test_scan_qr_check_in_and_check_out(): void
    {
        $token = Str::random(40);
        QrToken::create([
            'student_id' => $this->student->id,
            'token' => $token,
            'expires_at' => Carbon::now()->addSeconds(90),
        ]);

        $qrData = json_encode([
            'student_id' => $this->student->id,
            'ma_sinh_vien' => $this->student->ma_sinh_vien,
            'token' => $token,
        ]);

        // 1. Quét lần 1: Check-in (Vào)
        $resCheckIn = $this->postJson('/api/attendance/scan', [
            'qr_data' => $qrData,
            'workshop_id' => $this->workshop->id,
            'practice_session_id' => $this->practiceSession->id,
        ]);

        $resCheckIn->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'check_in',
            ]);

        // Giả lập sau 15 giây để vượt qua cooldown 10s
        $attendance = Attendance::where('student_id', $this->student->id)->first();
        $attendance->update([
            'check_in' => Carbon::now()->subSeconds(15)
        ]);

        // 2. Quét lần 2: Check-out (Ra)
        $resCheckOut = $this->postJson('/api/attendance/scan', [
            'qr_data' => $qrData,
            'workshop_id' => $this->workshop->id,
            'practice_session_id' => $this->practiceSession->id,
        ]);

        $resCheckOut->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'check_out',
            ]);

        // 3. Quét lần 3: Thông báo đã hoàn thành
        $resCompleted = $this->postJson('/api/attendance/scan', [
            'qr_data' => $qrData,
            'workshop_id' => $this->workshop->id,
            'practice_session_id' => $this->practiceSession->id,
        ]);

        $resCompleted->assertJson([
            'type' => 'completed',
        ]);
    }

    /**
     * Test chống QR hết hạn 90 giây
     */
    public function test_reject_expired_qr_token(): void
    {
        $expiredToken = Str::random(40);
        QrToken::create([
            'student_id' => $this->student->id,
            'token' => $expiredToken,
            'expires_at' => Carbon::now()->subSeconds(5), // đã hết hạn
        ]);

        $qrData = json_encode([
            'student_id' => $this->student->id,
            'ma_sinh_vien' => $this->student->ma_sinh_vien,
            'token' => $expiredToken,
        ]);

        $response = $this->postJson('/api/attendance/scan', [
            'qr_data' => $qrData,
            'workshop_id' => $this->workshop->id,
            'practice_session_id' => $this->practiceSession->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }

    /**
     * Test chống sinh viên sai lớp điểm danh vào buổi thực hành
     */
    public function test_reject_student_wrong_class(): void
    {
        $otherStudent = Student::create([
            'ma_sinh_vien' => 'SV888',
            'ho_ten' => 'Khác Lớp',
            'lop' => 'D21CNTT99', // Không thuộc D21CNTT01
        ]);

        $token = Str::random(40);
        QrToken::create([
            'student_id' => $otherStudent->id,
            'token' => $token,
            'expires_at' => Carbon::now()->addSeconds(90),
        ]);

        $qrData = json_encode([
            'student_id' => $otherStudent->id,
            'ma_sinh_vien' => $otherStudent->ma_sinh_vien,
            'token' => $token,
        ]);

        $response = $this->postJson('/api/attendance/scan', [
            'qr_data' => $qrData,
            'workshop_id' => $this->workshop->id,
            'practice_session_id' => $this->practiceSession->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }

    /**
     * Test phân quyền: Sinh viên không có quyền xóa buổi thực hành
     */
    public function test_role_permission_restriction(): void
    {
        $studentToken = $this->sinhVien->createToken('token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$studentToken}")
            ->deleteJson("/api/practice-sessions/{$this->practiceSession->id}");

        $response->assertStatus(403);
    }
}
