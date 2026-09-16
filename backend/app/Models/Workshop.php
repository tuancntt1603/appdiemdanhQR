<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'ten_xuong',
        'dia_diem'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
