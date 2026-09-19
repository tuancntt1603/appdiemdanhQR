<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'workshop_id',
        'practice_session_id',
        'check_in',
        'check_out',
        'status'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function practiceSession()
    {
        return $this->belongsTo(PracticeSession::class);
    }
}
