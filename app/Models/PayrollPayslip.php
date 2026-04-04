<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPayslip extends Model
{
    use HasFactory;
    protected $fillable = [
        'salary_id', 'file_path'
    ];

    public function salary()
    {
        return $this->belongsTo(PayrollSalary::class, 'salary_id');
    }
}
