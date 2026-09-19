<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Đăng nhập cán bộ / admin
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        $input = trim($request->email);

        // Tìm kiếm theo email hoặc theo name/mã sinh viên trong bảng users
        $user = User::where('email', $input)->first();

        // Nếu không tìm thấy theo email, thử tìm kiếm xem có phải sinh viên đăng nhập bằng mã SV
        if (! $user) {
            $user = User::where('name', $input)->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản hoặc mật khẩu không chính xác'
            ], 401);
        }

        // Tạo Personal Access Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Nếu là sinh viên, lấy kèm thông tin hồ sơ sinh viên
        $studentInfo = null;
        if ($user->role === 'sinh_vien') {
            $studentInfo = \App\Models\Student::where('email', $user->email)
                ->orWhere('ma_sinh_vien', $user->name)
                ->first();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'student' => $studentInfo,
                ]
            ]
        ]);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin người dùng thành công',
            'data' => $request->user()
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
            'data' => null
        ]);
    }
}
