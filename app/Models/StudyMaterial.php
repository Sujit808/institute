<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_class_id',
        'subject_id',
        'title',
        'description',
        'file_path',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
