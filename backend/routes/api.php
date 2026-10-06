<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PracticeSessionController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\WorkshopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Hệ thống Điểm danh QR Code
|--------------------------------------------------------------------------
*/

// 1. Xác thực công khai
Route::post('/login', [AuthController::class, 'login']);

// 2. Xác thực QR cho thiết bị quét (nếu cần verify ngoài)
Route::post('/qr/verify', [QrCodeController::class, 'verify']);

// 3. Toàn bộ các API yêu cầu đăng nhập qua Sanctum Token
Route::middleware('auth:sanctum')->group(function () {
    // Thông tin người dùng hiện tại & Đăng xuất
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sinh mã QR cá nhân chung (Nếu là sinh viên sẽ tự động chỉ định chính mình)
    Route::post('/qr/generate', [QrCodeController::class, 'generate']);

    /*
    |--------------------------------------------------------------------------
    | PHÂN QUYỀN: DÀNH RIÊNG CHO SINH VIÊN (role: student)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:student')->group(function () {
        Route::get('/my-attendance', [AttendanceController::class, 'myAttendance']);
        Route::get('/my-qr', [QrCodeController::class, 'myQr']);
        Route::post('/my-qr', [QrCodeController::class, 'myQr']);
    });

    /*
    |--------------------------------------------------------------------------
    | PHÂN QUYỀN: DÀNH CHO GIẢNG VIÊN VÀ QUẢN TRỊ VIÊN (role: lecturer, admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:lecturer,admin')->group(function () {
        // Bảng điều khiển (Dashboard)
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Quét QR & Điểm danh
        Route::post('/attendance/scan', [AttendanceController::class, 'scanQr']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::get('/attendance/student/{id}', [AttendanceController::class, 'getStudentAttendance']);

        // Quản lý sinh viên & QR sinh viên
        Route::get('/students', [StudentController::class, 'index']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::get('/students/{id}', [StudentController::class, 'show']);
        Route::put('/students/{id}', [StudentController::class, 'update']);
        Route::delete('/students/{id}', [StudentController::class, 'destroy']);
        Route::get('/students/{id}/qr', [QrCodeController::class, 'getStudentQr']);

        // Buổi thực hành & Lịch điểm danh
        Route::get('/practice-sessions/active', [PracticeSessionController::class, 'activeSessions']);
        Route::get('/practice-sessions', [PracticeSessionController::class, 'index']);
        Route::post('/practice-sessions', [PracticeSessionController::class, 'store']);
        Route::get('/practice-sessions/{id}', [PracticeSessionController::class, 'show']);
        Route::put('/practice-sessions/{id}', [PracticeSessionController::class, 'update']);
        Route::delete('/practice-sessions/{id}', [PracticeSessionController::class, 'destroy']);

        // Quản lý xưởng thực hành
        Route::get('/workshops', [WorkshopController::class, 'index']);
        Route::post('/workshops', [WorkshopController::class, 'store']);
        Route::get('/workshops/{id}', [WorkshopController::class, 'show']);
        Route::put('/workshops/{id}', [WorkshopController::class, 'update']);
        Route::delete('/workshops/{id}', [WorkshopController::class, 'destroy']);

        // Thống kê & Báo cáo & Xuất Excel
        Route::get('/reports/weekly', [ReportController::class, 'weekly']);
        Route::get('/reports/semester', [ReportController::class, 'semester']);
        Route::get('/reports/export', [ReportController::class, 'export']);
    });
});
