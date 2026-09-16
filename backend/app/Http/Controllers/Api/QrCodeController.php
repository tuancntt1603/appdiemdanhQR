<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrToken;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    /**
     * Tạo hoặc lấy QR code cá nhân cho sinh viên
     * Token có hiệu lực đúng 90 giây
     */
    public function generate(Request $request)
    {
        $request->validate([
            'ma_sinh_vien' => 'required_without:student_id|string',
            'student_id' => 'required_without:ma_sinh_vien|integer',
        ]);

        $query = Student::query();
        if ($request->filled('student_id')) {
            $student = $query->find($request->student_id);
        } else {
            $student = $query->where('ma_sinh_vien', $request->ma_sinh_vien)->first();
        }

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên trong hệ thống'
            ], 404);
        }

        // Tạo token ngẫu nhiên an toàn
        $tokenString = Str::random(40);
        $expiresAt = Carbon::now()->addSeconds(90);

        // Lưu bản ghi token mới
        $qrToken = QrToken::create([
            'student_id' => $student->id,
            'token' => $tokenString,
            'expires_at' => $expiresAt,
        ]);

        // Nội dung QR theo dạng JSON chuẩn theo đề bài
        $qrPayload = [
            'student_id' => $student->id,
            'ma_sinh_vien' => $student->ma_sinh_vien,
            'token' => $tokenString,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã QR cá nhân thành công',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'ma_sinh_vien' => $student->ma_sinh_vien,
                    'ho_ten' => $student->ho_ten,
                    'lop' => $student->lop,
                    'khoa' => $student->khoa,
                ],
                'token' => $tokenString,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_seconds' => 90,
                'qr_data' => json_encode($qrPayload, JSON_UNESCAPED_UNICODE),
                'payload' => $qrPayload,
            ]
        ]);
    }

    /**
     * Lấy QR hiện tại của sinh viên theo ID
     */
    public function getStudentQr($id)
    {
        $student = Student::find($id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên'
            ], 404);
        }

        // Tạo mới QR 90 giây
        $tokenString = Str::random(40);
        $expiresAt = Carbon::now()->addSeconds(90);

        QrToken::create([
            'student_id' => $student->id,
            'token' => $tokenString,
            'expires_at' => $expiresAt,
        ]);

        $qrPayload = [
            'student_id' => $student->id,
            'ma_sinh_vien' => $student->ma_sinh_vien,
            'token' => $tokenString,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã QR thành công',
            'data' => [
                'student' => $student,
                'token' => $tokenString,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_seconds' => 90,
                'qr_data' => json_encode($qrPayload, JSON_UNESCAPED_UNICODE),
                'payload' => $qrPayload,
            ]
        ]);
    }

    /**
     * Xác thực QR payload nhận được từ webcam
     */
    public function verify(Request $request)
    {
        $rawQr = $request->input('qr_data');

        if (! $rawQr) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu QR trống'
            ], 400);
        }

        // 1. Kiểm tra QR có đúng định dạng JSON không?
        $decoded = is_array($rawQr) ? $rawQr : json_decode($rawQr, true);
        if (! $decoded || ! isset($decoded['student_id'], $decoded['token'], $decoded['ma_sinh_vien'])) {
            return response()->json([
                'success' => false,
                'message' => 'Mã QR không đúng định dạng của hệ thống điểm danh'
            ], 400);
        }

        // 2. Sinh viên có tồn tại không?
        $student = Student::find($decoded['student_id']);
        if (! $student || $student->ma_sinh_vien !== $decoded['ma_sinh_vien']) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin sinh viên không khớp hoặc không tồn tại'
            ], 404);
        }

        // 3. Token có tồn tại không & có thuộc đúng sinh viên không?
        $qrToken = QrToken::where('student_id', $student->id)
            ->where('token', $decoded['token'])
            ->first();

        if (! $qrToken) {
            return response()->json([
                'success' => false,
                'message' => 'Mã token QR không hợp lệ'
            ], 400);
        }

        // 4. Token đã hết hạn chưa?
        if (Carbon::now()->greaterThan($qrToken->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã QR đã hết hạn (chỉ có hiệu lực trong 90 giây), vui lòng lấy mã mới'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mã QR hợp lệ',
            'data' => [
                'student' => $student,
                'qr_token_id' => $qrToken->id,
            ]
        ]);
    }
}
