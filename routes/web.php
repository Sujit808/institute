<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillingSettingsController;
use App\Http\Controllers\ExamAttemptReviewController;
use App\Http\Controllers\ExamBuilderController;
use App\Http\Controllers\ExamPaperSetupController;
use App\Http\Controllers\ICardController;
use App\Http\Controllers\InstituteSettingsController;
use App\Http\Controllers\AdmissionLeadController;
use App\Http\Controllers\LicenseSettingsController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SchoolModuleController;
use App\Http\Controllers\StudentPasswordController;
use App\Http\Controllers\StudentPortalController;

// Payroll module routes (only if enabled)
\App\Models\LicenseConfig::current()?->moduleEnabled('payroll') && require base_path('routes/payroll.php');
use App\Support\SchoolModuleRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Auth::routes(['register' => false]);

Route::middleware('guest')->prefix('student')->name('student.')->group(function (): void {
    Route::get('/password/reset', [StudentPasswordController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [StudentPasswordController::class, 'reset'])->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::redirect('/home', '/dashboard');

    Route::middleware('student')->prefix('student')->name('student.')->group(function (): void {
        Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [StudentPortalController::class, 'profile'])->name('profile');
        Route::get('/exams', [StudentPortalController::class, 'exams'])->middleware('module:exams')->name('exams');
        Route::get('/exams/{exam}/start', [StudentPortalController::class, 'startExam'])->middleware('module:exams')->name('exams.start');
        Route::post('/exams/{exam}/monitor', [StudentPortalController::class, 'monitorExam'])->middleware('module:exams')->name('exams.monitor');
        Route::post('/exams/{exam}/autosave', [StudentPortalController::class, 'autosaveExam'])->middleware('module:exams')->name('exams.autosave');
        Route::post('/exams/{exam}/submit', [StudentPortalController::class, 'submitExam'])->middleware('module:exams')->name('exams.submit');
        Route::get('/exams/{exam}/result/{attempt}', [StudentPortalController::class, 'result'])->middleware('module:exams')->name('exams.result');
        Route::get('/books', [StudentPortalController::class, 'books'])->middleware('module:study-materials')->name('books');
        Route::get('/books/{material}/download', [StudentPortalController::class, 'downloadBook'])->middleware('module:study-materials')->name('books.download');
        Route::get('/exam-papers/{paper}/download', [StudentPortalController::class, 'downloadExamPaper'])->middleware('module:exam-papers')->name('exam-papers.download');
        Route::get('/fees', [StudentPortalController::class, 'fees'])->middleware('module:fees')->name('fees');
        Route::get('/fees/{fee}/receipt', [StudentPortalController::class, 'downloadFeeReceipt'])->middleware('module:fees')->name('fees.receipt');
        Route::get('/payments/{payment}/receipt', [StudentPortalController::class, 'downloadPaymentReceipt'])->middleware('module:fees')->name('payments.receipt');
        Route::get('/attendance', [StudentPortalController::class, 'attendance'])->middleware('module:attendance')->name('attendance');
        Route::get('/mycalendar', [StudentPortalController::class, 'myCalendar'])->middleware('module:calendar')->name('mycalendar');
        Route::post('/mycalendar/map-holiday', [StudentPortalController::class, 'mapHoliday'])->middleware('module:calendar')->name('mycalendar.map-holiday');
        Route::get('/results', [StudentPortalController::class, 'results'])->middleware('module:results')->name('results');
        Route::get('/results/pdf', [StudentPortalController::class, 'downloadResultsPdf'])->middleware('module:results')->name('results.pdf');
        Route::get('/password/change', [StudentPasswordController::class, 'edit'])->name('password.edit');
        Route::post('/password/change', [StudentPasswordController::class, 'update'])->name('password.update');
    });

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change.edit');
    Route::post('/password/change', [PasswordController::class, 'update'])->name('password.change.update');

    Route::middleware('ensure-password-changed')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/settings/license', [LicenseSettingsController::class, 'edit'])->middleware('super-admin')->name('license-settings.edit');
        Route::post('/settings/license', [LicenseSettingsController::class, 'update'])->middleware('super-admin')->name('license-settings.update');
        Route::post('/settings/license/generate-key', [LicenseSettingsController::class, 'generateKey'])->middleware('super-admin')->name('license-settings.generate-key');
        Route::post('/settings/license/impact-preview', [LicenseSettingsController::class, 'impactPreview'])->middleware('super-admin')->name('license-settings.impact-preview');
        Route::post('/settings/license/rollback-last', [LicenseSettingsController::class, 'rollbackLast'])->middleware('super-admin')->name('license-settings.rollback-last');
        Route::get('/settings/billing', [BillingSettingsController::class, 'index'])->middleware('super-admin')->name('billing-settings.index');
        Route::get('/settings/access-matrix', [DashboardController::class, 'accessMatrix'])->middleware('super-admin')->name('settings.access-matrix');
        Route::get('/settings/password/hash-check', [PasswordController::class, 'hashCheckForm'])->name('password.hash-check.form');
        Route::post('/settings/password/hash-check', [PasswordController::class, 'hashCheckVerify'])->name('password.hash-check.verify');
        Route::get('/settings/institute', [InstituteSettingsController::class, 'edit'])->middleware('super-admin')->name('institute-settings.edit');
        Route::post('/settings/institute/profile', [InstituteSettingsController::class, 'updateProfile'])->middleware('super-admin')->name('institute-settings.profile.update');
        Route::post('/settings/institute/branches', [InstituteSettingsController::class, 'storeBranch'])->middleware('super-admin')->name('institute-settings.branches.store');
        Route::post('/settings/institute/mappings', [InstituteSettingsController::class, 'updateMappings'])->middleware('super-admin')->name('institute-settings.mappings.update');
        Route::post('/settings/institute/switch-branch', [InstituteSettingsController::class, 'switchBranch'])->name('institute-settings.switch-branch');
        Route::get('/exam-builder', [ExamBuilderController::class, 'index'])->middleware('module:exams')->name('exam-builder.index');
        Route::get('/exam-builder/{exam}', [ExamBuilderController::class, 'show'])->middleware('module:exams')->name('exam-builder.show');
        Route::post('/exam-builder/{exam}/settings', [ExamBuilderController::class, 'updateExam'])->middleware('module:exams')->name('exam-builder.settings.update');
        Route::post('/exam-builder/{exam}/questions', [ExamBuilderController::class, 'storeQuestion'])->middleware('module:exam-questions')->name('exam-builder.questions.store');
        Route::post('/exam-builder/{exam}/questions/import', [ExamBuilderController::class, 'importQuestions'])->middleware('module:exam-questions')->name('exam-builder.questions.import');
        Route::put('/exam-builder/{exam}/questions/{question}', [ExamBuilderController::class, 'updateQuestion'])->middleware('module:exam-questions')->name('exam-builder.questions.update');
        Route::delete('/exam-builder/{exam}/questions/{question}', [ExamBuilderController::class, 'destroyQuestion'])->middleware('module:exam-questions')->name('exam-builder.questions.destroy');
        // Exam paper set-wise upload
        Route::get('/exam-papers/setup', [ExamPaperSetupController::class, 'index'])->middleware('module:exam-papers')->name('exam-papers.setup');
        Route::get('/exam-papers/class-sections', [ExamPaperSetupController::class, 'classSections'])->middleware('module:exam-papers')->name('exam-papers.class-sections');
        Route::get('/exam-papers/class-exams', [ExamPaperSetupController::class, 'classExams'])->middleware('module:exam-papers')->name('exam-papers.class-exams');
        Route::get('/exam-papers/assignment-preview', [ExamPaperSetupController::class, 'assignmentPreview'])->middleware('module:exam-papers')->name('exam-papers.assignment-preview');
        Route::post('/exam-builder/{exam}/papers', [ExamPaperSetupController::class, 'store'])->middleware('module:exam-papers')->name('exam-paper-setup.store');
        Route::delete('/exam-papers/{paper}/remove', [ExamPaperSetupController::class, 'destroyPaper'])->middleware('module:exam-papers')->name('exam-paper-setup.destroy');
        Route::get('/exam-attempts/review', [ExamAttemptReviewController::class, 'index'])->middleware('module:exams')->name('exam-attempts.review.index');
        Route::get('/exam-attempts/review/{attempt}', [ExamAttemptReviewController::class, 'show'])->middleware('module:exams')->name('exam-attempts.review.show');
        Route::get('/icards', [ICardController::class, 'index'])->middleware('module:icards')->name('icards.index');
        Route::get('/icards/generate/{type}/{id}/{template?}', [ICardController::class, 'generate'])->where('template', 'standard|branded|premium')->middleware('module:icards')->name('icards.generate');
        Route::post('/icards/signature', [ICardController::class, 'uploadSignature'])->middleware('module:icards')->name('icards.signature.upload');
        Route::post('/icards/bulk-download', [ICardController::class, 'bulkDownload'])->middleware('module:icards')->name('icards.bulk.download');
        Route::get('/quotations', [QuotationController::class, 'create'])->middleware('module:quotations')->name('quotations.create');
        Route::get('/quotations/history', [QuotationController::class, 'index'])->middleware('module:quotations')->name('quotations.index');
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->whereNumber('quotation')->middleware('module:quotations')->name('quotations.edit');
        Route::get('/quotations/{quotation}/reuse', [QuotationController::class, 'reuse'])->whereNumber('quotation')->middleware('module:quotations')->name('quotations.reuse');
        Route::post('/quotations/{quotation}/archive', [QuotationController::class, 'archive'])->whereNumber('quotation')->middleware('module:quotations')->name('quotations.archive');
        Route::get('/quotations/{quotation}/view', [QuotationController::class, 'show'])->whereNumber('quotation')->middleware('module:quotations')->name('quotations.show');
        Route::get('/quotations/{quotation}/download', [QuotationController::class, 'downloadSaved'])->whereNumber('quotation')->middleware('module:quotations')->name('quotations.download-saved');
        Route::post('/quotations/generate-number', [QuotationController::class, 'generateNumber'])->middleware('module:quotations')->name('quotations.generate-number');
        Route::post('/quotations/preview', [QuotationController::class, 'preview'])->middleware('module:quotations')->name('quotations.preview');
        Route::post('/quotations/download', [QuotationController::class, 'download'])->middleware('module:quotations')->name('quotations.download');
        Route::post('/quotations/share-email', [QuotationController::class, 'shareEmail'])->middleware('module:quotations')->name('quotations.share-email');
        Route::post('/quotations/share-whatsapp', [QuotationController::class, 'shareWhatsapp'])->middleware('module:quotations')->name('quotations.share-whatsapp');
        Route::post('/leaves/{id}/quick-status', [SchoolModuleController::class, 'quickLeaveStatus'])
            ->whereNumber('id')
            ->middleware('module:leaves')
            ->name('leaves.quick-status');
        Route::get('/my-attendance', [SchoolModuleController::class, 'myAttendance'])
            ->middleware('module:attendance')
            ->name('my.attendance');
        Route::post('/attendance/import-excel', [SchoolModuleController::class, 'importAttendanceExcel'])
            ->middleware('module:attendance')
            ->name('attendance.import.excel');
        Route::post('/students/import-colleges', [SchoolModuleController::class, 'importStudentCollegeExcel'])
            ->middleware('module:students')
            ->name('students.import.colleges');
        Route::get('/attendance/import-template', [SchoolModuleController::class, 'downloadAttendanceImportTemplate'])
            ->middleware('module:attendance')
            ->name('attendance.import.template');
        Route::get('/students/import-template', [SchoolModuleController::class, 'downloadStudentCollegeImportTemplate'])
            ->middleware('module:students')
            ->name('students.import.template');
        Route::get('/students/export-editable-colleges', [SchoolModuleController::class, 'downloadStudentCollegeEditableExport'])
            ->middleware('module:students')
            ->name('students.export.editable.colleges');
        Route::get('/attendance/import-errors', [SchoolModuleController::class, 'downloadAttendanceImportErrors'])
            ->middleware('module:attendance')
            ->name('attendance.import.errors');
        Route::get('/students/import-errors', [SchoolModuleController::class, 'downloadStudentCollegeImportErrors'])
            ->middleware('module:students')
            ->name('students.import.errors');
        Route::post('/master-calendar/dayoff', [SchoolModuleController::class, 'storeMasterCalendarDayOff'])
            ->middleware('module:holidays')
            ->name('master.calendar.dayoff.store');
        Route::get('/admission-leads/kanban', [AdmissionLeadController::class, 'kanban'])
            ->middleware('module:admission-leads')
            ->name('admission-leads.kanban');
        Route::patch('/admission-leads/{id}/stage', [AdmissionLeadController::class, 'updateStage'])
            ->whereNumber('id')
            ->middleware('module:admission-leads')
            ->name('admission-leads.update-stage');
        Route::post('/admission-leads/{id}/convert', [AdmissionLeadController::class, 'convertToStudent'])
            ->whereNumber('id')
            ->middleware('module:admission-leads')
            ->name('admission-leads.convert');
        Route::get('/master-calendar/sections', [SchoolModuleController::class, 'masterCalendarSections'])
            ->middleware('module:attendance')
            ->name('master.calendar.sections');
        Route::get('/master-calendar/export', [SchoolModuleController::class, 'exportMasterCalendar'])
            ->middleware('module:attendance')
            ->name('master.calendar.export');
        Route::get('/master-calendar', [SchoolModuleController::class, 'masterCalendar'])
            ->middleware('module:attendance')
            ->name('master.calendar');
        Route::get('/day-wise-customization', [SchoolModuleController::class, 'dayWiseCustomizationIndex'])
            ->middleware('module:holidays')
            ->name('day-wise-customization.index');
        Route::post('/day-wise-customization/save', [SchoolModuleController::class, 'saveDayWiseEntries'])
            ->middleware('module:holidays')
            ->name('day-wise-customization.save');
        Route::delete('/day-wise-customization/{id}', [SchoolModuleController::class, 'deleteDayWiseEntry'])
            ->whereNumber('id')
            ->middleware('module:holidays')
            ->name('day-wise-customization.delete');
        Route::get('/students/calendar', [SchoolModuleController::class, 'studentCalendarIndex'])
            ->middleware('module:students')
            ->name('students.calendar.index');
        Route::get('/students/calendar/sections', [SchoolModuleController::class, 'studentCalendarSections'])
            ->middleware('module:students')
            ->name('students.calendar.sections');
        Route::get('/students/{id}/calendar', [SchoolModuleController::class, 'studentCalendar'])
            ->whereNumber('id')
            ->middleware('module:students')
            ->name('students.calendar');
        Route::get('/fees/{id}/receipt/download', [SchoolModuleController::class, 'downloadFeeReceipt'])
            ->whereNumber('id')
            ->middleware('module:fees')
            ->name('fees.receipt.download');

        // Fee Structure routes
        Route::get('/fee-structures', [SchoolModuleController::class, 'feeStructureIndex'])
            ->middleware('module:fees')
            ->name('fee-structures.index');
        Route::post('/fee-structures', [SchoolModuleController::class, 'feeStructureStore'])
            ->middleware('module:fees')
            ->name('fee-structures.store');
        Route::put('/fee-structures/{id}', [SchoolModuleController::class, 'feeStructureUpdate'])
            ->whereNumber('id')
            ->middleware('module:fees')
            ->name('fee-structures.update');
        Route::delete('/fee-structures/{id}', [SchoolModuleController::class, 'feeStructureDestroy'])
            ->whereNumber('id')
            ->middleware('module:fees')
            ->name('fee-structures.destroy');
        Route::post('/fee-structures/auto-generate', [SchoolModuleController::class, 'feeStructureAutoGenerate'])
            ->middleware('module:fees')
            ->name('fee-structures.auto-generate');

        // Certificate Generator routes
        Route::get('/certificates', [SchoolModuleController::class, 'certificateIndex'])
            ->middleware('module:students')
            ->name('certificates.index');
        Route::post('/certificates/assets', [SchoolModuleController::class, 'certificateAssetsUpdate'])
            ->middleware('module:students')
            ->name('certificates.assets.update');
        Route::get('/certificates/{type}/{studentId}', [SchoolModuleController::class, 'certificateGenerate'])
            ->where('type', 'tc|bonafide|character')
            ->whereNumber('studentId')
            ->middleware('module:students')
            ->name('certificates.generate');

        foreach (array_keys(SchoolModuleRegistry::all()) as $module) {
            Route::get('/'.$module, [SchoolModuleController::class, 'index'])
                ->defaults('module', $module)
                ->middleware('module:'.$module)
                ->name($module.'.index');
            Route::get('/'.$module.'/export/pdf', [SchoolModuleController::class, 'exportPdf'])
                ->defaults('module', $module)
                ->middleware('module:'.$module)
                ->name($module.'.export.pdf');
            Route::get('/'.$module.'/export/excel', [SchoolModuleController::class, 'exportExcel'])
                ->defaults('module', $module)
                ->middleware('module:'.$module)
                ->name($module.'.export.excel');
            Route::get('/'.$module.'/{id}', [SchoolModuleController::class, 'show'])
                ->defaults('module', $module)
                ->whereNumber('id')
                ->middleware('module:'.$module)
                ->name($module.'.show');
            Route::post('/'.$module, [SchoolModuleController::class, 'store'])
                ->defaults('module', $module)
                ->middleware('module:'.$module)
                ->name($module.'.store');
            Route::put('/'.$module.'/{id}', [SchoolModuleController::class, 'update'])
                ->defaults('module', $module)
                ->whereNumber('id')
                ->middleware('module:'.$module)
                ->name($module.'.update');
            Route::delete('/'.$module.'/{id}', [SchoolModuleController::class, 'destroy'])
                ->defaults('module', $module)
                ->whereNumber('id')
                ->middleware('module:'.$module)
                ->name($module.'.destroy');
        }
    });
});
