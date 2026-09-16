<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Danh sách sinh viên (có tìm kiếm và phân trang/lấy tất cả)
     */
    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_sinh_vien', 'like', "%{$keyword}%")
                  ->orWhere('ho_ten', 'like', "%{$keyword}%")
                  ->orWhere('lop', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('lop')) {
            $query->where('lop', $request->lop);
        }

        $students = $query->orderBy('ma_sinh_vien', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách sinh viên thành công',
            'data' => $students
        ]);
    }

    /**
     * Thêm mới sinh viên
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma_sinh_vien' => 'required|string|max:50|unique:students,ma_sinh_vien',
            'ho_ten' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'lop' => 'required|string|max:50',
            'khoa' => 'nullable|string|max:50',
        ], [
            'ma_sinh_vien.required' => 'Mã sinh viên không được để trống',
            'ma_sinh_vien.unique' => 'Mã sinh viên này đã tồn tại trong hệ thống',
            'ho_ten.required' => 'Họ tên sinh viên không được để trống',
            'lop.required' => 'Lớp không được để trống',
        ]);

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm sinh viên thành công',
            'data' => $student
        ], 201);
    }

    /**
     * Chi tiết sinh viên
     */
    public function show($id)
    {
        $student = Student::with(['attendances' => function ($q) {
            $q->orderBy('check_in', 'desc');
        }])->find($id);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin sinh viên thành công',
            'data' => $student
        ]);
    }

    /**
     * Cập nhật thông tin sinh viên
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên'
            ], 404);
        }

        $validated = $request->validate([
            'ma_sinh_vien' => "required|string|max:50|unique:students,ma_sinh_vien,{$id}",
            'ho_ten' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'lop' => 'required|string|max:50',
            'khoa' => 'nullable|string|max:50',
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sinh viên thành công',
            'data' => $student
        ]);
    }

    /**
     * Xóa sinh viên
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sinh viên'
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sinh viên thành công',
            'data' => null
        ]);
    }
}
