<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dữ liệu thống kê tổng quan cho Dashboard
     */
    public function index()
    {
        $today = Carbon::today();

        // 1. Tổng số sinh viên
        $totalStudents = Student::count();

        // 2. Danh sách điểm danh hôm nay
        $todayAttendances = Attendance::whereDate('check_in', $today)->get();

        // Số sinh viên đã điểm danh hôm nay (unique student_id)
        $attendedStudentIds = $todayAttendances->pluck('student_id')->unique();
        $attendedCount = $attendedStudentIds->count();

        // Số sinh viên chưa điểm danh
        $unattendedCount = max(0, $totalStudents - $attendedCount);

        // Số lượt vào (tất cả bản ghi có check_in hôm nay)
        $checkInCount = $todayAttendances->whereNotNull('check_in')->count();

        // Số lượt ra (bản ghi có check_out hôm nay)
        $checkOutCount = $todayAttendances->whereNotNull('check_out')->count();

        // 3. Biểu đồ 7 ngày gần nhất (Điểm danh theo ngày)
        $chartDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayAttendances = Attendance::whereDate('check_in', $date)->get();

            $chartDays[] = [
                'date' => $date->format('d/m'),
                'day_name' => $date->locale('vi')->isoFormat('dd'),
                'total' => $dayAttendances->pluck('student_id')->unique()->count(),
                'check_in' => $dayAttendances->whereNotNull('check_in')->count(),
                'check_out' => $dayAttendances->whereNotNull('check_out')->count(),
            ];
        }

        // 4. Biểu đồ 4 tuần gần nhất (Điểm danh theo tuần)
        $chartWeeks = [];
        for ($w = 3; $w >= 0; $w--) {
            $startWeek = Carbon::now()->subWeeks($w)->startOfWeek();
            $endWeek = Carbon::now()->subWeeks($w)->endOfWeek();

            $weekAttendances = Attendance::whereBetween('check_in', [$startWeek, $endWeek])->get();

            $chartWeeks[] = [
                'week' => 'Tuần ' . $startWeek->format('W'),
                'range' => $startWeek->format('d/m') . ' - ' . $endWeek->format('d/m'),
                'total' => $weekAttendances->count(),
            ];
        }

        // 5. Danh sách điểm danh mới nhất hôm nay (Top 10)
        $recentAttendances = Attendance::with('student', 'workshop')
            ->whereDate('check_in', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu thống kê dashboard thành công',
            'data' => [
                'total_students' => $totalStudents,
                'attended_today' => $attendedCount,
                'unattended_today' => $unattendedCount,
                'check_in_count' => $checkInCount,
                'check_out_count' => $checkOutCount,
                'chart_days' => $chartDays,
                'chart_weeks' => $chartWeeks,
                'recent_attendances' => $recentAttendances,
            ]
        ]);
    }
}
