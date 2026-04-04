<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionLead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Calculate and return a lead score (0-100) based on available data.
     * Rule-based, future AI/ML ready.
     */
    public function calculateScore(): int
    {
        $score = 0;

        // Source-based boost
        $source = strtolower((string) $this->source);
        if (in_array($source, ['website', 'meta_ads', 'google_ads', 'campaign'], true)) {
            $score += 30;
        } elseif ($source === 'reference') {
            $score += 20;
        } elseif ($source === 'walk_in') {
            $score += 10;
        }

        // Stage-based boost
        $stage = strtolower((string) $this->stage);
        if ($stage === 'contacted') {
            $score += 10;
        } elseif ($stage === 'counselling_scheduled' || $stage === 'counselling_done') {
            $score += 20;
        } elseif ($stage === 'follow_up') {
            $score += 15;
        } elseif ($stage === 'converted') {
            $score += 40;
        }

        // Contact info completeness
        if (!empty($this->phone)) {
            $score += 10;
        }
        if (!empty($this->email)) {
            $score += 5;
        }

        // Recent follow-up (last 3 days)
        if ($this->next_follow_up_at && $this->next_follow_up_at->isAfter(now()->subDays(3))) {
            $score += 10;
        }

        // Cap at 100
        return min(100, $score);
    }

    protected $fillable = [
        'student_name',
        'guardian_name',
        'phone',
        'email',
        'academic_class_id',
        'source',
        'stage',
        'score',
        'assigned_to_staff_id',
        'converted_student_id',
        'last_contacted_at',
        'next_follow_up_at',
        'converted_at',
        'conversion_reason',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'academic_class_id' => 'integer',
        'assigned_to_staff_id' => 'integer',
        'converted_student_id' => 'integer',
        'score' => 'integer',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function assignedToStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to_staff_id');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
