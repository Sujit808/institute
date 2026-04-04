<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\CalendarEvent;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\Fee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Result;
use App\Models\SchoolNotification;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RichDemoSchoolSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetDemoDataset extends Command
{
    protected $signature = 'demo:reset
        {--fresh : Run migrate:fresh --seed (full database reset)}
        {--skip-clean : Skip cleanup and only re-run demo seeder}';

    protected $description = 'Reset and re-seed Meerahr rich demo dataset (students, staff, attendance, exams, fees, biometrics).';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Running migrate:fresh --seed. This will wipe all tables.');
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->line(Artisan::output());
            $this->info('Full fresh reset completed.');

            return self::SUCCESS;
        }

        if (! $this->option('skip-clean')) {
            $this->cleanupDemoData();
        }

        Artisan::call('db:seed', ['--class' => RichDemoSchoolSeeder::class, '--force' => true]);
        $this->line(Artisan::output());

        $this->info('Demo dataset reset completed successfully.');

        return self::SUCCESS;
    }

    private function cleanupDemoData(): void
    {
        $this->info('Cleaning previous demo subset...');

        DB::transaction(function (): void {
            $demoClassCodes = ['CLASS-09', 'CLASS-10', 'CLASS-11', 'CLASS-12'];
            $demoHolidayTitles = [
                'Republic Day',
                'Holi Break',
                'Ambedkar Jayanti',
                'Eid-ul-Fitr',
                'Independence Day',
                'Janmashtami',
                'Gandhi Jayanti',
                'Diwali Vacation',
                'Christmas Day',
            ];

            $demoClasses = AcademicClass::withTrashed()->whereIn('code', $demoClassCodes)->get();
            $demoClassIds = $demoClasses->pluck('id');

            $demoStudents = Student::withTrashed()
                ->whereIn('academic_class_id', $demoClassIds)
                ->where('admission_no', 'like', 'ADM-%')
                ->get();
            $demoStudentIds = $demoStudents->pluck('id');

            $demoStaff = Staff::withTrashed()->where('employee_id', 'like', 'NOI-STF-%')->get();
            $demoStaffIds = $demoStaff->pluck('id');

            $demoDevices = BiometricDevice::query()->where('device_code', 'like', 'BIO-NOI-%')->get();
            $demoDeviceIds = $demoDevices->pluck('id');

            $demoExams = Exam::withTrashed()->where('name', 'like', 'Session Assessment %')->get();
            $demoExamIds = $demoExams->pluck('id');

            $demoSubjectIds = Subject::withTrashed()
                ->where(function ($query): void {
                    $query->where('code', 'like', '%-09')
                        ->orWhere('code', 'like', '%-10')
                        ->orWhere('code', 'like', '%-11')
                        ->orWhere('code', 'like', '%-12');
                })
                ->pluck('id');

            $demoFees = Fee::withTrashed()->whereIn('student_id', $demoStudentIds)->get();
            $demoFeeIds = $demoFees->pluck('id');

            // Remove child records first.
            Attendance::withTrashed()
                ->whereIn('student_id', $demoStudentIds)
                ->orWhereIn('staff_attendance_id', $demoStaffIds)
                ->orWhere('biometric_device_id', 'like', 'BIO-NOI-%')
                ->forceDelete();

            BiometricEnrollment::query()
                ->whereIn('biometric_device_id', $demoDeviceIds)
                ->orWhereIn('student_id', $demoStudentIds)
                ->orWhereIn('staff_id', $demoStaffIds)
                ->delete();

            foreach (ExamPaper::withTrashed()->whereIn('exam_id', $demoExamIds)->get() as $paper) {
                if (! empty($paper->file_path)) {
                    Storage::disk('public')->delete($paper->file_path);
                }
                $paper->forceDelete();
            }

            ExamQuestion::withTrashed()->whereIn('exam_id', $demoExamIds)->forceDelete();
            Result::withTrashed()->whereIn('exam_id', $demoExamIds)->forceDelete();
            Result::withTrashed()->whereIn('student_id', $demoStudentIds)->forceDelete();

            Payment::withTrashed()->whereIn('fee_id', $demoFeeIds)->forceDelete();
            Fee::withTrashed()->whereIn('id', $demoFeeIds)->forceDelete();

            LeaveRequest::withTrashed()
                ->whereIn('staff_id', $demoStaffIds)
                ->orWhereIn('student_id', $demoStudentIds)
                ->forceDelete();

            DB::table('class_subject')
                ->whereIn('academic_class_id', $demoClassIds)
                ->orWhereIn('subject_id', $demoSubjectIds)
                ->delete();

            User::withTrashed()->whereIn('student_id', $demoStudentIds)->forceDelete();
            User::withTrashed()->whereIn('staff_id', $demoStaffIds)->forceDelete();

            Student::withTrashed()->whereIn('id', $demoStudentIds)->forceDelete();
            Section::withTrashed()->whereIn('academic_class_id', $demoClassIds)->forceDelete();

            Subject::withTrashed()->whereIn('id', $demoSubjectIds)->forceDelete();

            Exam::withTrashed()->whereIn('id', $demoExamIds)->forceDelete();
            AcademicClass::withTrashed()->whereIn('id', $demoClassIds)->forceDelete();

            BiometricDevice::query()->whereIn('id', $demoDeviceIds)->delete();
            Staff::withTrashed()->whereIn('id', $demoStaffIds)->forceDelete();

            Holiday::withTrashed()->whereIn('title', $demoHolidayTitles)->forceDelete();

            SchoolNotification::withTrashed()
                ->whereIn('title', [
                    'Welcome to Meerahr Noida Demo Campus',
                    'Biometric Attendance Live',
                    'Exam Sets Available',
                ])->forceDelete();

            CalendarEvent::withTrashed()
                ->whereIn('title', [
                    'Parent Orientation',
                    'Science Practical Week',
                    'Unit Test Window',
                ])->forceDelete();

            Storage::disk('public')->deleteDirectory('avatars/staff');
            Storage::disk('public')->deleteDirectory('avatars/students');
            Storage::disk('public')->deleteDirectory('exam-papers/demo');
        });

        $this->info('Demo cleanup completed.');
    }
}
