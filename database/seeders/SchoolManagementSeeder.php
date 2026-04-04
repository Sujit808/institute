<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\CalendarEvent;
use App\Models\Holiday;
use App\Models\SchoolNotification;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolManagementSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = array_keys(SchoolModuleRegistry::lookupPermissions());

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@school.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'permissions' => $allPermissions,
                'must_change_password' => true,
                'active' => true,
            ]
        );

        // Admin user seed
        $admin = User::updateOrCreate(
            ['email' => 'admin@meerahr.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'permissions' => $allPermissions,
                'must_change_password' => true,
                'active' => true,
            ]
        );

        $teacher = Staff::updateOrCreate(
            ['employee_id' => 'EMP-1001'],
            [
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'email' => 'teacher@school.com',
                'designation' => 'Class Teacher',
                'role_type' => 'teacher',
                'phone' => '9876543210',
                'joining_date' => now()->subYears(2)->toDateString(),
                'qualification' => 'M.Sc, B.Ed',
                'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves'],
                'status' => 'active',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher@school.com'],
            [
                'name' => $teacher->full_name,
                'password' => Hash::make('ChangeMe@123'),
                'role' => 'teacher',
                'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves'],
                'staff_id' => $teacher->id,
                'must_change_password' => true,
                'active' => true,
            ]
        );

        $class = AcademicClass::updateOrCreate(
            ['code' => 'XII-SCI'],
            [
                'name' => 'Class XII Science',
                'capacity' => 40,
                'description' => 'Senior secondary science section',
                'status' => 'active',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        $section = Section::updateOrCreate(
            ['code' => 'A-XII'],
            [
                'academic_class_id' => $class->id,
                'name' => 'Section A',
                'room_no' => '201',
                'class_teacher_id' => $teacher->id,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        $subject = Subject::updateOrCreate(
            ['code' => 'PHY-12'],
            [
                'academic_class_id' => $class->id,
                'name' => 'Physics',
                'type' => 'theory',
                'staff_id' => $teacher->id,
                'max_marks' => 100,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        $class->subjects()->syncWithoutDetaching([$subject->id]);

        $student = Student::updateOrCreate(
            ['admission_no' => 'ADM-1001'],
            [
                'academic_class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => '12A01',
                'first_name' => 'Riya',
                'last_name' => 'Sharma',
                'gender' => 'female',
                'guardian_name' => 'Amit Sharma',
                'guardian_phone' => '9988776655',
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        User::updateOrCreate(
            ['student_id' => $student->id],
            [
                'name' => $student->full_name,
                'email' => $student->email ?: 'student'.$student->id.'@students.schoolsphere.local',
                'phone' => $student->phone ?: $student->guardian_phone,
                'password' => Hash::make((string) $student->roll_no),
                'role' => 'student',
                'permissions' => [],
                'must_change_password' => false,
                'active' => true,
            ]
        );

        SchoolNotification::updateOrCreate(
            ['title' => 'Welcome to the new portal'],
            [
                'message' => 'All school operations are now available through the management dashboard.',
                'audience' => 'all',
                'publish_date' => now()->toDateString(),
                'status' => 'published',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        Holiday::updateOrCreate(
            ['title' => 'Annual Day'],
            [
                'holiday_type' => 'school',
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'description' => 'Annual day celebrations.',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        CalendarEvent::updateOrCreate(
            ['title' => 'PTM Meeting'],
            [
                'event_type' => 'meeting',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7)->addHours(2),
                'location' => 'Main Hall',
                'description' => 'Parent teacher interaction session.',
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );
    }
}
