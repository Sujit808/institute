<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_device_id',
        'enrollment_for',
        'student_id',
        'staff_id',
        'punch_id',
        'finger_index',
        'status',
        'enrolled_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Human-readable name of the enrolled person. */
    public function getPersonNameAttribute(): string
    {
        if ($this->enrollment_for === 'staff') {
            return (string) ($this->staff?->full_name ?? '—');
        }

        return (string) ($this->student?->full_name ?? '—');
    }
}
