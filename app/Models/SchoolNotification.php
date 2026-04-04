<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
        'audience',
        'academic_class_id',
        'section_id',
        'source_type',
        'source_id',
        'publish_date',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'academic_class_id' => 'integer',
        'section_id' => 'integer',
        'source_id' => 'integer',
    ];
}
