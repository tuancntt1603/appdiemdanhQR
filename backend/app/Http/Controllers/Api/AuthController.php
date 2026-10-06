<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Đăng nhập hệ thống (Sinh viên, Giảng viên, Admin)
     * Cho phép đăng nhập bằng username hoặc email
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required_without:email|string',
            'email' => 'required_without:username|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('username') ?? $request->input('email'));

        // 1. Tìm user theo username, sau đó email, hoặc name
        $user = User::with('student')
            ->where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        // 2. Kiểm tra mật khẩu
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản hoặc mật khẩu không chính xác'
            ], 401);
        }

        // 3. Kiểm tra trạng thái hoạt động
        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'
            ], 403);
        }

        // 4. Tạo Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        $student = $user->student;
        $userResponse = [
            'id' => $user->id,
            'username' => $user->username ?? $user->email,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'student_id' => $user->student_id,
            'is_active' => (bool) ($user->is_active ?? true),
        ];

        if ($student) {
            $userResponse['ma_sinh_vien'] = $student->ma_sinh_vien;
            $userResponse['ho_ten'] = $student->ho_ten;
            $userResponse['lop'] = $student->lop;
            $userResponse['student'] = $student;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => $userResponse,
            // Duy trì 'data' để tương thích ngược 100% với frontend cũ
            'data' => [
                'token' => $token,
                'user' => $userResponse
            ]
        ]);
    }

    /**
     * Lấy thông tin user hiện tại (GET /api/me)
     * Trả về user và đầy đủ thông tin sinh viên nếu là tài khoản student
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $student = $user->student;
        $userData = [
            'id' => $user->id,
            'username' => $user->username ?? $user->email,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'student_id' => $user->student_id,
            'is_active' => (bool) ($user->is_active ?? true),
        ];

        if ($student) {
            $userData['student_id'] = $student->id;
            $userData['ma_sinh_vien'] = $student->ma_sinh_vien;
            $userData['ho_ten'] = $student->ho_ten;
            $userData['lop'] = $student->lop;
            $userData['email'] = $student->email;
            $userData['student'] = $student;
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin người dùng thành công',
            'user' => $userData,
            'data' => $userData
        ]);
    }

    /**
     * Đăng xuất hệ thống (POST /api/logout)
     * Xóa token hiện tại
     */
    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
            'data' => null
        ]);
    }
}
