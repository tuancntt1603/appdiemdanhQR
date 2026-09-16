<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    /**
     * Danh sách xưởng thực hành
     */
    public function index()
    {
        $workshops = Workshop::orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách xưởng thành công',
            'data' => $workshops
        ]);
    }

    /**
     * Thêm xưởng mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_xuong' => 'required|string|max:100',
            'dia_diem' => 'nullable|string|max:200',
        ]);

        $workshop = Workshop::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm xưởng thực hành thành công',
            'data' => $workshop
        ], 201);
    }

    /**
     * Chi tiết xưởng
     */
    public function show($id)
    {
        $workshop = Workshop::find($id);

        if (! $workshop) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy xưởng thực hành'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin xưởng thành công',
            'data' => $workshop
        ]);
    }

    /**
     * Cập nhật xưởng
     */
    public function update(Request $request, $id)
    {
        $workshop = Workshop::find($id);

        if (! $workshop) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy xưởng thực hành'
            ], 404);
        }

        $validated = $request->validate([
            'ten_xuong' => 'required|string|max:100',
            'dia_diem' => 'nullable|string|max:200',
        ]);

        $workshop->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật xưởng thành công',
            'data' => $workshop
        ]);
    }

    /**
     * Xóa xưởng
     */
    public function destroy($id)
    {
        $workshop = Workshop::find($id);

        if (! $workshop) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy xưởng thực hành'
            ], 404);
        }

        $workshop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa xưởng thực hành thành công',
            'data' => null
        ]);
    }
}
