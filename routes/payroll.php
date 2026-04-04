<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payroll\StaffSalaryController;

Route::prefix('payroll')
    ->middleware(['web', 'auth', 'can:manage-payroll'])
    ->group(function () {
        // Index route for navigation
        Route::get('/', [StaffSalaryController::class, 'index'])->name('payroll.index');
        Route::get('/staff-salary', [StaffSalaryController::class, 'index'])->name('payroll.salary.index');
        Route::get('/staff-salary/create', [StaffSalaryController::class, 'create'])->name('payroll.salary.create');
        Route::post('/staff-salary', [StaffSalaryController::class, 'store'])->name('payroll.salary.store');
        Route::get('/staff-salary/{id}/edit', [StaffSalaryController::class, 'edit'])->name('payroll.salary.edit');
        Route::put('/staff-salary/{id}', [StaffSalaryController::class, 'update'])->name('payroll.salary.update');
        Route::get('/staff-salary/{id}', [StaffSalaryController::class, 'show'])->name('payroll.salary.show');
        Route::post('/staff-salary/{id}/payslip', [StaffSalaryController::class, 'generatePayslip'])->name('payroll.salary.payslip.generate');
        Route::get('/staff-salary/{id}/payslip/download', [StaffSalaryController::class, 'downloadPayslip'])->name('payroll.salary.payslip.download');
        Route::get('/summary', [StaffSalaryController::class, 'summary'])->name('payroll.salary.summary');
        Route::get('/salary-export', [StaffSalaryController::class, 'export'])->name('payroll.salary.export');
        Route::post('/staff-salary/bulk', [StaffSalaryController::class, 'bulkUpdate'])->name('payroll.salary.bulk');
        Route::post('/staff-salary/{id}/email-payslip', [StaffSalaryController::class, 'emailPayslip'])->name('payroll.salary.email-payslip');
        // Add more routes for show, edit, update as needed
    });
