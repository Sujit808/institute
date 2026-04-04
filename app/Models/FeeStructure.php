<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_class_id',
        'fee_head',
        'fee_label',
        'amount',
        'due_month',
        'academic_year',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_month' => 'integer',
    ];

    public static array $feeHeads = [
        'tuition' => 'Tuition Fee',
        'transport' => 'Transport Fee',
        'lab' => 'Lab Fee',
        'sports' => 'Sports Fee',
        'exam' => 'Exam Fee',
        'hostel' => 'Hostel Fee',
        'library' => 'Library Fee',
        'misc' => 'Miscellaneous',
    ];

    public static array $months = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
