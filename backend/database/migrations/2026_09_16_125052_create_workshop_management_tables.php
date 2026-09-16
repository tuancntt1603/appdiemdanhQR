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
        // 1. Bảng students (Quản lý sinh viên)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('ma_sinh_vien')->unique();
            $table->string('ho_ten');
            $table->string('email')->nullable();
            $table->string('lop');
            $table->string('khoa')->nullable();
            $table->timestamps();
        });

        // 2. Bảng workshops (Quản lý xưởng thực hành)
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->string('ten_xuong');
            $table->string('dia_diem')->nullable();
            $table->timestamps();
        });

        // 3. Bảng qr_tokens (Token QR cá nhân hết hạn 90s)
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('token')->index();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // 4. Bảng attendances (Lịch sử điểm danh vào / ra)
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('workshop_id')->nullable()->constrained('workshops')->onDelete('set null');
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->string('status')->default('dung_gio'); // dung_gio, muon, hoan_thanh
            $table->timestamps();

            $table->index(['student_id', 'check_in']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('qr_tokens');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('students');
    }
};
