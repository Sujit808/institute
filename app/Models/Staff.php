<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'designation',
        'role_type',
        'joining_date',
        'qualification',
        'permissions',
        'experience_years',
        'leave_balance_days',
        'salary',
        'address',
        'aadhar_number',
        'pan_number',
        'photo',
        'aadhar_file',
        'pancard_file',
        'qualification_files',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'qualification_files' => 'array',
        'permissions' => 'array',
        'leave_balance_days' => 'integer',
        'salary' => 'decimal:2',
    ];

    protected $appends = ['full_name'];

    public function linkedUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function sectionsAsClassTeacher(): HasMany
    {
        return $this->hasMany(Section::class, 'class_teacher_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
