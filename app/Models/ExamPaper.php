<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPaper extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'set_code',
        'title',
        'instructions',
        'file_path',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
