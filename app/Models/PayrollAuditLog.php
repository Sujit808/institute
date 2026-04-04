<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAuditLog extends Model
{
    protected $fillable = [
        'salary_id', 'user_id', 'action', 'old_values', 'new_values'
    ];
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
    public function salary() { return $this->belongsTo(PayrollSalary::class, 'salary_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
