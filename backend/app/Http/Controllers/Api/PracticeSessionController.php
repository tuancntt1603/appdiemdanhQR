<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PracticeSessionController extends Controller
{
    /**
     * Danh sách buổi thực hành (hỗ trợ lọc theo ngày, lớp, xưởng, trạng thái)
     */
    public function index(Request $request)
    {
        $query = PracticeSession::with(['workshop', 'lecturer'])
            ->withCount('attendances');

        if ($request->filled('ngay_hoc')) {
            $query->whereDate('ngay_hoc', $request->ngay_hoc);
        }

        if ($request->filled('lop')) {
            $query->where('lop', $request->lop);
        }

        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $sessions = $query->orderBy('ngay_hoc', 'desc')
            ->orderBy('gio_bat_dau', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách buổi thực hành thành công',
            'data' => $sessions
        ]);
    }

    /**
     * Lấy các buổi thực hành khả dụng hôm nay hoặc đang diễn ra để quét QR
     */
    public function activeSessions(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $query = PracticeSession::with(['workshop', 'lecturer'])
            ->withCount('attendances')
            ->whereDate('ngay_hoc', $today);

        if ($request->filled('lop')) {
            $query->where('lop', $request->lop);
        }

        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        $sessions = $query->orderBy('gio_bat_dau', 'asc')->get();

        // Tự động gắn cờ is_in_time cho từng buổi (bao gồm dung sai 30 phút trước và sau)
        $sessions->transform(function ($session) use ($now, $today) {
            $start = Carbon::parse($today . ' ' . $session->gio_bat_dau)->subMinutes(30);
            $end = Carbon::parse($today . ' ' . $session->gio_ket_thuc)->addMinutes(30);
            $session->is_in_time = $now->between($start, $end);
            return $session;
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách buổi thực hành hôm nay thành công',
            'data' => $sessions
        ]);
    }

    /**
     * Thêm buổi thực hành mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_buoi' => 'required|string|max:150',
            'mon_hoc' => 'nullable|string|max:100',
            'lop' => 'required|string|max:50',
            'workshop_id' => 'required|exists:workshops,id',
            'user_id' => 'nullable|exists:users,id',
            'ngay_hoc' => 'required|date',
            'gio_bat_dau' => 'required|date_format:H:i',
            'gio_ket_thuc' => 'required|date_format:H:i|after:gio_bat_dau',
            'trang_thai' => 'nullable|in:sap_dien_ra,dang_dien_ra,da_ket_thuc',
            'ghi_chu' => 'nullable|string|max:500',
        ], [
            'ten_buoi.required' => 'Tên buổi thực hành không được để trống',
            'lop.required' => 'Lớp thực hành không được để trống',
            'workshop_id.required' => 'Vui lòng chọn xưởng thực hành',
            'workshop_id.exists' => 'Xưởng thực hành không tồn tại',
            'ngay_hoc.required' => 'Vui lòng chọn ngày học',
            'gio_bat_dau.required' => 'Vui lòng nhập giờ bắt đầu',
            'gio_ket_thuc.required' => 'Vui lòng nhập giờ kết thúc',
            'gio_ket_thuc.after' => 'Giờ kết thúc phải sau giờ bắt đầu',
        ]);

        if (empty($validated['trang_thai'])) {
            $validated['trang_thai'] = 'dang_dien_ra';
        }

        $session = PracticeSession::create($validated);
        $session->load(['workshop', 'lecturer']);

        return response()->json([
            'success' => true,
            'message' => 'Tạo buổi thực hành thành công',
            'data' => $session
        ], 201);
    }

    /**
     * Chi tiết buổi thực hành kèm danh sách điểm danh
     */
    public function show($id)
    {
        $session = PracticeSession::with(['workshop', 'lecturer', 'attendances.student'])->find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy buổi thực hành'
            ], 404);
        }

        // Lấy tất cả sinh viên thuộc lớp của buổi này
        $classStudents = Student::where('lop', $session->lop)->get();
        $attendedStudentIds = $session->attendances->pluck('student_id')->toArray();

        $studentsStatus = $classStudents->map(function ($student) use ($session) {
            $attendance = $session->attendances->firstWhere('student_id', $student->id);
            return [
                'student' => $student,
                'attended' => $attendance !== null,
                'attendance' => $attendance,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết buổi thực hành thành công',
            'data' => [
                'session' => $session,
                'students_status' => $studentsStatus,
                'total_students' => $classStudents->count(),
                'attended_count' => count($attendedStudentIds),
                'absent_count' => max(0, $classStudents->count() - count($attendedStudentIds)),
            ]
        ]);
    }

    /**
     * Cập nhật buổi thực hành
     */
    public function update(Request $request, $id)
    {
        $session = PracticeSession::find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy buổi thực hành'
            ], 404);
        }

        $validated = $request->validate([
            'ten_buoi' => 'required|string|max:150',
            'mon_hoc' => 'nullable|string|max:100',
            'lop' => 'required|string|max:50',
            'workshop_id' => 'required|exists:workshops,id',
            'user_id' => 'nullable|exists:users,id',
            'ngay_hoc' => 'required|date',
            'gio_bat_dau' => 'required',
            'gio_ket_thuc' => 'required|after:gio_bat_dau',
            'trang_thai' => 'nullable|in:sap_dien_ra,dang_dien_ra,da_ket_thuc',
            'ghi_chu' => 'nullable|string|max:500',
        ]);

        $session->update($validated);
        $session->load(['workshop', 'lecturer']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật buổi thực hành thành công',
            'data' => $session
        ]);
    }

    /**
     * Xóa buổi thực hành
     */
    public function destroy($id)
    {
        $session = PracticeSession::find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy buổi thực hành'
            ], 404);
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa buổi thực hành thành công',
            'data' => null
        ]);
    }
}
