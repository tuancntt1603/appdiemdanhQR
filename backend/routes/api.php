<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\WorkshopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Hệ thống Điểm danh QR Code Xưởng Thực Hành
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('/login', [AuthController::class, 'login']);

// QR Code APIs (Dành cho quét webcam và sinh mã cá nhân)
Route::post('/qr/generate', [QrCodeController::class, 'generate']);
Route::post('/qr/verify', [QrCodeController::class, 'verify']);
Route::get('/students/{id}/qr', [QrCodeController::class, 'getStudentQr']);

// Điểm danh (Tự động vào/ra theo quy trình)
Route::post('/attendance/scan', [AttendanceController::class, 'scanQr']);
Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

// Danh sách điểm danh & Lịch sử
Route::get('/attendance', [AttendanceController::class, 'index']);
Route::get('/attendance/student/{id}', [AttendanceController::class, 'getStudentAttendance']);

// Sinh viên (Public hoặc Auth)
Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::delete('/students/{id}', [StudentController::class, 'destroy']);

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Báo cáo & Xuất Excel
Route::get('/reports/weekly', [ReportController::class, 'weekly']);
Route::get('/reports/semester', [ReportController::class, 'semester']);
Route::get('/reports/export', [ReportController::class, 'export']);

// Xưởng thực hành
Route::get('/workshops', [WorkshopController::class, 'index']);
Route::post('/workshops', [WorkshopController::class, 'store']);
Route::get('/workshops/{id}', [WorkshopController::class, 'show']);
Route::put('/workshops/{id}', [WorkshopController::class, 'update']);
Route::delete('/workshops/{id}', [WorkshopController::class, 'destroy']);

// Routes cần xác thực bằng Bearer token (nếu cần bảo mật nâng cao)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
