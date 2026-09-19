<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportController extends Controller
{
    /**
     * Báo cáo chuyên cần theo tuần
     */
    public function weekly(Request $request)
    {
        $weekOffset = (int) $request->input('week', 0); // 0: tuần này
        $startDate = Carbon::now()->subWeeks($weekOffset)->startOfWeek();
        $endDate = Carbon::now()->subWeeks($weekOffset)->endOfWeek();

        $data = $this->calculateDiligenceReport(
            $startDate,
            $endDate,
            $request->input('lop'),
            $request->input('practice_session_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Lấy báo cáo chuyên cần theo tuần thành công',
            'data' => [
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'week_number' => $startDate->format('W'),
                'items' => $data
            ]
        ]);
    }

    /**
     * Báo cáo chuyên cần theo kỳ (hoặc theo tháng)
     */
    public function semester(Request $request)
    {
        // Mặc định tính trong 15 tuần học kỳ hoặc từ đầu kỳ (khoảng 3 tháng gần nhất)
        $months = (int) $request->input('months', 4);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $data = $this->calculateDiligenceReport(
            $startDate,
            $endDate,
            $request->input('lop'),
            $request->input('practice_session_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Lấy báo cáo chuyên cần theo kỳ thành công',
            'data' => [
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'items' => $data
            ]
        ]);
    }

    /**
     * Hàm tính toán chuyên cần:
     * Tổng số buổi (các ngày xưởng có tổ chức điểm danh)
     * Số buổi có mặt
     * Số buổi vắng = Tổng số buổi - Số buổi có mặt
     * Tỷ lệ = (có mặt / tổng số) * 100
     */
    private function calculateDiligenceReport($startDate, $endDate, $lop = null, $practiceSessionId = null)
    {
        // Lấy tất cả ngày xưởng có điểm danh trong khoảng thời gian
        $activeDaysQuery = Attendance::whereBetween('check_in', [$startDate, $endDate]);
        if ($practiceSessionId) {
            $activeDaysQuery->where('practice_session_id', $practiceSessionId);
        }
        $activeDays = $activeDaysQuery->selectRaw('DATE(check_in) as date')
            ->distinct()
            ->pluck('date');

        $totalSessions = max(1, $activeDays->count()); // Tối thiểu 1 để tránh chia cho 0

        $studentsQuery = Student::query();
        if ($lop) {
            $studentsQuery->where('lop', $lop);
        }
        $students = $studentsQuery->orderBy('ma_sinh_vien')->get();

        $report = [];
        foreach ($students as $student) {
            // Đếm số ngày sinh viên có điểm danh
            $presentDaysQuery = Attendance::where('student_id', $student->id)
                ->whereBetween('check_in', [$startDate, $endDate]);

            if ($practiceSessionId) {
                $presentDaysQuery->where('practice_session_id', $practiceSessionId);
            }

            $presentDays = $presentDaysQuery->selectRaw('DATE(check_in) as date')
                ->distinct()
                ->pluck('date')
                ->count();

            $absentDays = max(0, $totalSessions - $presentDays);
            $rate = round(($presentDays / $totalSessions) * 100, 1);

            $report[] = [
                'student_id' => $student->id,
                'ma_sinh_vien' => $student->ma_sinh_vien,
                'ho_ten' => $student->ho_ten,
                'lop' => $student->lop,
                'tong_buoi' => $totalSessions,
                'co_mat' => $presentDays,
                'vang' => $absentDays,
                'ty_le' => $rate,
            ];
        }

        return $report;
    }

    /**
     * Xuất báo cáo Excel bằng PhpSpreadsheet
     * Định dạng file: bao_cao_diem_danh_YYYY_MM_DD.xlsx
     */
    public function export(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
        $lop = $request->input('lop');
        $practiceSessionId = $request->input('practice_session_id');

        // Lấy danh sách điểm danh chi tiết
        $query = Attendance::with(['student', 'workshop', 'practiceSession'])
            ->whereBetween('check_in', [$startDate, $endDate]);

        if ($lop) {
            $query->whereHas('student', function ($q) use ($lop) {
                $q->where('lop', $lop);
            });
        }

        if ($practiceSessionId) {
            $query->where('practice_session_id', $practiceSessionId);
        }

        $attendances = $query->orderBy('check_in', 'asc')->get();

        // Tính tỷ lệ chuyên cần cho từng sinh viên
        $diligenceMap = [];
        $diligenceList = $this->calculateDiligenceReport($startDate, $endDate, $lop, $practiceSessionId);
        foreach ($diligenceList as $item) {
            $diligenceMap[$item['student_id']] = $item['ty_le'];
        }

        // Tạo Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Điểm Danh Xưởng');

        // Thiết lập Tiêu đề báo cáo
        $sheet->setCellValue('A1', 'BÁO CÁO ĐIỂM DANH SINH VIÊN TẠI XƯỞNG THỰC HÀNH');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E3A8A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Thời gian: ' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header bảng
        $headers = [
            'A4' => 'STT',
            'B4' => 'Mã sinh viên',
            'C4' => 'Họ và tên',
            'D4' => 'Lớp',
            'E4' => 'Xưởng thực hành',
            'F4' => 'Buổi thực hành',
            'G4' => 'Ngày',
            'H4' => 'Giờ vào',
            'I4' => 'Giờ ra',
            'J4' => 'Trạng thái',
            'K4' => 'Tỷ lệ chuyên cần'
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col, $title);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB']
                ]
            ]
        ];
        $sheet->getStyle('A4:K4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Đổ dữ liệu
        $row = 5;
        $stt = 1;
        foreach ($attendances as $att) {
            $checkIn = $att->check_in ? Carbon::parse($att->check_in) : null;
            $checkOut = $att->check_out ? Carbon::parse($att->check_out) : null;
            $statusText = $att->status === 'hoan_thanh' ? 'Hoàn thành' : ($att->status === 'muon' ? 'Đi muộn' : 'Đã vào xưởng');
            $rate = isset($diligenceMap[$att->student_id]) ? $diligenceMap[$att->student_id] . '%' : 'N/A';
            $workshopName = $att->workshop ? $att->workshop->ten_xuong : 'Xưởng chung';
            $sessionName = $att->practiceSession ? $att->practiceSession->ten_buoi : 'Chung';

            $sheet->setCellValue("A{$row}", $stt++);
            $sheet->setCellValue("B{$row}", $att->student->ma_sinh_vien ?? '');
            $sheet->setCellValue("C{$row}", $att->student->ho_ten ?? '');
            $sheet->setCellValue("D{$row}", $att->student->lop ?? '');
            $sheet->setCellValue("E{$row}", $workshopName);
            $sheet->setCellValue("F{$row}", $sessionName);
            $sheet->setCellValue("G{$row}", $checkIn ? $checkIn->format('d/m/Y') : '');
            $sheet->setCellValue("H{$row}", $checkIn ? $checkIn->format('H:i:s') : '');
            $sheet->setCellValue("I{$row}", $checkOut ? $checkOut->format('H:i:s') : '--:--:--');
            $sheet->setCellValue("J{$row}", $statusText);
            $sheet->setCellValue("K{$row}", $rate);

            // Căn lề
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Kẻ viền bảng
            $sheet->getStyle("A{$row}:K{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

            $row++;
        }

        // Tự căn chỉnh kích thước cột
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Xuất file ra stream download
        $fileName = 'bao_cao_diem_danh_' . Carbon::now()->format('Y_m_d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
