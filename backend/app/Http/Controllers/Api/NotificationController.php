<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AbsenceAlertMail;
use App\Mail\CustomNotificationMail;
use App\Models\Attendance;
use App\Models\PracticeSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    /**
     * Lấy cài đặt gửi email hiện tại
     */
    public function getSettings()
    {
        $autoEmailOnScan = Cache::get('setting_auto_email_on_scan', true);

        return response()->json([
            'success' => true,
            'data' => [
                'auto_email_on_scan' => (bool)$autoEmailOnScan,
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_from' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
            ]
        ]);
    }

    /**
     * Cập nhật cài đặt gửi email
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'auto_email_on_scan' => 'required|boolean',
        ]);

        Cache::forever('setting_auto_email_on_scan', $request->auto_email_on_scan);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cài đặt thông báo email thành công',
            'data' => [
                'auto_email_on_scan' => (bool)$request->auto_email_on_scan,
            ]
        ]);
    }

    /**
     * Gửi email kiểm tra kết nối (Test Mail)
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email nhận',
            'email.email' => 'Địa chỉ email không đúng định dạng',
        ]);

        $recipientEmail = $request->email;

        try {
            $testData = [
                'subject' => '[Test Kết Nối] Kiểm tra hệ thống gửi Email Điểm Danh QR',
                'recipient_name' => 'Quản trị viên / Cán bộ kiểm thử',
                'content' => "Đây là email gửi thử nghiệm từ Hệ thống Điểm danh Sinh viên QR Code.\n\nNếu bạn nhận được email này, cấu hình gửi thư (Mail Transport: " . config('mail.default') . ") đã hoạt động chính xác và sẵn sàng gửi thông báo cho sinh viên.",
                'sender_name' => auth()->user()?->name ?? 'Hệ thống Quản trị',
                'sent_at' => Carbon::now()->format('H:i:s d/m/Y'),
            ];

            Mail::to($recipientEmail)->send(new CustomNotificationMail($testData));

            return response()->json([
                'success' => true,
                'message' => "Đã gửi email thử nghiệm thành công tới: {$recipientEmail} (Driver: " . config('mail.default') . ")",
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi gửi test email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gửi email thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soạn và gửi email thông báo tùy chỉnh
     */
    public function sendCustom(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|in:single,class,session,all',
            'subject' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'student_id' => 'required_if:recipient_type,single|nullable|integer',
            'lop' => 'required_if:recipient_type,class|nullable|string',
            'practice_session_id' => 'required_if:recipient_type,session|nullable|integer',
        ], [
            'subject.required' => 'Vui lòng nhập tiêu đề email',
            'content.required' => 'Vui lòng nhập nội dung thông báo',
            'student_id.required_if' => 'Vui lòng chọn sinh viên cần gửi',
            'lop.required_if' => 'Vui lòng chọn hoặc nhập lớp cần gửi',
            'practice_session_id.required_if' => 'Vui lòng chọn buổi thực hành',
        ]);

        $studentsQuery = Student::whereNotNull('email')->where('email', '!=', '');

        if ($request->recipient_type === 'single') {
            $studentsQuery->where('id', $request->student_id);
        } elseif ($request->recipient_type === 'class') {
            $studentsQuery->where('lop', $request->lop);
        } elseif ($request->recipient_type === 'session') {
            $session = PracticeSession::find($request->practice_session_id);
            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buổi thực hành được chọn không tồn tại',
                ], 404);
            }
            $studentsQuery->where('lop', $session->lop);
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên nào có địa chỉ email hợp lệ theo tiêu chí đã chọn',
            ], 400);
        }

        $sentCount = 0;
        $failedEmails = [];
        $senderName = auth()->user()?->name ?? 'Ban Quản Lý Xưởng';
        $sentAt = Carbon::now()->format('H:i:s d/m/Y');

        foreach ($students as $student) {
            try {
                $mailData = [
                    'subject' => $request->subject,
                    'recipient_name' => $student->ho_ten . ' (' . $student->ma_sinh_vien . ')',
                    'content' => $request->content,
                    'sender_name' => $senderName,
                    'sent_at' => $sentAt,
                ];

                Mail::to($student->email)->send(new CustomNotificationMail($mailData));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Gửi email thông báo thất bại tới {$student->email}: " . $e->getMessage());
                $failedEmails[] = $student->email;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã gửi thành công {$sentCount}/" . count($students) . " email thông báo.",
            'data' => [
                'total_targeted' => count($students),
                'sent_count' => $sentCount,
                'failed_count' => count($failedEmails),
                'failed_emails' => $failedEmails,
            ]
        ]);
    }

    /**
     * Gửi email cảnh báo vắng mặt cho sinh viên chưa điểm danh theo buổi thực hành
     */
    public function sendAbsenceAlert(Request $request)
    {
        $request->validate([
            'practice_session_id' => 'required|integer|exists:practice_sessions,id',
        ], [
            'practice_session_id.required' => 'Vui lòng chọn buổi thực hành',
            'practice_session_id.exists' => 'Buổi thực hành không tồn tại trong hệ thống',
        ]);

        $session = PracticeSession::with('workshop')->findOrFail($request->practice_session_id);

        // Lấy tất cả sinh viên thuộc lớp của buổi thực hành có email
        $allStudents = Student::where('lop', $session->lop)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($allStudents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Lớp {$session->lop} không có sinh viên nào có địa chỉ email để gửi thông báo",
            ], 400);
        }

        // Lấy danh sách ID sinh viên đã điểm danh buổi này
        $attendedStudentIds = Attendance::where('practice_session_id', $session->id)
            ->pluck('student_id')
            ->toArray();

        // Lọc các sinh viên chưa điểm danh
        $absentStudents = $allStudents->filter(function ($s) use ($attendedStudentIds) {
            return ! in_array($s->id, $attendedStudentIds);
        });

        if ($absentStudents->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tất cả sinh viên trong lớp đều đã điểm danh buổi này! Không có sinh viên vắng mặt.',
                'data' => [
                    'absent_count' => 0,
                    'sent_count' => 0,
                ]
            ]);
        }

        $sentCount = 0;
        $failedEmails = [];
        $sessionTimeStr = Carbon::parse($session->ngay_hoc)->format('d/m/Y') . ' (' . substr($session->gio_bat_dau, 0, 5) . ' - ' . substr($session->gio_ket_thuc, 0, 5) . ')';

        foreach ($absentStudents as $student) {
            try {
                $alertData = [
                    'student_name' => $student->ho_ten,
                    'ma_sinh_vien' => $student->ma_sinh_vien,
                    'lop' => $student->lop,
                    'session_name' => $session->ten_buoi,
                    'mon_hoc' => $session->mon_hoc,
                    'workshop_name' => $session->workshop?->ten_xuong ?? 'Xưởng thực hành',
                    'session_time' => $sessionTimeStr,
                ];

                Mail::to($student->email)->send(new AbsenceAlertMail($alertData));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Gửi email cảnh báo vắng mặt thất bại tới {$student->email}: " . $e->getMessage());
                $failedEmails[] = $student->email;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã gửi email cảnh báo vắng mặt thành công cho {$sentCount}/" . count($absentStudents) . " sinh viên chưa điểm danh.",
            'data' => [
                'total_absent' => count($absentStudents),
                'sent_count' => $sentCount,
                'failed_count' => count($failedEmails),
            ]
        ]);
    }
}
