<?php

namespace Database\Seeders;

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
use App\Support\SchoolModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class RichDemoSchoolSeeder extends Seeder
{
    private User $superAdmin;

    public function run(): void
    {
        $allPermissions = array_keys(SchoolModuleRegistry::lookupPermissions());

        $this->superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@school.com'],
            [
                'name' => 'Meerahr Super Admin',
                'phone' => '9876543200',
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'permissions' => $allPermissions,
                'must_change_password' => false,
                'active' => true,
            ]
        );

        $staff = $this->seedStaff($allPermissions);
        [$classes, $sectionsByClass, $subjectsByClass] = $this->seedAcademics($staff);
        $students = $this->seedStudents($classes, $sectionsByClass);
        [$devices, $studentEnrollments, $staffEnrollments] = $this->seedBiometricData($students, $staff);

        $this->seedAttendance($students, $staff, $devices, $studentEnrollments, $staffEnrollments, $sectionsByClass);
        $this->seedExamsAndQuestions($classes, $subjectsByClass);
        $this->seedExamPapers($classes);
        $this->seedFeesPayments($students);
        $this->seedResults($classes, $subjectsByClass, $students, $staff);
        $this->seedHolidays();
        $this->seedLeaves($staff, $students);
        $this->seedAnnouncements();
    }

    private function seedStaff(array $allPermissions): Collection
    {
        $definitions = [
            ['employee_id' => 'NOI-STF-001', 'first_name' => 'Amit', 'last_name' => 'Bhardwaj', 'email' => 'amit.bhardwaj@meerahr.local', 'phone' => '9818001001', 'designation' => 'Mathematics Teacher', 'role_type' => 'teacher', 'qualification' => 'M.Sc Mathematics, B.Ed', 'experience_years' => 8, 'leave_balance_days' => 10, 'salary' => 52000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-002', 'first_name' => 'Neha', 'last_name' => 'Tyagi', 'email' => 'neha.tyagi@meerahr.local', 'phone' => '9818001002', 'designation' => 'Science Teacher', 'role_type' => 'teacher', 'qualification' => 'M.Sc Physics, B.Ed', 'experience_years' => 7, 'leave_balance_days' => 11, 'salary' => 54000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-003', 'first_name' => 'Rohit', 'last_name' => 'Chauhan', 'email' => 'rohit.chauhan@meerahr.local', 'phone' => '9818001003', 'designation' => 'English Teacher', 'role_type' => 'teacher', 'qualification' => 'M.A English, B.Ed', 'experience_years' => 6, 'leave_balance_days' => 9, 'salary' => 50000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-004', 'first_name' => 'Pooja', 'last_name' => 'Saxena', 'email' => 'pooja.saxena@meerahr.local', 'phone' => '9818001004', 'designation' => 'Hindi Teacher', 'role_type' => 'teacher', 'qualification' => 'M.A Hindi, B.Ed', 'experience_years' => 5, 'leave_balance_days' => 12, 'salary' => 47000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-005', 'first_name' => 'Imran', 'last_name' => 'Qureshi', 'email' => 'imran.qureshi@meerahr.local', 'phone' => '9818001005', 'designation' => 'Computer Teacher', 'role_type' => 'teacher', 'qualification' => 'MCA, B.Ed', 'experience_years' => 4, 'leave_balance_days' => 8, 'salary' => 51000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-006', 'first_name' => 'Kavita', 'last_name' => 'Rana', 'email' => 'kavita.rana@meerahr.local', 'phone' => '9818001006', 'designation' => 'Social Science Teacher', 'role_type' => 'teacher', 'qualification' => 'M.A History, B.Ed', 'experience_years' => 9, 'leave_balance_days' => 14, 'salary' => 53000, 'permissions' => ['students', 'attendance', 'results', 'timetable', 'leaves']],
            ['employee_id' => 'NOI-STF-007', 'first_name' => 'Arjun', 'last_name' => 'Malik', 'email' => 'arjun.malik@meerahr.local', 'phone' => '9818001007', 'designation' => 'Operations Admin', 'role_type' => 'admin', 'qualification' => 'MBA Operations', 'experience_years' => 10, 'leave_balance_days' => 15, 'salary' => 68000, 'permissions' => $allPermissions],
            ['employee_id' => 'NOI-STF-008', 'first_name' => 'Sneha', 'last_name' => 'Bisht', 'email' => 'sneha.bisht@meerahr.local', 'phone' => '9818001008', 'designation' => 'HR Executive', 'role_type' => 'hr', 'qualification' => 'MBA HR', 'experience_years' => 6, 'leave_balance_days' => 16, 'salary' => 56000, 'permissions' => ['students', 'attendance', 'results', 'staff', 'leaves', 'notifications', 'calendar', 'holidays', 'icards']],
            ['employee_id' => 'NOI-STF-009', 'first_name' => 'Deepak', 'last_name' => 'Tomar', 'email' => 'deepak.tomar@meerahr.local', 'phone' => '9818001009', 'designation' => 'Lab Assistant', 'role_type' => 'staff', 'qualification' => 'B.Sc', 'experience_years' => 5, 'leave_balance_days' => 10, 'salary' => 32000, 'permissions' => []],
            ['employee_id' => 'NOI-STF-010', 'first_name' => 'Farah', 'last_name' => 'Naqvi', 'email' => 'farah.naqvi@meerahr.local', 'phone' => '9818001010', 'designation' => 'Front Office Coordinator', 'role_type' => 'staff', 'qualification' => 'B.Com', 'experience_years' => 3, 'leave_balance_days' => 12, 'salary' => 30000, 'permissions' => []],
        ];

        return collect($definitions)->map(function (array $definition, int $index) {
            $photoPath = $this->storeAvatar('staff', $definition['employee_id'], $definition['first_name'].' '.$definition['last_name'], $this->palette($index));

            $staff = Staff::updateOrCreate(
                ['employee_id' => $definition['employee_id']],
                [
                    'first_name' => $definition['first_name'],
                    'last_name' => $definition['last_name'],
                    'email' => $definition['email'],
                    'phone' => $definition['phone'],
                    'designation' => $definition['designation'],
                    'role_type' => $definition['role_type'],
                    'joining_date' => now()->subYears($definition['experience_years'])->subMonths($index + 1)->toDateString(),
                    'qualification' => $definition['qualification'],
                    'permissions' => $definition['permissions'],
                    'experience_years' => $definition['experience_years'],
                    'leave_balance_days' => $definition['leave_balance_days'],
                    'salary' => $definition['salary'],
                    'address' => 'Sector '.(21 + $index).', Noida, Gautam Buddha Nagar, Uttar Pradesh',
                    'aadhar_number' => '7214'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                    'pan_number' => 'NODA'.chr(65 + $index).'1234Z',
                    'photo' => $photoPath,
                    'status' => 'active',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            if (in_array($staff->role_type, ['teacher', 'admin', 'hr'], true)) {
                User::updateOrCreate(
                    ['staff_id' => $staff->id],
                    [
                        'name' => $staff->full_name,
                        'email' => $staff->email,
                        'phone' => $staff->phone,
                        'password' => Hash::make('Password@123'),
                        'photo' => $staff->photo,
                        'role' => $staff->role_type,
                        'permissions' => $definition['permissions'],
                        'must_change_password' => false,
                        'active' => true,
                    ]
                );
            }

            return $staff;
        });
    }

    private function seedAcademics(Collection $staff): array
    {
        $teachers = $staff->where('role_type', 'teacher')->values();

        $classDefinitions = [
            ['label' => '9th', 'code' => 'CLASS-09', 'capacity' => 50, 'subjects' => ['English', 'Hindi', 'Mathematics', 'Science', 'Social Science']],
            ['label' => '10th', 'code' => 'CLASS-10', 'capacity' => 50, 'subjects' => ['English', 'Hindi', 'Mathematics', 'Science', 'Social Science']],
            ['label' => '11th', 'code' => 'CLASS-11', 'capacity' => 50, 'subjects' => ['English', 'Physics', 'Chemistry', 'Mathematics', 'Computer Science']],
            ['label' => '12th', 'code' => 'CLASS-12', 'capacity' => 50, 'subjects' => ['English', 'Physics', 'Chemistry', 'Mathematics', 'Computer Science']],
        ];

        $classes = collect();
        $sectionsByClass = [];
        $subjectsByClass = [];

        foreach ($classDefinitions as $classIndex => $definition) {
            $class = AcademicClass::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['label'],
                    'capacity' => $definition['capacity'],
                    'description' => $definition['label'].' standard demo batch for Noida campus.',
                    'status' => 'active',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            $classes->push($class);

            $sectionsByClass[$class->id] = collect(['A', 'B'])->map(function (string $suffix, int $sectionIndex) use ($class, $classIndex, $teachers) {
                return Section::updateOrCreate(
                    ['code' => $class->name.'-'.$suffix],
                    [
                        'academic_class_id' => $class->id,
                        'name' => 'Section '.$suffix,
                        'room_no' => (string) (201 + ($classIndex * 10) + $sectionIndex),
                        'class_teacher_id' => optional($teachers->get(($classIndex + $sectionIndex) % max(1, $teachers->count())))->id,
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );
            });

            $subjectsByClass[$class->id] = collect($definition['subjects'])->map(function (string $subjectName, int $subjectIndex) use ($class, $teachers) {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subjectName), 0, 4)).'-'.str_pad((string) preg_replace('/\D+/', '', $class->name), 2, '0', STR_PAD_LEFT);

                $subject = Subject::updateOrCreate(
                    ['code' => $code],
                    [
                        'academic_class_id' => $class->id,
                        'name' => $subjectName,
                        'type' => 'theory',
                        'staff_id' => optional($teachers->get($subjectIndex % max(1, $teachers->count())))->id,
                        'max_marks' => 100,
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );

                $class->subjects()->syncWithoutDetaching([$subject->id]);

                return $subject;
            });
        }

        return [$classes, $sectionsByClass, $subjectsByClass];
    }

    private function seedStudents(Collection $classes, array $sectionsByClass): Collection
    {
        $maleFirstNames = ['Aarav', 'Vivaan', 'Aditya', 'Krishna', 'Ishaan', 'Priyansh', 'Rudra', 'Ayush', 'Lakshya', 'Shaurya', 'Ansh', 'Yuvraj', 'Harsh', 'Mohit', 'Kunal'];
        $femaleFirstNames = ['Ananya', 'Aadhya', 'Myra', 'Kiara', 'Diya', 'Pari', 'Saanvi', 'Navya', 'Riya', 'Ira', 'Prisha', 'Kashvi', 'Nitya', 'Muskan', 'Tanya'];
        $lastNames = ['Sharma', 'Verma', 'Singh', 'Yadav', 'Gupta', 'Chauhan', 'Tyagi', 'Tomar', 'Malik', 'Khan', 'Ali', 'Agarwal', 'Rastogi', 'Bhati', 'Saxena'];
        $guardianFirstNames = ['Rajesh', 'Sunil', 'Amit', 'Vikas', 'Rakesh', 'Neeraj', 'Suresh', 'Pankaj', 'Naveen', 'Arvind', 'Pooja', 'Sunita', 'Kavita', 'Shalini', 'Meena'];

        $students = collect();

        foreach ($classes as $classIndex => $class) {
            $sections = $sectionsByClass[$class->id];
            $classNumber = preg_replace('/\D+/', '', $class->name) ?: (string) ($classIndex + 9);

            foreach ($sections as $sectionIndex => $section) {
                for ($i = 1; $i <= 25; $i++) {
                    $serial = ($sectionIndex * 25) + $i;
                    $gender = $serial % 2 === 0 ? 'female' : 'male';
                    $firstNamePool = $gender === 'female' ? $femaleFirstNames : $maleFirstNames;
                    $firstName = $firstNamePool[($serial + $classIndex + $sectionIndex) % count($firstNamePool)];
                    $lastName = $lastNames[($serial + $classIndex) % count($lastNames)];
                    $guardianName = $guardianFirstNames[($serial + $sectionIndex) % count($guardianFirstNames)].' '.$lastNames[($serial + 3) % count($lastNames)];
                    $admissionNo = 'ADM-'.$classNumber.$section->name[strlen($section->name) - 1].str_pad((string) $i, 3, '0', STR_PAD_LEFT);
                    $rollNo = $classNumber.$section->name[strlen($section->name) - 1].str_pad((string) $i, 3, '0', STR_PAD_LEFT);
                    $email = strtolower($firstName.'.'.$lastName.'.'.$rollNo.'@students.meerahr.local');
                    $photoPath = $this->storeAvatar('students', $admissionNo, $firstName.' '.$lastName, $this->palette($classIndex + $sectionIndex + $i));

                    $student = Student::updateOrCreate(
                        ['admission_no' => $admissionNo],
                        [
                            'academic_class_id' => $class->id,
                            'section_id' => $section->id,
                            'roll_no' => $rollNo,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'gender' => $gender,
                            'date_of_birth' => now()->subYears((int) $classNumber + 5)->subDays($serial)->toDateString(),
                            'phone' => '98'.str_pad((string) ($classIndex + 1), 2, '0', STR_PAD_LEFT).str_pad((string) ($sectionIndex + 1), 2, '0', STR_PAD_LEFT).str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
                            'email' => $email,
                            'guardian_name' => $guardianName,
                            'guardian_phone' => '97'.str_pad((string) ($classIndex + 1), 2, '0', STR_PAD_LEFT).str_pad((string) ($sectionIndex + 1), 2, '0', STR_PAD_LEFT).str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
                            'admission_date' => now()->subMonths(9)->toDateString(),
                            'blood_group' => ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-'][($serial + $classIndex) % 6],
                            'address' => 'House '.(100 + $serial).', Sector '.(45 + (($serial + $classIndex) % 30)).', Noida, Gautam Buddha Nagar, Uttar Pradesh',
                            'aadhar_number' => '6312'.str_pad((string) (($classIndex * 50) + $serial), 8, '0', STR_PAD_LEFT),
                            'photo' => $photoPath,
                            'status' => 'active',
                            'created_by' => $this->superAdmin->id,
                            'updated_by' => $this->superAdmin->id,
                        ]
                    );

                    User::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'name' => $student->full_name,
                            'email' => $student->email,
                            'phone' => $student->guardian_phone,
                            'password' => Hash::make((string) $student->roll_no),
                            'photo' => $student->photo,
                            'role' => 'student',
                            'permissions' => [],
                            'must_change_password' => false,
                            'active' => true,
                        ]
                    );

                    $students->push($student);
                }
            }
        }

        return $students;
    }

    private function seedBiometricData(Collection $students, Collection $staff): array
    {
        $deviceDefinitions = [
            ['device_code' => 'BIO-NOI-MAIN-01', 'device_name' => 'Main Gate Face Scanner', 'brand' => 'ZKTeco', 'model_no' => 'MB20', 'ip_address' => '192.168.10.11', 'port' => 4370, 'location' => 'Main Gate', 'device_type' => 'face', 'communication' => 'push_api'],
            ['device_code' => 'BIO-NOI-ACAD-01', 'device_name' => 'Academic Block Finger Scanner', 'brand' => 'eSSL', 'model_no' => 'X990', 'ip_address' => '192.168.10.12', 'port' => 4370, 'location' => 'Academic Block', 'device_type' => 'fingerprint', 'communication' => 'push_api'],
            ['device_code' => 'BIO-NOI-STAFF-01', 'device_name' => 'Staff Room Hybrid Device', 'brand' => 'BioMax', 'model_no' => 'N-BM50', 'ip_address' => '192.168.10.13', 'port' => 8080, 'location' => 'Staff Room', 'device_type' => 'multi', 'communication' => 'push_api'],
        ];

        $devices = collect($deviceDefinitions)->map(function (array $definition) {
            return BiometricDevice::updateOrCreate(
                ['device_code' => $definition['device_code']],
                array_merge($definition, [
                    'status' => 'active',
                    'last_sync_at' => now()->subMinutes(5),
                    'notes' => 'Auto-generated demo biometric device for Meerahr Noida campus.',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ])
            );
        })->keyBy('device_code');

        $studentEnrollments = $students->mapWithKeys(function (Student $student, int $index) use ($devices) {
            $device = in_array((string) $student->academicClass?->name, ['9th', '10th'], true)
                ? $devices['BIO-NOI-MAIN-01']
                : $devices['BIO-NOI-ACAD-01'];

            $punchId = 'STU'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

            $enrollment = BiometricEnrollment::updateOrCreate(
                ['biometric_device_id' => $device->id, 'punch_id' => $punchId],
                [
                    'enrollment_for' => 'student',
                    'student_id' => $student->id,
                    'staff_id' => null,
                    'finger_index' => (string) ($index % 10),
                    'status' => 'active',
                    'enrolled_at' => now()->subMonths(2)->addMinutes($index),
                    'notes' => 'Auto-generated student machine enrollment.',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            return [$student->id => $enrollment];
        });

        $staffEnrollments = $staff->mapWithKeys(function (Staff $staffMember, int $index) use ($devices) {
            $device = $devices['BIO-NOI-STAFF-01'];
            $punchId = 'STF'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $enrollment = BiometricEnrollment::updateOrCreate(
                ['biometric_device_id' => $device->id, 'punch_id' => $punchId],
                [
                    'enrollment_for' => 'staff',
                    'student_id' => null,
                    'staff_id' => $staffMember->id,
                    'finger_index' => (string) (($index + 2) % 10),
                    'status' => 'active',
                    'enrolled_at' => now()->subMonths(3)->addMinutes($index),
                    'notes' => 'Auto-generated staff machine enrollment.',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            return [$staffMember->id => $enrollment];
        });

        return [$devices, $studentEnrollments, $staffEnrollments];
    }

    private function seedAttendance(Collection $students, Collection $staff, Collection $devices, Collection $studentEnrollments, Collection $staffEnrollments, array $sectionsByClass): void
    {
        $dates = collect(range(1, 7))
            ->map(fn (int $offset) => now()->subDays($offset)->startOfDay())
            ->reject(fn ($date) => in_array($date->dayOfWeekIso, [6, 7], true))
            ->values();

        foreach ($dates as $dayIndex => $date) {
            foreach ($students as $studentIndex => $student) {
                $enrollment = $studentEnrollments->get($student->id);
                $hash = crc32($student->admission_no.'-'.$date->toDateString());
                $statusBucket = $hash % 100;
                $status = $statusBucket < 86 ? 'present' : ($statusBucket < 92 ? 'late' : ($statusBucket < 97 ? 'leave' : 'absent'));
                $method = $statusBucket < 72 ? 'biometric_machine' : ($statusBucket < 84 ? 'mobile_face' : ($statusBucket < 92 ? 'mobile_finger' : 'manual'));
                $capturedAt = $date->copy()->setTime(7 + (($studentIndex + $dayIndex) % 2), 35 + ($studentIndex % 20));
                $deviceCode = $method === 'biometric_machine' ? optional($enrollment?->device)->device_code : null;
                $section = $student->section;
                $markedBy = optional($section)->class_teacher_id;

                Attendance::updateOrCreate(
                    [
                        'attendance_for' => 'student',
                        'attendance_date' => $date->toDateString(),
                        'student_id' => $student->id,
                    ],
                    [
                        'staff_attendance_id' => null,
                        'academic_class_id' => $student->academic_class_id,
                        'section_id' => $student->section_id,
                        'staff_id' => $markedBy,
                        'marked_by_staff_id' => $markedBy,
                        'attendance_method' => $method,
                        'biometric_device_id' => $deviceCode,
                        'biometric_log_id' => $deviceCode ? 'LOG-STU-'.$date->format('Ymd').'-'.$student->id : null,
                        'capture_payload' => [
                            'source' => $method,
                            'punch_id' => $enrollment?->punch_id,
                            'location' => $deviceCode,
                        ],
                        'captured_at' => $capturedAt,
                        'status' => $status,
                        'sync_status' => 'synced',
                        'remarks' => $status === 'late' ? 'Reached after assembly.' : null,
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );
            }

            foreach ($staff as $staffIndex => $staffMember) {
                $enrollment = $staffEnrollments->get($staffMember->id);
                $hash = crc32($staffMember->employee_id.'-'.$date->toDateString());
                $statusBucket = $hash % 100;
                $status = $statusBucket < 88 ? 'present' : ($statusBucket < 94 ? 'late' : ($statusBucket < 97 ? 'leave' : 'absent'));
                $method = $statusBucket < 78 ? 'biometric_machine' : ($statusBucket < 88 ? 'mobile_face' : 'manual');
                $capturedAt = $date->copy()->setTime(7 + (($staffIndex + $dayIndex) % 2), 55 + ($staffIndex % 5));
                $deviceCode = $method === 'biometric_machine' ? optional($enrollment?->device)->device_code : null;

                Attendance::updateOrCreate(
                    [
                        'attendance_for' => 'staff',
                        'attendance_date' => $date->toDateString(),
                        'staff_attendance_id' => $staffMember->id,
                    ],
                    [
                        'student_id' => null,
                        'academic_class_id' => null,
                        'section_id' => null,
                        'staff_id' => $this->superAdmin->staff_id,
                        'marked_by_staff_id' => null,
                        'attendance_method' => $method,
                        'biometric_device_id' => $deviceCode,
                        'biometric_log_id' => $deviceCode ? 'LOG-STF-'.$date->format('Ymd').'-'.$staffMember->id : null,
                        'capture_payload' => [
                            'source' => $method,
                            'punch_id' => $enrollment?->punch_id,
                            'device' => $deviceCode,
                        ],
                        'captured_at' => $capturedAt,
                        'status' => $status,
                        'sync_status' => 'synced',
                        'remarks' => $status === 'leave' ? 'Planned leave entry.' : null,
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );
            }
        }
    }

    private function seedExamsAndQuestions(Collection $classes, array $subjectsByClass): void
    {
        foreach ($classes as $classIndex => $class) {
            $exam = Exam::updateOrCreate(
                ['name' => 'Session Assessment '.$class->name],
                [
                    'academic_class_id' => $class->id,
                    'exam_type' => $classIndex % 2 === 0 ? 'unit' : 'midterm',
                    'question_sets' => ['A', 'B', 'C', 'D', 'E'],
                    'duration_minutes' => 90,
                    'negative_mark_per_wrong' => 0.25,
                    'start_date' => now()->subDays(20)->toDateString(),
                    'end_date' => now()->subDays(18)->toDateString(),
                    'total_marks' => 100,
                    'status' => 'scheduled',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            $subjects = $subjectsByClass[$class->id]->values();

            foreach (['A', 'B', 'C', 'D', 'E'] as $setIndex => $setCode) {
                for ($questionNo = 1; $questionNo <= 5; $questionNo++) {
                    $subject = $subjects->get(($setIndex + $questionNo - 1) % max(1, $subjects->count()));

                    ExamQuestion::updateOrCreate(
                        [
                            'exam_id' => $exam->id,
                            'set_code' => $setCode,
                            'question_order' => $questionNo,
                        ],
                        [
                            'subject_id' => $subject?->id,
                            'question_text' => 'Set '.$setCode.' Question '.$questionNo.' for '.$class->name.' on '.($subject?->name ?? 'General Studies').'.',
                            'option_a' => 'Correct concept statement',
                            'option_b' => 'Distractor option 1',
                            'option_c' => 'Distractor option 2',
                            'option_d' => 'Distractor option 3',
                            'correct_option' => ['A', 'B', 'C', 'D'][($questionNo + $setIndex) % 4],
                            'marks' => 2,
                            'status' => 'active',
                            'created_by' => $this->superAdmin->id,
                            'updated_by' => $this->superAdmin->id,
                        ]
                    );
                }
            }
        }
    }

    private function seedExamPapers(Collection $classes): void
    {
        $setCodes = ['A', 'B', 'C', 'D', 'E'];

        foreach ($classes as $class) {
            $exam = Exam::query()->where('name', 'Session Assessment '.$class->name)->first();
            if (! $exam) {
                continue;
            }

            foreach ($setCodes as $setCode) {
                $filePath = 'exam-papers/demo/'.$class->code.'/set-'.$setCode.'.pdf';
                Storage::disk('public')->put($filePath, $this->dummyPdfContent('Session Assessment '.$class->name, 'Set '.$setCode));

                ExamPaper::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'set_code' => $setCode,
                        'title' => $class->name.' Set '.$setCode.' Paper',
                    ],
                    [
                        'instructions' => 'Attempt all questions. This is a demo generated paper for seeded data.',
                        'file_path' => $filePath,
                        'status' => 'active',
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );
            }
        }
    }

    private function seedFeesPayments(Collection $students): void
    {
        $feeTypes = ['tuition', 'transport', 'hostel', 'misc'];
        $paymentModes = ['cash', 'upi', 'bank', 'card'];

        foreach ($students as $index => $student) {
            $feeType = $feeTypes[$index % count($feeTypes)];
            $amount = 18000 + (($index % 8) * 1250);
            $paidBucket = $index % 5;
            $paidAmount = match ($paidBucket) {
                0 => (float) $amount,
                1 => round($amount * 0.75, 2),
                2 => round($amount * 0.5, 2),
                3 => round($amount * 0.25, 2),
                default => 0.0,
            };

            $status = $paidAmount >= $amount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');
            $receiptNo = 'RCPT-'.$student->roll_no.'-'.now()->format('Y');

            $fee = Fee::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'fee_type' => $feeType,
                    'due_date' => now()->addDays(7 + ($index % 25))->toDateString(),
                ],
                [
                    'academic_class_id' => $student->academic_class_id,
                    'amount' => $amount,
                    'paid_amount' => $paidAmount,
                    'receipt_no' => $paidAmount > 0 ? $receiptNo : null,
                    'payment_mode' => $paidAmount > 0 ? $paymentModes[$index % count($paymentModes)] : null,
                    'status' => $status,
                    'remarks' => 'Demo fee record generated for seeded dataset.',
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );

            if ($paidAmount > 0) {
                $firstInstallment = $paidAmount >= ($amount * 0.6) ? round($paidAmount * 0.6, 2) : $paidAmount;
                $secondInstallment = round($paidAmount - $firstInstallment, 2);

                Payment::updateOrCreate(
                    [
                        'fee_id' => $fee->id,
                        'receipt_no' => $receiptNo.'-1',
                    ],
                    [
                        'student_id' => $student->id,
                        'amount' => $firstInstallment,
                        'payment_date' => now()->subDays(($index % 20) + 2)->toDateString(),
                        'payment_mode' => $paymentModes[$index % count($paymentModes)],
                        'remarks' => 'Demo fee installment 1.',
                        'created_by' => $this->superAdmin->id,
                        'updated_by' => $this->superAdmin->id,
                    ]
                );

                if ($secondInstallment > 0) {
                    Payment::updateOrCreate(
                        [
                            'fee_id' => $fee->id,
                            'receipt_no' => $receiptNo.'-2',
                        ],
                        [
                            'student_id' => $student->id,
                            'amount' => $secondInstallment,
                            'payment_date' => now()->subDays($index % 10)->toDateString(),
                            'payment_mode' => $paymentModes[($index + 1) % count($paymentModes)],
                            'remarks' => 'Demo fee installment 2.',
                            'created_by' => $this->superAdmin->id,
                            'updated_by' => $this->superAdmin->id,
                        ]
                    );
                }
            }
        }
    }

    private function seedResults(Collection $classes, array $subjectsByClass, Collection $students, Collection $staff): void
    {
        $teacherId = optional($staff->where('role_type', 'teacher')->first())->id;

        foreach ($classes as $class) {
            $exam = Exam::query()->where('name', 'Session Assessment '.$class->name)->first();
            if (! $exam) {
                continue;
            }

            $classStudents = $students->where('academic_class_id', $class->id)->values();
            $subjects = ($subjectsByClass[$class->id] ?? collect())->take(3)->values();

            foreach ($classStudents as $studentIndex => $student) {
                foreach ($subjects as $subjectIndex => $subject) {
                    $marks = 45 + (($studentIndex * 7 + $subjectIndex * 11) % 56);
                    $grade = $this->gradeFromMarks($marks);

                    Result::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'exam_id' => $exam->id,
                            'subject_id' => $subject->id,
                        ],
                        [
                            'staff_id' => $teacherId,
                            'marks_obtained' => $marks,
                            'grade' => $grade,
                            'remarks' => 'Demo generated set-wise assessment result.',
                            'created_by' => $this->superAdmin->id,
                            'updated_by' => $this->superAdmin->id,
                        ]
                    );
                }
            }
        }
    }

    private function seedHolidays(): void
    {
        $holidays = [
            ['title' => 'Republic Day', 'holiday_type' => 'national', 'start_date' => '2026-01-26', 'end_date' => '2026-01-26', 'description' => 'National holiday for Republic Day.'],
            ['title' => 'Holi Break', 'holiday_type' => 'festival', 'start_date' => '2026-03-03', 'end_date' => '2026-03-04', 'description' => 'Festival of colours observance.'],
            ['title' => 'Ambedkar Jayanti', 'holiday_type' => 'national', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14', 'description' => 'Dr. B. R. Ambedkar Jayanti.'],
            ['title' => 'Eid-ul-Fitr', 'holiday_type' => 'festival', 'start_date' => '2026-03-21', 'end_date' => '2026-03-21', 'description' => 'Festival holiday for Eid-ul-Fitr.'],
            ['title' => 'Independence Day', 'holiday_type' => 'national', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'description' => 'National holiday for Independence Day.'],
            ['title' => 'Janmashtami', 'holiday_type' => 'festival', 'start_date' => '2026-09-05', 'end_date' => '2026-09-05', 'description' => 'Festival holiday for Janmashtami.'],
            ['title' => 'Gandhi Jayanti', 'holiday_type' => 'national', 'start_date' => '2026-10-02', 'end_date' => '2026-10-02', 'description' => 'National holiday for Gandhi Jayanti.'],
            ['title' => 'Diwali Vacation', 'holiday_type' => 'festival', 'start_date' => '2026-11-08', 'end_date' => '2026-11-10', 'description' => 'Diwali festival break.'],
            ['title' => 'Christmas Day', 'holiday_type' => 'festival', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'description' => 'Christmas holiday.'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['title' => $holiday['title']],
                array_merge($holiday, [
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ])
            );
        }
    }

    private function seedLeaves(Collection $staff, Collection $students): void
    {
        foreach ($staff as $index => $staffMember) {
            $status = ['approved', 'pending', 'rejected'][($index + 1) % 3];
            LeaveRequest::updateOrCreate(
                [
                    'requester_type' => 'staff',
                    'staff_id' => $staffMember->id,
                    'start_date' => now()->subDays(30 - $index)->toDateString(),
                ],
                [
                    'student_id' => null,
                    'leave_type' => ['casual', 'medical', 'earned'][$index % 3],
                    'end_date' => now()->subDays(29 - $index)->toDateString(),
                    'reason' => 'Auto-generated '.$status.' leave request for demo visibility.',
                    'status' => $status,
                    'approved_by' => $status === 'approved' ? $this->superAdmin->id : null,
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );
        }

        foreach ($students->take(12) as $index => $student) {
            LeaveRequest::updateOrCreate(
                [
                    'requester_type' => 'student',
                    'student_id' => $student->id,
                    'start_date' => now()->subDays(14 - $index)->toDateString(),
                ],
                [
                    'staff_id' => null,
                    'leave_type' => ['medical', 'casual'][$index % 2],
                    'end_date' => now()->subDays(14 - $index)->toDateString(),
                    'reason' => 'Parent submitted student leave request for demo data.',
                    'status' => $index % 3 === 0 ? 'approved' : 'pending',
                    'approved_by' => $index % 3 === 0 ? $this->superAdmin->id : null,
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ]
            );
        }
    }

    private function seedAnnouncements(): void
    {
        $notifications = [
            ['title' => 'Welcome to Meerahr Noida Demo Campus', 'message' => 'All modules now include demo-ready data for staff, students, attendance, exams, and biometrics.', 'audience' => 'all', 'publish_date' => now()->toDateString(), 'status' => 'published'],
            ['title' => 'Biometric Attendance Live', 'message' => 'Student and staff biometric devices are active for Main Gate, Academic Block, and Staff Room.', 'audience' => 'admin', 'publish_date' => now()->subDay()->toDateString(), 'status' => 'published'],
            ['title' => 'Exam Sets Available', 'message' => 'Set A to E question banks have been prepared for classes 9th to 12th.', 'audience' => 'teacher', 'publish_date' => now()->subDays(2)->toDateString(), 'status' => 'published'],
        ];

        foreach ($notifications as $notification) {
            SchoolNotification::updateOrCreate(
                ['title' => $notification['title']],
                array_merge($notification, [
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ])
            );
        }

        $events = [
            ['title' => 'Parent Orientation', 'event_type' => 'meeting', 'start_date' => now()->addDays(5)->setTime(10, 0), 'end_date' => now()->addDays(5)->setTime(12, 0), 'location' => 'Auditorium', 'description' => 'Orientation meeting for parents across classes 9th to 12th.'],
            ['title' => 'Science Practical Week', 'event_type' => 'event', 'start_date' => now()->addDays(12)->setTime(9, 0), 'end_date' => now()->addDays(16)->setTime(15, 0), 'location' => 'Science Labs', 'description' => 'Practical assessments for Physics and Chemistry.'],
            ['title' => 'Unit Test Window', 'event_type' => 'exam', 'start_date' => now()->addDays(20)->setTime(8, 0), 'end_date' => now()->addDays(24)->setTime(14, 0), 'location' => 'Noida Campus', 'description' => 'Scheduled demo exam window for seeded assessments.'],
        ];

        foreach ($events as $event) {
            CalendarEvent::updateOrCreate(
                ['title' => $event['title']],
                array_merge($event, [
                    'created_by' => $this->superAdmin->id,
                    'updated_by' => $this->superAdmin->id,
                ])
            );
        }
    }

    private function storeAvatar(string $folder, string $code, string $name, array $palette): string
    {
        $path = 'avatars/'.$folder.'/'.strtolower($code).'.svg';
        Storage::disk('public')->put($path, $this->avatarSvg($name, $palette[0], $palette[1]));

        return $path;
    }

    private function avatarSvg(string $name, string $background, string $foreground): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? 'M', 0, 1).substr($parts[1] ?? '', 0, 1));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="360" viewBox="0 0 320 360" role="img" aria-label="{$name}">
  <rect width="320" height="360" rx="28" fill="{$background}" />
  <circle cx="160" cy="132" r="70" fill="{$foreground}" fill-opacity="0.18" />
  <rect x="52" y="230" width="216" height="82" rx="20" fill="{$foreground}" fill-opacity="0.14" />
  <text x="160" y="156" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="64" font-weight="700" fill="{$foreground}">{$initials}</text>
  <text x="160" y="286" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="18" fill="{$foreground}">Meerahr Noida Demo</text>
</svg>
SVG;
    }

    private function dummyPdfContent(string $title, string $setLabel): string
    {
        $content = "{$title} - {$setLabel} - Demo Exam Paper";
        $length = strlen($content) + 55;

        return "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length {$length}>>stream\n"
            ."BT /F1 18 Tf 60 740 Td ({$content}) Tj ET\n"
            ."BT /F1 12 Tf 60 710 Td (Generated by RichDemoSchoolSeeder) Tj ET\n"
            ."endstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000010 00000 n \n0000000061 00000 n \n0000000118 00000 n \n0000000241 00000 n \n0000000370 00000 n \n"
            ."trailer<</Size 6/Root 1 0 R>>\nstartxref\n451\n%%EOF";
    }

    private function gradeFromMarks(float $marks): string
    {
        return match (true) {
            $marks >= 91 => 'A1',
            $marks >= 81 => 'A2',
            $marks >= 71 => 'B1',
            $marks >= 61 => 'B2',
            $marks >= 51 => 'C1',
            $marks >= 41 => 'C2',
            $marks >= 33 => 'D',
            default => 'E',
        };
    }

    private function palette(int $seed): array
    {
        $palettes = [
            ['#e9f5ff', '#0f4c81'],
            ['#fff4e5', '#8a4b08'],
            ['#eefbf3', '#146c43'],
            ['#fff1f2', '#b42318'],
            ['#f4f3ff', '#4338ca'],
            ['#f0fdfa', '#115e59'],
        ];

        return $palettes[$seed % count($palettes)];
    }
}
