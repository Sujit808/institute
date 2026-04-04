<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSalary extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id', 'salary_month', 'gross_salary', 'deductions', 'net_salary', 'status',
        'pf', 'esi', 'tds', 'custom_components'
    ];
    protected $casts = [
        'custom_components' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(PayrollEmployee::class, 'employee_id');
    }

    public function payslip()
    {
        return $this->hasOne(PayrollPayslip::class, 'salary_id');
    }
}
