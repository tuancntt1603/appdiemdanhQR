<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tạo bảng practice_sessions (Quản lý buổi thực hành và lịch điểm danh)
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('ten_buoi'); // Ví dụ: Buổi 1 - Thực hành Mạng & Định tuyến
            $table->string('mon_hoc')->nullable(); // Ví dụ: Mạng máy tính căn bản
            $table->string('lop'); // Ví dụ: D21CNTT01
            $table->foreignId('workshop_id')->constrained('workshops')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Giảng viên phụ trách
            $table->date('ngay_hoc'); // Ngày diễn ra buổi học
            $table->time('gio_bat_dau'); // Giờ bắt đầu (VD: 07:30:00)
            $table->time('gio_ket_thuc'); // Giờ kết thúc (VD: 11:30:00)
            $table->string('trang_thai')->default('dang_dien_ra'); // sap_dien_ra, dang_dien_ra, da_ket_thuc
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index(['lop', 'ngay_hoc']);
            $table->index('workshop_id');
        });

        // 2. Bổ sung practice_session_id vào bảng attendances nếu chưa có
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'practice_session_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('practice_session_id')
                    ->nullable()
                    ->after('workshop_id')
                    ->constrained('practice_sessions')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'practice_session_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropForeign(['practice_session_id']);
                $table->dropColumn('practice_session_id');
            });
        }

        Schema::dropIfExists('practice_sessions');
    }
};
