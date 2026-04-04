<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attendance_for',
        'student_id',
        'staff_attendance_id',
        'academic_class_id',
        'section_id',
        'staff_id',
        'marked_by_staff_id',
        'attendance_date',
        'attendance_method',
        'biometric_device_id',
        'biometric_log_id',
        'capture_payload',
        'captured_at',
        'status',
        'sync_status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'captured_at' => 'datetime',
        'capture_payload' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function staffAttendance(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_attendance_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'marked_by_staff_id');
    }
}
