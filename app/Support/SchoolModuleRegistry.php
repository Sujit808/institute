<?php

namespace App\Support;

use App\Models\AcademicClass;
use App\Models\AdmissionLead;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\CalendarEvent;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\Fee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Result;
use App\Models\SchoolNotification;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use InvalidArgumentException;

class SchoolModuleRegistry
{
    public static function all(): array
    {
        return [
            'payroll' => [
                'title' => 'Payroll',
                'singular' => 'Payroll',
                'model' => \App\Models\PayrollSalary::class,
                'view' => 'payroll.staff-salary',
                'permission' => 'payroll',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'employee_id', 'label' => 'Employee ID'],
                    ['key' => 'full_name', 'label' => 'Name'],
                    ['key' => 'designation', 'label' => 'Designation'],
                    ['key' => 'salary_month', 'label' => 'Month'],
                    ['key' => 'net_salary', 'label' => 'Net Salary'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'employee_id', 'label' => 'Employee ID', 'type' => 'text', 'required' => true],
                    ['name' => 'salary_month', 'label' => 'Salary Month', 'type' => 'month', 'required' => true],
                    ['name' => 'net_salary', 'label' => 'Net Salary', 'type' => 'number', 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'paid' => 'Paid'], 'required' => true],
                ],
            ],
            'students' => [
                'title' => 'Students',
                'singular' => 'Student',
                'model' => Student::class,
                'view' => 'students.index',
                'permission' => 'students',
                'teacher_access' => true,
                'file_fields' => ['photo', 'aadhar_file', 'documents'],
                'eager_load' => ['academicClass', 'section'],
                'table_columns' => [
                    ['key' => 'admission_no', 'label' => 'Admission No'],
                    ['key' => 'roll_no', 'label' => 'Roll No'],
                    ['key' => 'full_name', 'label' => 'Student Name'],
                    ['key' => 'college_name', 'label' => 'Previous School/College'],
                    ['key' => 'current_college_name', 'label' => 'Current School/College'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'section.name', 'label' => 'Section'],
                    ['key' => 'guardian_phone', 'label' => 'Guardian Phone'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
                    ['name' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => true],
                    ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], 'required' => true],
                    ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'guardian_name', 'label' => 'Guardian Name', 'type' => 'text'],
                    ['name' => 'guardian_phone', 'label' => 'Guardian Phone', 'type' => 'text'],
                    ['name' => 'college_name', 'label' => 'Previous School/College Name', 'type' => 'text', 'placeholder' => 'e.g. XYZ Public School', 'help' => 'Student pehle kis school/college me tha. Example: XYZ Public School.'],
                    ['name' => 'current_college_name', 'label' => 'Current School/College Name', 'type' => 'text', 'placeholder' => 'e.g. Meerah Junior College', 'help' => 'Agar student currently kisi college/school me enrolled hai to uska naam.'],
                    ['name' => 'admission_date', 'label' => 'Admission Date', 'type' => 'date'],
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes', 'required' => true, 'teacher_restricted' => true],
                    ['name' => 'section_id', 'label' => 'Section', 'type' => 'select', 'lookup' => 'sections', 'required' => true, 'teacher_restricted' => true],
                    ['name' => 'blood_group', 'label' => 'Blood Group', 'type' => 'select', 'options' => ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-']],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                    ['name' => 'aadhar_number', 'label' => 'Aadhar No', 'type' => 'text'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'alumni' => 'Alumni'], 'required' => true],
                    ['name' => 'photo', 'label' => 'Photo', 'type' => 'file'],
                    ['name' => 'aadhar_file', 'label' => 'Aadhar File', 'type' => 'file'],
                    ['name' => 'documents', 'label' => 'Documents', 'type' => 'file', 'multiple' => true],
                ],
            ],
            'admission-leads' => [
                'title' => 'Admission CRM',
                'singular' => 'Admission Lead',
                'model' => AdmissionLead::class,
                'view' => 'modules.page',
                'permission' => 'admission-leads',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'student_name', 'label' => 'Student Name'],
                    ['key' => 'guardian_name', 'label' => 'Guardian'],
                    ['key' => 'phone', 'label' => 'Phone'],
                    ['key' => 'source', 'label' => 'Source', 'type' => 'badge'],
                    ['key' => 'stage', 'label' => 'Stage', 'type' => 'badge'],
                    ['key' => 'next_follow_up_at', 'label' => 'Next Follow Up'],
                ],
                'fields' => [
                    ['name' => 'student_name', 'label' => 'Student Name', 'type' => 'text', 'required' => true],
                    ['name' => 'guardian_name', 'label' => 'Guardian Name', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'academic_class_id', 'label' => 'Interested Class', 'type' => 'select', 'lookup' => 'academic_classes'],
                    ['name' => 'source', 'label' => 'Lead Source', 'type' => 'select', 'options' => ['walk_in' => 'Walk In', 'website' => 'Website', 'meta_ads' => 'Meta Ads', 'google_ads' => 'Google Ads', 'reference' => 'Reference', 'campaign' => 'Campaign', 'other' => 'Other'], 'required' => true],
                    ['name' => 'stage', 'label' => 'Pipeline Stage', 'type' => 'select', 'options' => ['new' => 'New', 'contacted' => 'Contacted', 'counselling_scheduled' => 'Counselling Scheduled', 'counselling_done' => 'Counselling Done', 'follow_up' => 'Follow Up', 'converted' => 'Converted', 'lost' => 'Lost'], 'required' => true],
                    ['name' => 'score', 'label' => 'Lead Score (0-100)', 'type' => 'number'],
                    ['name' => 'assigned_to_staff_id', 'label' => 'Assigned Counselor', 'type' => 'select', 'lookup' => 'staff'],
                    ['name' => 'last_contacted_at', 'label' => 'Last Contacted At', 'type' => 'datetime-local'],
                    ['name' => 'next_follow_up_at', 'label' => 'Next Follow Up At', 'type' => 'datetime-local'],
                    ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                ],
            ],
            'staff' => [
                'title' => 'Staff / Teachers',
                'singular' => 'Staff Member',
                'model' => Staff::class,
                'view' => 'staff.index',
                'permission' => 'staff',
                'teacher_access' => false,
                'file_fields' => ['photo', 'aadhar_file', 'pancard_file', 'qualification_files'],
                'eager_load' => ['linkedUser'],
                'table_columns' => [
                    ['key' => 'employee_id', 'label' => 'Employee ID'],
                    ['key' => 'full_name', 'label' => 'Name'],
                    ['key' => 'designation', 'label' => 'Designation'],
                    ['key' => 'role_type', 'label' => 'Role', 'type' => 'badge'],
                    ['key' => 'leave_balance_days', 'label' => 'Leave Balance'],
                    ['key' => 'phone', 'label' => 'Phone'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'employee_id', 'label' => 'Employee ID', 'type' => 'text', 'required' => true],
                    ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
                    ['name' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'required' => true],
                    ['name' => 'role_type', 'label' => 'Role Type', 'type' => 'select', 'options' => ['admin' => 'Admin', 'hr' => 'HR', 'teacher' => 'Teacher', 'staff' => 'Staff'], 'required' => true],
                    ['name' => 'joining_date', 'label' => 'Joining Date', 'type' => 'date'],
                    ['name' => 'qualification', 'label' => 'Qualification', 'type' => 'textarea'],
                    ['name' => 'experience_years', 'label' => 'Experience Years', 'type' => 'number'],
                    ['name' => 'leave_balance_days', 'label' => 'Leave Balance (Days)', 'type' => 'number'],
                    ['name' => 'salary', 'label' => 'Salary', 'type' => 'number'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                    ['name' => 'aadhar_number', 'label' => 'Aadhar No', 'type' => 'text'],
                    ['name' => 'pan_number', 'label' => 'PAN No', 'type' => 'text'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                    ['name' => 'permissions', 'label' => 'Permissions', 'type' => 'checkboxes', 'lookup' => 'permissions'],
                    ['name' => 'photo', 'label' => 'Photo', 'type' => 'file'],
                    ['name' => 'aadhar_file', 'label' => 'Aadhar File', 'type' => 'file'],
                    ['name' => 'pancard_file', 'label' => 'PAN File', 'type' => 'file'],
                    ['name' => 'qualification_files', 'label' => 'Qualification Files', 'type' => 'file', 'multiple' => true],
                ],
            ],
            'classes' => [
                'title' => 'Classes',
                'singular' => 'Class',
                'model' => AcademicClass::class,
                'view' => 'classes.index',
                'permission' => 'classes',
                'teacher_access' => false,
                'eager_load' => ['subjects'],
                'table_columns' => [
                    ['key' => 'code', 'label' => 'Code'],
                    ['key' => 'name', 'label' => 'Class Name'],
                    ['key' => 'capacity', 'label' => 'Capacity'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Class Name', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Class Code', 'type' => 'text', 'required' => true],
                    ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'subject_ids', 'label' => 'Subjects', 'type' => 'checkboxes', 'lookup' => 'subjects'],
                    ['name' => 'new_subject_names', 'label' => 'Add New Subjects', 'type' => 'textarea', 'help' => 'Optional: comma or line separated subject names (e.g. Physics, Chemistry).'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                ],
            ],
            'sections' => [
                'title' => 'Sections',
                'singular' => 'Section',
                'model' => Section::class,
                'view' => 'sections.index',
                'permission' => 'sections',
                'teacher_access' => false,
                'eager_load' => ['academicClass', 'classTeacher'],
                'table_columns' => [
                    ['key' => 'code', 'label' => 'Code'],
                    ['key' => 'name', 'label' => 'Section'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'classTeacher.full_name', 'label' => 'Class Teacher'],
                    ['key' => 'room_no', 'label' => 'Room'],
                ],
                'fields' => [
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes', 'required' => true],
                    ['name' => 'name', 'label' => 'Section Name', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Section Code', 'type' => 'text', 'required' => true],
                    ['name' => 'room_no', 'label' => 'Room No', 'type' => 'text'],
                    ['name' => 'class_teacher_id', 'label' => 'Class Teacher', 'type' => 'select', 'lookup' => 'teachers'],
                ],
            ],
            'subjects' => [
                'title' => 'Subjects',
                'singular' => 'Subject',
                'model' => Subject::class,
                'view' => 'subjects.index',
                'permission' => 'subjects',
                'teacher_access' => false,
                'eager_load' => ['academicClass', 'staff'],
                'table_columns' => [
                    ['key' => 'code', 'label' => 'Code'],
                    ['key' => 'name', 'label' => 'Subject'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'staff.full_name', 'label' => 'Teacher'],
                    ['key' => 'type', 'label' => 'Type', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes'],
                    ['name' => 'name', 'label' => 'Subject Name', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Subject Code', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['theory' => 'Theory', 'practical' => 'Practical'], 'required' => true],
                    ['name' => 'staff_id', 'label' => 'Teacher', 'type' => 'select', 'lookup' => 'teachers'],
                    ['name' => 'max_marks', 'label' => 'Max Marks', 'type' => 'number'],
                ],
            ],
            'exams' => [
                'title' => 'Exams',
                'singular' => 'Exam',
                'model' => Exam::class,
                'view' => 'exams.index',
                'permission' => 'exams',
                'teacher_access' => false,
                'eager_load' => ['academicClass', 'papers'],
                'table_columns' => [
                    ['key' => 'name', 'label' => 'Exam'],
                    ['key' => 'exam_type', 'label' => 'Type', 'type' => 'badge'],
                    ['key' => 'question_sets', 'label' => 'Sets'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'start_date', 'label' => 'Start Date'],
                    ['key' => 'end_date', 'label' => 'End Date'],
                ],
                'fields' => [
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes', 'required' => true],
                    ['name' => 'name', 'label' => 'Exam Name', 'type' => 'text', 'required' => true],
                    ['name' => 'exam_type', 'label' => 'Exam Type', 'type' => 'select', 'options' => ['unit' => 'Unit Test', 'midterm' => 'Mid Term', 'final' => 'Final Exam'], 'required' => true],
                    ['name' => 'question_sets', 'label' => 'Question Sets', 'type' => 'checkboxes', 'options' => ['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C', 'D' => 'Set D', 'E' => 'Set E']],
                    ['name' => 'duration_minutes', 'label' => 'Duration (Minutes)', 'type' => 'number'],
                    ['name' => 'negative_mark_per_wrong', 'label' => 'Negative Mark/Wrong', 'type' => 'number', 'help' => 'Use 0 for no negative marking. Example: 0.25'],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true],
                    ['name' => 'total_marks', 'label' => 'Total Marks', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['scheduled' => 'Scheduled', 'completed' => 'Completed'], 'required' => true],
                ],
            ],
            'exam-questions' => [
                'title' => 'Exam Questions',
                'singular' => 'Exam Question',
                'model' => ExamQuestion::class,
                'view' => 'exam-questions.index',
                'permission' => 'exam-questions',
                'teacher_access' => false,
                'eager_load' => ['exam', 'subject'],
                'table_columns' => [
                    ['key' => 'exam.name', 'label' => 'Exam'],
                    ['key' => 'set_code', 'label' => 'Set'],
                    ['key' => 'question_text', 'label' => 'Question'],
                    ['key' => 'marks', 'label' => 'Marks'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'lookup' => 'exams', 'required' => true],
                    ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'lookup' => 'subjects'],
                    ['name' => 'set_code', 'label' => 'Set Code', 'type' => 'select', 'options' => ['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C', 'D' => 'Set D', 'E' => 'Set E'], 'required' => true],
                    ['name' => 'question_text', 'label' => 'Question', 'type' => 'textarea', 'required' => true],
                    ['name' => 'option_a', 'label' => 'Option A', 'type' => 'text', 'required' => true],
                    ['name' => 'option_b', 'label' => 'Option B', 'type' => 'text', 'required' => true],
                    ['name' => 'option_c', 'label' => 'Option C', 'type' => 'text', 'required' => true],
                    ['name' => 'option_d', 'label' => 'Option D', 'type' => 'text', 'required' => true],
                    ['name' => 'correct_option', 'label' => 'Correct Option', 'type' => 'select', 'options' => ['A' => 'Option A', 'B' => 'Option B', 'C' => 'Option C', 'D' => 'Option D'], 'required' => true],
                    ['name' => 'marks', 'label' => 'Marks', 'type' => 'number', 'required' => true],
                    ['name' => 'question_order', 'label' => 'Order', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                ],
            ],
            'exam-papers' => [
                'title' => 'Exam Papers',
                'singular' => 'Exam Paper',
                'model' => ExamPaper::class,
                'view' => 'exam-papers.index',
                'permission' => 'exam-papers',
                'teacher_access' => false,
                'file_fields' => ['file_path'],
                'eager_load' => ['exam'],
                'table_columns' => [
                    ['key' => 'exam.name', 'label' => 'Exam'],
                    ['key' => 'set_code', 'label' => 'Set'],
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'lookup' => 'exams', 'required' => true],
                    ['name' => 'set_code', 'label' => 'Set Code', 'type' => 'select', 'options' => ['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C', 'D' => 'Set D', 'E' => 'Set E'], 'required' => true],
                    ['name' => 'title', 'label' => 'Paper Title', 'type' => 'text', 'required' => true],
                    ['name' => 'instructions', 'label' => 'Instructions', 'type' => 'textarea'],
                    ['name' => 'file_path', 'label' => 'Paper File', 'type' => 'file', 'required' => true, 'help' => 'Upload PDF/image of the paper. Existing file preview appears while editing.'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                ],
            ],
            'study-materials' => [
                'title' => 'Study Materials',
                'singular' => 'Study Material',
                'model' => StudyMaterial::class,
                'view' => 'study-materials.index',
                'permission' => 'study-materials',
                'teacher_access' => false,
                'file_fields' => ['file_path'],
                'eager_load' => ['academicClass', 'subject'],
                'table_columns' => [
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'subject.name', 'label' => 'Subject'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes'],
                    ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'lookup' => 'subjects'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'file_path', 'label' => 'Material File', 'type' => 'file', 'required' => true, 'help' => 'Upload books, notes, PPT, or PDFs. Existing files can be previewed during edit.'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                ],
            ],
            'results' => [
                'title' => 'Results',
                'singular' => 'Result',
                'model' => Result::class,
                'view' => 'results.index',
                'permission' => 'results',
                'teacher_access' => true,
                'eager_load' => ['student.academicClass', 'exam', 'subject'],
                'table_columns' => [
                    ['key' => 'student.full_name', 'label' => 'Student'],
                    ['key' => 'exam.name', 'label' => 'Exam'],
                    ['key' => 'subject.name', 'label' => 'Subject'],
                    ['key' => 'marks_obtained', 'label' => 'Marks'],
                    ['key' => 'grade', 'label' => 'Grade', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'lookup' => 'students', 'required' => true],
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'lookup' => 'exams', 'required' => true],
                    ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'lookup' => 'subjects', 'required' => true],
                    ['name' => 'marks_obtained', 'label' => 'Marks Obtained', 'type' => 'number', 'required' => true],
                    ['name' => 'grade', 'label' => 'Grade', 'type' => 'text'],
                    ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'attendance' => [
                'title' => 'Attendance',
                'singular' => 'Attendance',
                'model' => Attendance::class,
                'view' => 'attendance.index',
                'permission' => 'attendance',
                'teacher_access' => true,
                'eager_load' => ['student.academicClass', 'section', 'staffAttendance', 'markedBy'],
                'table_columns' => [
                    ['key' => 'attendance_date', 'label' => 'Date'],
                    ['key' => 'attendance_for', 'label' => 'For', 'type' => 'badge'],
                    ['key' => 'student.full_name', 'label' => 'Student'],
                    ['key' => 'staffAttendance.full_name', 'label' => 'Staff'],
                    ['key' => 'attendance_method', 'label' => 'Method', 'type' => 'badge'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                    ['key' => 'sync_status', 'label' => 'Sync', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'attendance_for', 'label' => 'Attendance For', 'type' => 'select', 'options' => ['student' => 'Student', 'staff' => 'Staff'], 'required' => true, 'help' => 'Student select karein ya staff attendance mark karein.'],
                    ['name' => 'attendance_method', 'label' => 'Capture Method', 'type' => 'select', 'options' => ['manual' => 'Manual', 'biometric_machine' => 'Biometric Machine', 'mobile_face' => 'Mobile Face', 'mobile_finger' => 'Mobile Finger'], 'required' => true],
                    ['name' => 'attendance_date', 'label' => 'Attendance Date', 'type' => 'date', 'required' => true],
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes'],
                    ['name' => 'section_id', 'label' => 'Section', 'type' => 'select', 'lookup' => 'sections'],
                    ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'lookup' => 'students'],
                    ['name' => 'staff_attendance_id', 'label' => 'Staff', 'type' => 'select', 'lookup' => 'staff'],
                    ['name' => 'marked_by_staff_id', 'label' => 'Marked By', 'type' => 'select', 'lookup' => 'teachers'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'], 'required' => true],
                    ['name' => 'sync_status', 'label' => 'Sync Status', 'type' => 'select', 'options' => ['synced' => 'Synced', 'pending' => 'Pending', 'failed' => 'Failed'], 'required' => true],
                    ['name' => 'captured_at', 'label' => 'Captured At', 'type' => 'datetime-local'],
                    ['name' => 'biometric_device_id', 'label' => 'Biometric Device ID', 'type' => 'text'],
                    ['name' => 'biometric_log_id', 'label' => 'Biometric Log ID', 'type' => 'text'],
                    ['name' => 'capture_payload', 'label' => 'Capture Payload (JSON)', 'type' => 'textarea'],
                    ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'fees' => [
                'title' => 'Fees & Payments',
                'singular' => 'Fee',
                'model' => Fee::class,
                'view' => 'fees.index',
                'permission' => 'fees',
                'teacher_access' => false,
                'eager_load' => ['student.academicClass', 'payments'],
                'table_columns' => [
                    ['key' => 'student.full_name', 'label' => 'Student'],
                    ['key' => 'fee_type', 'label' => 'Fee Type'],
                    ['key' => 'amount', 'label' => 'Amount'],
                    ['key' => 'paid_amount', 'label' => 'Paid'],
                    ['key' => 'due_date', 'label' => 'Due Date'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'lookup' => 'students', 'required' => true],
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes'],
                    ['name' => 'fee_type', 'label' => 'Fee Type', 'type' => 'select', 'options' => ['tuition' => 'Tuition', 'transport' => 'Transport', 'hostel' => 'Hostel', 'misc' => 'Misc'], 'required' => true],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true],
                    ['name' => 'paid_amount', 'label' => 'Paid Amount', 'type' => 'number'],
                    ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
                    ['name' => 'payment_mode', 'label' => 'Payment Mode', 'type' => 'select', 'options' => ['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank Transfer', 'card' => 'Card']],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'partial' => 'Partial', 'paid' => 'Paid', 'overdue' => 'Overdue'], 'required' => true],
                    ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'timetable' => [
                'title' => 'Timetable',
                'singular' => 'Timetable Entry',
                'model' => TimetableEntry::class,
                'view' => 'timetable.index',
                'permission' => 'timetable',
                'teacher_access' => true,
                'eager_load' => ['academicClass', 'section', 'subject', 'staff'],
                'table_columns' => [
                    ['key' => 'day_of_week', 'label' => 'Day'],
                    ['key' => 'academicClass.name', 'label' => 'Class'],
                    ['key' => 'section.name', 'label' => 'Section'],
                    ['key' => 'subject.name', 'label' => 'Subject'],
                    ['key' => 'time_slot', 'label' => 'Time'],
                ],
                'fields' => [
                    ['name' => 'academic_class_id', 'label' => 'Class', 'type' => 'select', 'lookup' => 'academic_classes', 'required' => true],
                    ['name' => 'section_id', 'label' => 'Section', 'type' => 'select', 'lookup' => 'sections', 'required' => true],
                    ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'lookup' => 'subjects', 'required' => true],
                    ['name' => 'staff_id', 'label' => 'Teacher', 'type' => 'select', 'lookup' => 'teachers'],
                    ['name' => 'day_of_week', 'label' => 'Day', 'type' => 'select', 'options' => ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'], 'required' => true],
                    ['name' => 'start_time', 'label' => 'Start Time', 'type' => 'time', 'required' => true],
                    ['name' => 'end_time', 'label' => 'End Time', 'type' => 'time', 'required' => true],
                    ['name' => 'room_no', 'label' => 'Room No', 'type' => 'text'],
                ],
            ],
            'notifications' => [
                'title' => 'Notifications',
                'singular' => 'Notification',
                'model' => SchoolNotification::class,
                'view' => 'notifications.index',
                'permission' => 'notifications',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'audience', 'label' => 'Audience', 'type' => 'badge'],
                    ['key' => 'publish_date', 'label' => 'Publish Date'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                    ['name' => 'audience', 'label' => 'Audience', 'type' => 'select', 'options' => ['all' => 'All', 'admin' => 'Admins', 'teacher' => 'Teachers', 'student' => 'Students'], 'required' => true],
                    ['name' => 'publish_date', 'label' => 'Publish Date', 'type' => 'date', 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published'], 'required' => true],
                ],
            ],
            'holidays' => [
                'title' => 'Holidays',
                'singular' => 'Holiday',
                'model' => Holiday::class,
                'view' => 'holidays.index',
                'permission' => 'holidays',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'title', 'label' => 'Holiday'],
                    ['key' => 'holiday_type', 'label' => 'Type', 'type' => 'badge'],
                    ['key' => 'start_date', 'label' => 'Start Date'],
                    ['key' => 'end_date', 'label' => 'End Date'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Holiday Title', 'type' => 'text', 'required' => true],
                    ['name' => 'holiday_type', 'label' => 'Holiday Type', 'type' => 'select', 'options' => ['national' => 'National', 'festival' => 'Festival', 'school' => 'School'], 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'leaves' => [
                'title' => 'Leaves',
                'singular' => 'Leave Request',
                'model' => LeaveRequest::class,
                'view' => 'leaves.index',
                'permission' => 'leaves',
                'teacher_access' => true,
                'eager_load' => ['staff', 'student'],
                'table_columns' => [
                    ['key' => 'applicant_name', 'label' => 'Applicant'],
                    ['key' => 'requester_type', 'label' => 'Type', 'type' => 'badge'],
                    ['key' => 'leave_type', 'label' => 'Leave Type'],
                    ['key' => 'start_date', 'label' => 'Start Date'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'requester_type', 'label' => 'Requester Type', 'type' => 'select', 'options' => ['staff' => 'Staff', 'student' => 'Student'], 'required' => true],
                    ['name' => 'staff_id', 'label' => 'Staff', 'type' => 'select', 'lookup' => 'staff'],
                    ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'lookup' => 'students'],
                    ['name' => 'leave_type', 'label' => 'Leave Type', 'type' => 'select', 'options' => ['casual' => 'Casual', 'medical' => 'Medical', 'earned' => 'Earned'], 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true],
                    ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], 'required' => true],
                ],
            ],
            'calendar' => [
                'title' => 'Calendar Events',
                'singular' => 'Calendar Event',
                'model' => CalendarEvent::class,
                'view' => 'calendar.index',
                'permission' => 'calendar',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'title', 'label' => 'Event'],
                    ['key' => 'event_type', 'label' => 'Type', 'type' => 'badge'],
                    ['key' => 'start_date', 'label' => 'Start Date'],
                    ['key' => 'end_date', 'label' => 'End Date'],
                    ['key' => 'location', 'label' => 'Location'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                    ['name' => 'event_type', 'label' => 'Event Type', 'type' => 'select', 'options' => ['meeting' => 'Meeting', 'event' => 'Event', 'exam' => 'Exam', 'holiday' => 'Holiday'], 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'end_date', 'label' => 'End Date', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'biometric-devices' => [
                'title' => 'Biometric Devices',
                'singular' => 'Device',
                'model' => BiometricDevice::class,
                'view' => 'biometric-devices.index',
                'permission' => 'biometric-devices',
                'teacher_access' => false,
                'table_columns' => [
                    ['key' => 'device_name', 'label' => 'Device Name'],
                    ['key' => 'device_code', 'label' => 'Device Code'],
                    ['key' => 'brand', 'label' => 'Brand'],
                    ['key' => 'ip_address', 'label' => 'IP Address'],
                    ['key' => 'location', 'label' => 'Location'],
                    ['key' => 'device_type', 'label' => 'Type', 'type' => 'badge'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'device_name', 'label' => 'Device Name', 'type' => 'text', 'required' => true, 'help' => 'e.g. Main Gate Fingerprint Machine'],
                    ['name' => 'device_code', 'label' => 'Device Code (unique)', 'type' => 'text', 'required' => true, 'help' => 'e.g. BIO-MG-01 — This exact code is sent in the punch API as biometric_device_id.'],
                    ['name' => 'brand', 'label' => 'Brand', 'type' => 'select', 'options' => ['ZKTeco' => 'ZKTeco', 'eSSL' => 'eSSL', 'BioMax' => 'BioMax', 'Secureye' => 'Secureye', 'Anviz' => 'Anviz', 'Realand' => 'Realand', 'Other' => 'Other']],
                    ['name' => 'model_no', 'label' => 'Model No.', 'type' => 'text', 'help' => 'e.g. ZK9600, MB10, SF600'],
                    ['name' => 'ip_address', 'label' => 'IP Address', 'type' => 'text', 'help' => 'Static IP assigned to machine on your LAN'],
                    ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'help' => 'Default: 4370 for ZKTeco; 8080 for eSSL'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'help' => 'e.g. Main Gate, Staff Room, Library Entry'],
                    ['name' => 'device_type', 'label' => 'Device Type', 'type' => 'select', 'options' => ['fingerprint' => 'Fingerprint', 'face' => 'Face Recognition', 'card' => 'RFID / Card', 'multi' => 'Multi-Modal (Finger + Face)'], 'required' => true],
                    ['name' => 'communication', 'label' => 'Integration Method', 'type' => 'select', 'options' => ['push_api' => 'Push to our API (recommended)', 'pull_sdk' => 'Pull via SDK / ZKLib', 'adms' => 'ADMS Cloud Push (ZKTeco)'], 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'maintenance' => 'Maintenance'], 'required' => true],
                    ['name' => 'notes', 'label' => 'Notes / Config', 'type' => 'textarea'],
                ],
            ],
            'biometric-enrollments' => [
                'title' => 'Biometric Enrollments',
                'singular' => 'Enrollment',
                'model' => BiometricEnrollment::class,
                'view' => 'biometric-enrollments.index',
                'permission' => 'biometric-devices',
                'teacher_access' => false,
                'eager_load' => ['device', 'student', 'staff'],
                'table_columns' => [
                    ['key' => 'device.device_name', 'label' => 'Device'],
                    ['key' => 'enrollment_for', 'label' => 'For', 'type' => 'badge'],
                    ['key' => 'punch_id', 'label' => 'Punch ID'],
                    ['key' => 'student.full_name', 'label' => 'Student'],
                    ['key' => 'staff.full_name', 'label' => 'Staff'],
                    ['key' => 'finger_index', 'label' => 'Finger'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [
                    ['name' => 'biometric_device_id', 'label' => 'Device', 'type' => 'select', 'lookup' => 'biometric_devices', 'required' => true],
                    ['name' => 'enrollment_for', 'label' => 'Enroll For', 'type' => 'select', 'options' => ['student' => 'Student', 'staff' => 'Staff'], 'required' => true],
                    ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'lookup' => 'students'],
                    ['name' => 'staff_id', 'label' => 'Staff Member', 'type' => 'select', 'lookup' => 'staff'],
                    ['name' => 'punch_id', 'label' => 'Punch ID (Machine ID)', 'type' => 'text', 'required' => true, 'help' => 'Exact numeric/text ID stored in machine memory for this person.'],
                    ['name' => 'finger_index', 'label' => 'Finger', 'type' => 'select', 'options' => ['' => 'Not specified', '0' => '0 - Right Thumb', '1' => '1 - Right Index', '2' => '2 - Right Middle', '3' => '3 - Right Ring', '4' => '4 - Right Pinky', '5' => '5 - Left Thumb', '6' => '6 - Left Index', '7' => '7 - Left Middle', '8' => '8 - Left Ring', '9' => '9 - Left Pinky']],
                    ['name' => 'enrolled_at', 'label' => 'Enrolled At', 'type' => 'datetime-local'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                ],
            ],
            'audit-logs' => [
                'title' => 'Audit Logs',
                'singular' => 'Audit Log',
                'model' => AuditLog::class,
                'view' => 'audit-logs.index',
                'permission' => 'audit-logs',
                'teacher_access' => false,
                'readonly' => true,
                'eager_load' => ['user'],
                'table_columns' => [
                    ['key' => 'created_at', 'label' => 'Date'],
                    ['key' => 'user.name', 'label' => 'User'],
                    ['key' => 'module', 'label' => 'Module'],
                    ['key' => 'action', 'label' => 'Action', 'type' => 'badge'],
                    ['key' => 'description', 'label' => 'Description'],
                    ['key' => 'ip_address', 'label' => 'IP Address'],
                ],
                'fields' => [],
            ],
        ];
    }

    public static function get(string $module): array
    {
        $modules = self::all();

        if (! isset($modules[$module])) {
            throw new InvalidArgumentException("Unknown module [{$module}].");
        }

        return $modules[$module];
    }

    public static function lookupPermissions(): array
    {
        return [
            'payroll' => 'Payroll',
            'students' => 'Students',
            'admission-leads' => 'Admission CRM',
            'staff' => 'Staff / Teachers',
            'classes' => 'Classes',
            'sections' => 'Sections',
            'subjects' => 'Subjects',
            'exams' => 'Exams',
            'exam-questions' => 'Exam Questions',
            'exam-papers' => 'Exam Papers',
            'results' => 'Results',
            'study-materials' => 'Study Materials',
            'attendance' => 'Attendance',
            'biometric-devices' => 'Biometric Devices & Enrollments',
            'fees' => 'Fees & Payments',
            'timetable' => 'Timetable',
            'notifications' => 'Notifications',
            'holidays' => 'Holidays',
            'leaves' => 'Leaves',
            'calendar' => 'Calendar',
            'icards' => 'iCards',
            'quotations' => 'Quotations',
            'audit-logs' => 'Audit Logs',
        ];
    }

    public static function permissionAliases(): array
    {
        return [
            'biometric-enrollments' => 'biometric-devices',
        ];
    }

    public static function normalizePermissionKey(string $permission): string
    {
        return self::permissionAliases()[$permission] ?? $permission;
    }

    public static function defaultPermissionsForRole(string $role): array
    {
        return match ($role) {
            'admin' => array_merge(['payroll'], array_keys(self::lookupPermissions())),
            'hr' => array_merge(['payroll'], [
                'students',
                'admission-leads',
                'attendance',
                'results',
                'staff',
                'leaves',
                'notifications',
                'calendar',
                'holidays',
                'icards',
            ]),
            'teacher' => [
                'students',
                'attendance',
                'results',
                'timetable',
                'leaves',
                'study-materials',
                'exam-papers',
                'icards',
            ],
            default => [],
        };
    }

    public static function roleMatrix(): array
    {
        $labels = self::lookupPermissions();

        return collect($labels)->map(function (string $label, string $permission): array {
            return [
                'permission' => $permission,
                'label' => $label,
                'super_admin' => true,
                'admin' => in_array($permission, self::defaultPermissionsForRole('admin'), true),
                'hr' => in_array($permission, self::defaultPermissionsForRole('hr'), true),
                'teacher' => in_array($permission, self::defaultPermissionsForRole('teacher'), true),
            ];
        })->values()->all();
    }

    public static function navigation(User $user): array
    {
        return collect(self::all())
            ->reject(fn (array $module, string $key) => $key === 'audit-logs' && ! $user->isSuperAdmin())
            ->filter(fn (array $module, string $key) => $user->canAccessModule($key))
            ->map(fn (array $module, string $key) => [
                'key' => $key,
                'title' => $module['title'],
                'route' => route($key.'.index'),
            ])
            ->values()
            ->all();
    }
}
