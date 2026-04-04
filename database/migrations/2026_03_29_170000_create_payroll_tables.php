<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('designation')->nullable();
            $table->decimal('base_salary', 10, 2);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->timestamps();
        });
        Schema::create('payroll_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->date('salary_month');
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salary_id')->index();
            $table->string('file_path');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payroll_payslips');
        Schema::dropIfExists('payroll_salaries');
        Schema::dropIfExists('payroll_employees');
    }
};