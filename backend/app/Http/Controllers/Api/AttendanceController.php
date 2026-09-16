<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\QrToken;
use App\Models\Student;
use App\Models\Workshop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Danh sách lịch sử điểm danh với bộ lọc:
     * theo ngày, theo tuần, theo tháng, theo sinh viên, theo lớp
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['student', 'workshop']);

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
        $attendances = Attendance::with('workshop')
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
     * Quy trình:
     * - Kiểm tra QR payload (JSON, student, token 90s)
     * - Nếu chưa có bản ghi trong ngày => Check-in (Vào thành công)
     * - Nếu đã có check-in nhưng chưa check-out => Check-out (Ra thành công)
     * - Nếu đã có cả check-in và check-out => Báo hoàn thành điểm danh
     */
    public function scanQr(Request $request)
    {
        $rawQr = $request->input('qr_data');
        $workshopId = $request->input('workshop_id');

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

        // 5. Kiểm tra xưởng thực hành mặc định nếu chưa truyền
        if (! $workshopId) {
            $defaultWorkshop = Workshop::first();
            $workshopId = $defaultWorkshop ? $defaultWorkshop->id : null;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Tìm bản ghi điểm danh hôm nay của sinh viên
        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('check_in', $today)
            ->first();

        // Tình huống 1: Chưa có bản ghi hôm nay => Ghi nhận Giờ Vào
        if (! $attendance) {
            $newAttendance = Attendance::create([
                'student_id' => $student->id,
                'workshop_id' => $workshopId,
                'check_in' => $now,
                'check_out' => null,
                'status' => 'dung_gio',
            ]);

            return response()->json([
                'success' => true,
                'type' => 'check_in',
                'message' => 'Điểm danh vào thành công',
                'data' => [
                    'student' => $student,
                    'attendance' => $newAttendance,
                    'action' => 'VÀO',
                    'time' => $now->format('H:i:s d/m/Y'),
                    'status' => 'dung_gio'
                ]
            ]);
        }

        // Tình huống 2: Đã check_in nhưng chưa check_out => Ghi nhận Giờ Ra
        if ($attendance->check_in && ! $attendance->check_out) {
            // Chống quét trùng lập tức trong vòng 10 giây
            if ($now->diffInSeconds($attendance->check_in) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn vừa điểm danh vào cách đây vài giây. Vui lòng chờ trước khi điểm danh ra!'
                ], 400);
            }

            $attendance->update([
                'check_out' => $now,
                'status' => 'hoan_thanh'
            ]);

            return response()->json([
                'success' => true,
                'type' => 'check_out',
                'message' => 'Điểm danh ra thành công',
                'data' => [
                    'student' => $student,
                    'attendance' => $attendance,
                    'action' => 'RA',
                    'time' => $now->format('H:i:s d/m/Y'),
                    'status' => 'hoan_thanh'
                ]
            ]);
        }

        // Tình huống 3: Đã có cả check_in và check_out => Hoàn thành
        return response()->json([
            'success' => false,
            'type' => 'completed',
            'message' => 'Hôm nay sinh viên đã hoàn thành điểm danh',
            'data' => [
                'student' => $student,
                'attendance' => $attendance,
                'check_in' => $attendance->check_in->format('H:i:s d/m/Y'),
                'check_out' => $attendance->check_out->format('H:i:s d/m/Y'),
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
}
