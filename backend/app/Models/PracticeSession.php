<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'ten_buoi',
        'mon_hoc',
        'lop',
        'workshop_id',
        'user_id',
        'ngay_hoc',
        'gio_bat_dau',
        'gio_ket_thuc',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'ngay_hoc' => 'date',
    ];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
