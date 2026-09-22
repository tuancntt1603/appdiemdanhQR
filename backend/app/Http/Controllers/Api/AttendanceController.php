<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PracticeSession;
use App\Models\QrToken;
use App\Models\Student;
use App\Models\Workshop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Danh sách lịch sử điểm danh với bộ lọc:
     * theo ngày, theo tuần, theo tháng, theo sinh viên, theo lớp, theo buổi thực hành
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['student', 'workshop', 'practiceSession']);

        // Bộ lọc theo buổi thực hành
        if ($request->filled('practice_session_id')) {
            $query->where('practice_session_id', $request->practice_session_id);
        }

        // Bộ lọc theo xưởng
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        // Bộ lọc theo ngày cụ thể (YYYY-MM-DD)
        if ($request->filled('date')) {
            $query->whereDate('check_in', $request->date);
        }

        // Bộ lọc theo khoảng ngày (start_date, end_date)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('check_in', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Bộ lọc theo tuần (week_offset: 0 là tuần này, 1 là tuần trước...)
        if ($request->filled('week')) {
            $weekDate = Carbon::now()->startOfWeek()->subWeeks((int)$request->week);
            $query->whereBetween('check_in', [
                $weekDate->copy()->startOfWeek(),
                $weekDate->copy()->endOfWeek(),
            ]);
        }

        // Bộ lọc theo tháng (YYYY-MM)
        if ($request->filled('month')) {
            $parsedMonth = Carbon::parse($request->month);
            $query->whereYear('check_in', $parsedMonth->year)
                  ->whereMonth('check_in', $parsedMonth->month);
        }

        // Bộ lọc theo sinh viên
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Bộ lọc theo mã sinh viên
        if ($request->filled('ma_sinh_vien')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('ma_sinh_vien', $request->ma_sinh_vien);
            });
        }

        // Bộ lọc theo lớp
        if ($request->filled('lop')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('lop', $request->lop);
            });
        }

        $attendances = $query->orderBy('check_in', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách điểm danh thành công',
            'data' => $attendances
        ]);
    }

    /**
     * Lịch sử điểm danh của 1 sinh viên cụ thể
     */
    public function getStudentAttendance($id)
    {
        $attendances = Attendance::with(['workshop', 'practiceSession'])
            ->where('student_id', $id)
            ->orderBy('check_in', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy lịch sử điểm danh của sinh viên thành công',
            'data' => $attendances
        ]);
    }

    /**
     * Quét QR điểm danh (Xử lý thông minh: tự động vào hoặc ra)
     * Ràng buộc:
     * 1. Kiểm tra format JSON và tính hợp lệ của sinh viên
     * 2. Kiểm tra token cá nhân và thời hạn 90 giây
     * 3. Nếu gắn với Buổi thực hành:
     *    - Kiểm tra sinh viên có thuộc đúng Lớp của buổi thực hành không
     *    - Kiểm tra thời gian quét có nằm trong khung giờ của buổi học không (cho phép sớm 30p, muộn 45p)
     *    - Tự động gán workshop_id từ buổi học nếu chưa có
     * 4. Quét lần 1 trong buổi => Check-in (VÀO)
     * 5. Quét lần 2 trong buổi => Check-out (RA)
     * 6. Quét từ lần 3 trở đi => Thông báo đã hoàn thành
     * 7. Chống quét trùng liên tục trong vòng 10 giây
     */
    public function scanQr(Request $request)
    {
        $rawQr = $request->input('qr_data');
        $workshopId = $request->input('workshop_id');
        $practiceSessionId = $request->input('practice_session_id');

        if (! $rawQr) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp mã QR'
            ], 400);
        }

        // 1. Validate payload JSON
        $decoded = is_array($rawQr) ? $rawQr : json_decode($rawQr, true);
        if (! $decoded || ! isset($decoded['student_id'], $decoded['token'], $decoded['ma_sinh_vien'])) {
            return response()->json([
                'success' => false,
                'message' => 'Mã QR không đúng định dạng của hệ thống điểm danh'
            ], 400);
        }

        // 2. Kiểm tra sinh viên
        $student = Student::find($decoded['student_id']);
        if (! $student || $student->ma_sinh_vien !== $decoded['ma_sinh_vien']) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin sinh viên không hợp lệ hoặc không tồn tại'
            ], 404);
        }

        // 3. Kiểm tra token có tồn tại và thuộc đúng sinh viên không
        $qrToken = QrToken::where('student_id', $student->id)
            ->where('token', $decoded['token'])
            ->first();

        if (! $qrToken) {
            return response()->json([
                'success' => false,
                'message' => 'Mã token QR không tồn tại hoặc không hợp lệ'
            ], 400);
        }

        // 4. Kiểm tra thời hạn 90 giây (Server side time)
        if (Carbon::now()->greaterThan($qrToken->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã QR đã hết hạn (chỉ có hiệu lực trong 90 giây). Vui lòng tạo mã mới!'
            ], 400);
        }

        // 5. Kiểm tra Buổi thực hành (nếu được chọn)
        $practiceSession = null;
        if ($practiceSessionId) {
            $practiceSession = PracticeSession::find($practiceSessionId);
            if (! $practiceSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buổi thực hành được chọn không tồn tại'
                ], 404);
            }

            // Gắn workshop_id từ buổi học
            if (! $workshopId && $practiceSession->workshop_id) {
                $workshopId = $practiceSession->workshop_id;
            }

            // RÀNG BUỘC: Kiểm tra sinh viên có thuộc đúng lớp của buổi học không
            if (trim(strtoupper($student->lop)) !== trim(strtoupper($practiceSession->lop))) {
                return response()->json([
                    'success' => false,
                    'message' => "Sinh viên thuộc lớp {$student->lop}, không thuộc lớp {$practiceSession->lop} của buổi thực hành này!"
                ], 400);
            }

            // RÀNG BUỘC: Kiểm tra thời gian điểm danh có nằm trong khung giờ buổi học hay không
            // Cho phép điểm danh vào sớm tối đa 30 phút, và kết thúc trễ tối đa 45 phút
            $now = Carbon::now();
            $ngayHocStr = $practiceSession->ngay_hoc->toDateString();
            $sessionStart = Carbon::parse($ngayHocStr . ' ' . $practiceSession->gio_bat_dau)->subMinutes(30);
            $sessionEnd = Carbon::parse($ngayHocStr . ' ' . $practiceSession->gio_ket_thuc)->addMinutes(45);

            if ($now->lt($sessionStart)) {
                $earliest = $sessionStart->format('H:i d/m/Y');
                return response()->json([
                    'success' => false,
                    'message' => "Chưa đến giờ điểm danh cho buổi thực hành '{$practiceSession->ten_buoi}'. Giờ mở sớm nhất: {$earliest}."
                ], 400);
            }

            if ($now->gt($sessionEnd)) {
                $latest = $sessionEnd->format('H:i d/m/Y');
                return response()->json([
                    'success' => false,
                    'message' => "Buổi thực hành '{$practiceSession->ten_buoi}' đã kết thúc điểm danh lúc {$latest}!"
                ], 400);
            }
        }

        // Kiểm tra xưởng thực hành mặc định nếu chưa truyền
        if (! $workshopId) {
            $defaultWorkshop = Workshop::first();
            $workshopId = $defaultWorkshop ? $defaultWorkshop->id : null;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Tìm bản ghi điểm danh tương ứng:
        // Nếu có practice_session_id thì tìm theo sinh viên + practice_session_id
        // Nếu không có thì tìm theo sinh viên + ngày hôm nay
        $attendanceQuery = Attendance::where('student_id', $student->id);
        if ($practiceSession) {
            $attendanceQuery->where('practice_session_id', $practiceSession->id);
        } else {
            $attendanceQuery->whereDate('check_in', $today);
        }

        $attendance = $attendanceQuery->first();

        // Xác định trạng thái đúng giờ hay muộn (nếu có buổi thực hành)
        $status = 'dung_gio';
        if ($practiceSession) {
            $startTime = Carbon::parse($practiceSession->ngay_hoc->toDateString() . ' ' . $practiceSession->gio_bat_dau);
            // Nếu check-in sau giờ bắt đầu hơn 15 phút thì tính là muộn
            if ($now->gt($startTime->copy()->addMinutes(15))) {
                $status = 'muon';
            }
        }

        // Tình huống 1: Chưa có bản ghi => Ghi nhận Giờ Vào (Check-In)
        if (! $attendance) {
            $newAttendance = Attendance::create([
                'student_id' => $student->id,
                'workshop_id' => $workshopId,
                'practice_session_id' => $practiceSession ? $practiceSession->id : null,
                'check_in' => $now,
                'check_out' => null,
                'status' => $status,
            ]);

            $newAttendance->load(['student', 'workshop', 'practiceSession']);

            // Tự động gửi email thông báo điểm danh vào
            $this->sendAttendanceNotificationEmail($student, $newAttendance, 'VÀO', $now->format('H:i:s d/m/Y'), $practiceSession);

            $statusText = $status === 'muon' ? ' (ĐI MUỘN)' : '';
            return response()->json([
                'success' => true,
                'type' => 'check_in',
                'message' => 'Điểm danh vào xưởng thành công' . $statusText,
                'data' => [
                    'student' => $student,
                    'attendance' => $newAttendance,
                    'action' => 'VÀO',
                    'time' => $now->format('H:i:s d/m/Y'),
                    'status' => $status,
                    'session_name' => $practiceSession ? $practiceSession->ten_buoi : 'Buổi thực hành chung',
                ]
            ]);
        }

        // Tình huống 2: Đã check_in nhưng chưa check_out => Ghi nhận Giờ Ra (Check-Out)
        if ($attendance->check_in && ! $attendance->check_out) {
            // Chống quét trùng lập tức trong vòng 10 giây
            $checkInCarbon = Carbon::parse($attendance->check_in);
            if (abs($now->diffInSeconds($checkInCarbon, false)) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn vừa điểm danh vào cách đây vài giây. Vui lòng chờ trước khi điểm danh ra!'
                ], 400);
            }

            $attendance->update([
                'check_out' => $now,
                'status' => 'hoan_thanh'
            ]);

            $attendance->load(['student', 'workshop', 'practiceSession']);

            // Tự động gửi email thông báo điểm danh ra
            $this->sendAttendanceNotificationEmail($student, $attendance, 'RA', $now->format('H:i:s d/m/Y'), $practiceSession);

            return response()->json([
                'success' => true,
                'type' => 'check_out',
                'message' => 'Điểm danh ra xưởng thành công',
                'data' => [
                    'student' => $student,
                    'attendance' => $attendance,
                    'action' => 'RA',
                    'time' => $now->format('H:i:s d/m/Y'),
                    'status' => 'hoan_thanh',
                    'session_name' => $practiceSession ? $practiceSession->ten_buoi : 'Buổi thực hành chung',
                ]
            ]);
        }

        // Tình huống 3: Đã có cả check_in và check_out => Hoàn thành
        return response()->json([
            'success' => false,
            'type' => 'completed',
            'message' => 'Sinh viên đã hoàn thành điểm danh cả giờ vào và giờ ra cho buổi này!',
            'data' => [
                'student' => $student,
                'attendance' => $attendance,
                'check_in' => $attendance->check_in ? $attendance->check_in->format('H:i:s d/m/Y') : '--',
                'check_out' => $attendance->check_out ? $attendance->check_out->format('H:i:s d/m/Y') : '--',
            ]
        ], 200);
    }

    /**
     * Endpoint Check-In thủ công hoặc riêng biệt
     */
    public function checkIn(Request $request)
    {
        return $this->scanQr($request);
    }

    /**
     * Endpoint Check-Out riêng biệt
     */
    public function checkOut(Request $request)
    {
        return $this->scanQr($request);
    }

    /**
     * Gửi email thông báo điểm danh tự động nếu có cấu hình bật
     */
    private function sendAttendanceNotificationEmail(Student $student, Attendance $attendance, string $action, string $timeStr, ?PracticeSession $practiceSession): void
    {
        if (empty($student->email)) {
            return;
        }

        $autoEmail = \Illuminate\Support\Facades\Cache::get('setting_auto_email_on_scan', true);
        if (! $autoEmail) {
            return;
        }

        try {
            $workshopName = $attendance->workshop?->ten_xuong ?? 'Xưởng thực hành';
            $mailData = [
                'student_name' => $student->ho_ten,
                'ma_sinh_vien' => $student->ma_sinh_vien,
                'lop' => $student->lop,
                'action' => $action,
                'time' => $timeStr,
                'status' => $attendance->status,
                'session_name' => $practiceSession ? $practiceSession->ten_buoi : 'Buổi thực hành chung',
                'workshop_name' => $workshopName,
            ];

            \Illuminate\Support\Facades\Mail::to($student->email)
                ->send(new \App\Mail\AttendanceNotificationMail($mailData));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Không thể gửi email thông báo điểm danh tới {$student->email}: " . $e->getMessage());
        }
    }
}
