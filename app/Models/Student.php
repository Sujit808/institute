<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            if (! $student->academic_class_id) {
                return;
            }

            $maxRollNo = static::query()
                ->where('academic_class_id', $student->academic_class_id)
                ->pluck('roll_no')
                ->map(fn ($roll) => (int) preg_replace('/\D+/', '', (string) $roll))
                ->filter(fn (int $roll) => $roll > 0)
                ->max();

            $nextRoll = (! $maxRollNo || $maxRollNo < 1000) ? 1001 : $maxRollNo + 1;

            if (empty($student->roll_no)) {
                $student->roll_no = (string) $nextRoll;
            }

            if (empty($student->admission_no)) {
                $class = AcademicClass::query()->find($student->academic_class_id);
                $classCode = strtoupper((string) ($class?->code ?? $class?->name ?? 'GEN'));
                $classCode = preg_replace('/[^A-Z0-9]/', '', $classCode) ?: 'GEN';
                $student->admission_no = 'ADM-'.$classCode.$student->roll_no;
            }
        });
    }

    protected $fillable = [
        'academic_class_id',
        'section_id',
        'admission_no',
        'roll_no',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'guardian_name',
        'guardian_phone',
        'college_name',
        'current_college_name',
        'admission_date',
        'blood_group',
        'address',
        'aadhar_number',
        'photo',
        'aadhar_file',
        'documents',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'documents' => 'array',
    ];

    protected $appends = ['full_name'];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
