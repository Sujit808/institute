<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_class_id',
        'section_id',
        'subject_id',
        'staff_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room_no',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['time_slot'];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getTimeSlotAttribute(): string
    {
        return $this->start_time && $this->end_time ? $this->start_time.' - '.$this->end_time : '';
    }
}
