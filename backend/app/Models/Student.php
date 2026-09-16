<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'ma_sinh_vien',
        'ho_ten',
        'email',
        'lop',
        'khoa'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function qrTokens()
    {
        return $this->hasMany(QrToken::class);
    }
}
