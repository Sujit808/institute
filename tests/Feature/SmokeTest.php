<?php

namespace Tests\Feature;

use App\Models\AdmissionLead;
use App\Models\AcademicClass;
use App\Models\AuditLog;
use App\Models\LicenseConfig;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test suite — covers auth, access control, module pages and security headers.
 * Uses an in-memory SQLite database (configured in phpunit.xml).
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    // ─── User fixtures ───────────────────────────────────────────────────────────

    private function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'must_change_password' => false,
            'active' => true,
        ]);
    }

    private function makeTeacher(): User
    {
        return User::factory()->create([
            'role' => 'teacher',
            'must_change_password' => false,
            'active' => true,
        ]);
    }

    private function makeUser(array $attributes = []): User
    {
        /** @var User $user */
        $user = User::factory()->create($attributes);

        return $user;
    }

    /**
     * Creates a Student DB record AND a linked User with role=student.
     */
    private function makeStudentWithUser(): User
    {
        $class = AcademicClass::create([
            'name' => 'Class X',
            'code' => 'CX',
            'status' => 'active',
        ]);

        $section = Section::create([
            'academic_class_id' => $class->id,
            'name' => 'A',
            'code' => 'CXA',
        ]);

        $student = Student::create([
            'academic_class_id' => $class->id,
            'section_id' => $section->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'admission_no' => 'ADM-TEST-001',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return User::factory()->create([
            'role' => 'student',
            'student_id' => $student->id,
            'must_change_password' => false,
            'active' => true,
        ]);
    }

    // ─── 1. Auth ─────────────────────────────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_unauthenticated_user_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_unauthenticated_user_redirected_to_login_from_students_module(): void
    {
        $this->get('/students')->assertRedirect('/login');
    }

    public function test_super_admin_can_login_and_is_redirected(): void
    {
        $user = $this->makeSuperAdmin();

        // GET initialises the session and mints a CSRF token.
        $this->get('/login');

        $this->post('/login', [
            '_token' => session()->token(),
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_validation_error(): void
    {
        $this->get('/login'); // initialise session / CSRF token

        $this->from('/login')->post('/login', [
            '_token' => session()->token(),
            'login' => 'nobody@example.com',
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors('login');
    }

    public function test_logout_invalidates_session_and_redirects(): void
    {
        $user = $this->makeSuperAdmin();

        // Load any page to initialise the session, then grab the CSRF token.
        $this->actingAs($user)->get('/dashboard');

        $this->post('/logout', ['_token' => session()->token()])
            ->assertRedirect('/');

        $this->assertGuest();
    }

    // ─── 2. Dashboard ────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_main_dashboard(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_teacher_can_access_main_dashboard(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_student_role_is_redirected_from_main_dashboard_to_student_portal(): void
    {
        $user = $this->makeStudentWithUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('student.dashboard'));
    }

    // ─── 3. Must-change-password gate ────────────────────────────────────────────

    public function test_user_with_must_change_password_is_redirected(): void
    {
        $user = $this->makeUser([
            'role' => 'super_admin',
            'must_change_password' => true,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('password.change.edit'));
    }

    public function test_change_password_page_accessible_while_must_change_is_set(): void
    {
        $user = $this->makeUser([
            'role' => 'super_admin',
            'must_change_password' => true,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk();
    }

    // ─── 4. Super-admin-only routes ──────────────────────────────────────────────

    public function test_super_admin_can_access_license_settings(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/settings/license')
            ->assertOk();
    }

    public function test_teacher_is_forbidden_from_license_settings(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/settings/license')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_institute_settings(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/settings/institute')
            ->assertOk();
    }

    public function test_super_admin_can_access_billing_settings(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/settings/billing')
            ->assertOk();
    }

    public function test_teacher_is_forbidden_from_institute_settings(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/settings/institute')
            ->assertForbidden();
    }

    public function test_teacher_is_forbidden_from_billing_settings(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/settings/billing')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_password_hash_checker(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/settings/password/hash-check')
            ->assertOk();
    }

    public function test_teacher_is_forbidden_from_password_hash_checker(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/settings/password/hash-check')
            ->assertForbidden();
    }

    // ─── 5. Main module index pages (super_admin) ────────────────────────────────

    public function test_students_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/students')
            ->assertOk();
    }

    public function test_admission_leads_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/admission-leads')
            ->assertOk();
    }

    public function test_admission_leads_kanban_page_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/admission-leads/kanban')
            ->assertOk();
    }

    public function test_admission_lead_stage_can_be_updated_from_kanban_endpoint(): void
    {
        $lead = AdmissionLead::create([
            'student_name' => 'Demo Prospect',
            'guardian_name' => 'Guardian',
            'phone' => '9999999999',
            'source' => 'walk_in',
            'stage' => 'new',
            'status' => 'active',
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->patchJson('/admission-leads/'.$lead->id.'/stage', [
                'stage' => 'contacted',
                'score' => 75,
            ])
            ->assertOk()
            ->assertJsonPath('lead.stage', 'contacted')
            ->assertJsonPath('lead.score', 75);

        $lead->refresh();
        $this->assertSame('contacted', $lead->stage);
        $this->assertSame(75, $lead->score);
        $this->assertNotNull($lead->last_contacted_at);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'admission-leads',
            'action' => 'update',
            'auditable_id' => $lead->id,
        ]);
    }

    public function test_admission_lead_stage_auto_calculates_score_when_not_provided(): void
    {
        $lead = AdmissionLead::create([
            'student_name' => 'Auto Score Prospect',
            'guardian_name' => 'Guardian',
            'phone' => '9888888888',
            'email' => 'autoscore@example.com',
            'source' => 'website',
            'stage' => 'new',
            'status' => 'active',
        ]);

        $nextFollowUpAt = now()->addDay()->toDateTimeString();

        $this->actingAs($this->makeSuperAdmin())
            ->patchJson('/admission-leads/'.$lead->id.'/stage', [
                'stage' => 'contacted',
                'next_follow_up_at' => $nextFollowUpAt,
            ])
            ->assertOk()
            ->assertJsonPath('lead.stage', 'contacted')
            ->assertJsonPath('lead.score', 65);

        $lead->refresh();
        $this->assertSame('contacted', $lead->stage);
        $this->assertSame(65, $lead->score);
        $this->assertNotNull($lead->last_contacted_at);
    }

    public function test_admission_lead_can_be_converted_to_student_from_kanban_endpoint(): void
    {
        $admin = $this->makeSuperAdmin();

        $class = AcademicClass::create([
            'name' => 'Class IX',
            'code' => 'C9',
            'status' => 'active',
        ]);

        $section = Section::create([
            'academic_class_id' => $class->id,
            'name' => 'B',
            'code' => 'C9B',
        ]);

        $lead = AdmissionLead::create([
            'student_name' => 'Aman Sharma',
            'guardian_name' => 'Ramesh Sharma',
            'phone' => '9000000002',
            'email' => 'aman@example.com',
            'source' => 'walk_in',
            'stage' => 'follow_up',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/admission-leads/'.$lead->id.'/convert', [
                'academic_class_id' => $class->id,
                'section_id' => $section->id,
                'gender' => 'male',
                'conversion_reason' => 'Parent completed fee + docs.',
            ])
            ->assertOk();

        $studentId = (int) $response->json('student.id');
        $this->assertGreaterThan(0, $studentId);

        $lead->refresh();
        $this->assertSame('converted', $lead->stage);
        $this->assertSame($studentId, (int) $lead->converted_student_id);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame('Parent completed fee + docs.', $lead->conversion_reason);
        $this->assertSame(65, $lead->score);

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'first_name' => 'Aman',
            'last_name' => 'Sharma',
        ]);

        $this->assertDatabaseHas('users', [
            'student_id' => $studentId,
            'role' => 'student',
        ]);

        $this->assertTrue(AuditLog::query()
            ->where('module', 'admission-leads')
            ->where('action', 'convert')
            ->where('auditable_id', $lead->id)
            ->exists());
    }

    public function test_admission_lead_conversion_blocks_on_possible_duplicate_without_force_flag(): void
    {
        $admin = $this->makeSuperAdmin();

        $class = AcademicClass::create([
            'name' => 'Class X',
            'code' => 'CX2',
            'status' => 'active',
        ]);

        $section = Section::create([
            'academic_class_id' => $class->id,
            'name' => 'C',
            'code' => 'CX2C',
        ]);

        Student::create([
            'academic_class_id' => $class->id,
            'section_id' => $section->id,
            'first_name' => 'Ravi',
            'last_name' => 'Kumar',
            'admission_no' => 'ADM-CX2-001',
            'phone' => '9111111111',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $lead = AdmissionLead::create([
            'student_name' => 'Ravi Kumar',
            'phone' => '9111111111',
            'source' => 'walk_in',
            'stage' => 'follow_up',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/admission-leads/'.$lead->id.'/convert', [
                'academic_class_id' => $class->id,
                'section_id' => $section->id,
                'gender' => 'male',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Potential duplicate students found. Review before converting.')
            ->assertJsonPath('strict_mode', true)
            ->assertJsonPath('duplicates.0.confidence_label', 'high');

        $lead->refresh();
        $this->assertNull($lead->converted_student_id);
        $this->assertSame('follow_up', $lead->stage);
    }

    public function test_admission_lead_conversion_allows_duplicate_when_strict_mode_disabled(): void
    {
        $admin = $this->makeSuperAdmin();

        LicenseConfig::query()->create([
            'license_key' => 'DUP-SOFT-001',
            'plan_name' => 'Starter',
            'is_active' => true,
            'enabled_modules' => ['admission-leads'],
            'approval_settings' => [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
                'admission_duplicate_strict' => false,
            ],
            'role_limits' => [
                'admin' => 1,
                'hr' => 1,
                'teacher' => 10,
            ],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $class = AcademicClass::create([
            'name' => 'Class XI',
            'code' => 'CXI',
            'status' => 'active',
        ]);

        $section = Section::create([
            'academic_class_id' => $class->id,
            'name' => 'A',
            'code' => 'CXIA',
        ]);

        Student::create([
            'academic_class_id' => $class->id,
            'section_id' => $section->id,
            'first_name' => 'Neha',
            'last_name' => 'Singh',
            'admission_no' => 'ADM-CXI-001',
            'phone' => '9222222222',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $lead = AdmissionLead::create([
            'student_name' => 'Neha Singh',
            'phone' => '9222222222',
            'source' => 'walk_in',
            'stage' => 'follow_up',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/admission-leads/'.$lead->id.'/convert', [
                'academic_class_id' => $class->id,
                'section_id' => $section->id,
                'gender' => 'female',
            ])
            ->assertOk();

        $lead->refresh();
        $this->assertNotNull($lead->converted_student_id);
        $this->assertSame('converted', $lead->stage);
    }

    public function test_admission_lead_can_link_to_existing_student_during_conversion(): void
    {
        $admin = $this->makeSuperAdmin();

        $class = AcademicClass::create([
            'name' => 'Class XII',
            'code' => 'CXII',
            'status' => 'active',
        ]);

        $section = Section::create([
            'academic_class_id' => $class->id,
            'name' => 'A',
            'code' => 'CXIIA',
        ]);

        $existingStudent = Student::create([
            'academic_class_id' => $class->id,
            'section_id' => $section->id,
            'first_name' => 'Anaya',
            'last_name' => 'Verma',
            'admission_no' => 'ADM-CXII-001',
            'phone' => '9333333333',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $lead = AdmissionLead::create([
            'student_name' => 'Anaya Verma',
            'phone' => '9333333333',
            'source' => 'walk_in',
            'stage' => 'follow_up',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/admission-leads/'.$lead->id.'/convert', [
                'existing_student_id' => $existingStudent->id,
                'gender' => 'female',
                'conversion_reason' => 'Duplicate lead linked to existing record.',
            ])
            ->assertOk()
            ->assertJsonPath('student.id', $existingStudent->id);

        $lead->refresh();
        $this->assertSame('converted', $lead->stage);
        $this->assertSame($existingStudent->id, (int) $lead->converted_student_id);
        $this->assertSame('Duplicate lead linked to existing record.', $lead->conversion_reason);

        $this->assertTrue(AuditLog::query()
            ->where('module', 'admission-leads')
            ->where('action', 'convert-link')
            ->where('auditable_id', $lead->id)
            ->exists());
    }

    public function test_admission_lead_stage_update_respects_server_side_wip_limit(): void
    {
        $admin = $this->makeSuperAdmin();

        LicenseConfig::query()->create([
            'license_key' => 'WIP-LIMIT-001',
            'plan_name' => 'Starter',
            'is_active' => true,
            'enabled_modules' => ['admission-leads'],
            'approval_settings' => [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
                'admission_wip_limits' => [
                    'new' => 5,
                    'contacted' => 1,
                    'counselling_scheduled' => 5,
                    'counselling_done' => 5,
                    'follow_up' => 5,
                    'converted' => 100,
                    'lost' => 100,
                ],
            ],
            'role_limits' => [
                'admin' => 1,
                'hr' => 1,
                'teacher' => 20,
            ],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        AdmissionLead::create([
            'student_name' => 'Already Contacted',
            'phone' => '9000000000',
            'source' => 'walk_in',
            'stage' => 'contacted',
            'status' => 'active',
        ]);

        $lead = AdmissionLead::create([
            'student_name' => 'Move Attempt',
            'phone' => '9000000001',
            'source' => 'walk_in',
            'stage' => 'new',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patchJson('/admission-leads/'.$lead->id.'/stage', [
                'stage' => 'contacted',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('stage');

        $lead->refresh();
        $this->assertSame('new', $lead->stage);
    }

    public function test_staff_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/staff')
            ->assertOk();
    }

    public function test_fees_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/fees')
            ->assertOk();
    }

    public function test_results_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/results')
            ->assertOk();
    }

    public function test_attendance_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/attendance')
            ->assertOk();
    }

    public function test_classes_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/classes')
            ->assertOk();
    }

    public function test_sections_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/sections')
            ->assertOk();
    }

    public function test_subjects_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/subjects')
            ->assertOk();
    }

    public function test_exams_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/exams')
            ->assertOk();
    }

    public function test_notifications_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/notifications')
            ->assertOk();
    }

    public function test_calendar_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/calendar')
            ->assertOk();
    }

    public function test_holidays_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/holidays')
            ->assertOk();
    }

    public function test_leaves_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/leaves')
            ->assertOk();
    }

    public function test_timetable_module_index_loads(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/timetable')
            ->assertOk();
    }

    // ─── 6. Role-based module access ─────────────────────────────────────────────

    public function test_teacher_can_access_students_module(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/students')
            ->assertOk();
    }

    public function test_teacher_is_forbidden_from_staff_module(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/staff')
            ->assertForbidden();
    }

    // ─── 7. Student portal ───────────────────────────────────────────────────────

    public function test_unauthenticated_user_redirected_from_student_portal(): void
    {
        $this->get('/student/dashboard')->assertRedirect('/login');
    }

    public function test_non_student_is_forbidden_from_student_portal(): void
    {
        $this->actingAs($this->makeTeacher())
            ->get('/student/dashboard')
            ->assertForbidden();
    }

    public function test_student_can_access_student_dashboard(): void
    {
        $user = $this->makeStudentWithUser();

        $this->actingAs($user)
            ->get('/student/dashboard')
            ->assertOk();
    }

    public function test_student_can_access_student_profile(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/profile')
            ->assertOk();
    }

    public function test_student_can_access_student_attendance(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/attendance')
            ->assertOk();
    }

    public function test_student_can_access_student_fees(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/fees')
            ->assertOk();
    }

    public function test_student_can_access_student_results(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/results')
            ->assertOk();
    }

    public function test_student_can_access_student_exams_list(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/exams')
            ->assertOk();
    }

    public function test_student_can_access_study_materials(): void
    {
        $this->actingAs($this->makeStudentWithUser())
            ->get('/student/books')
            ->assertOk();
    }

    public function test_student_without_linked_record_is_forbidden(): void
    {
        $orphan = $this->makeUser([
            'role' => 'student',
            'student_id' => null,
            'active' => true,
        ]);

        $this->actingAs($orphan)
            ->get('/student/dashboard')
            ->assertForbidden();
    }

    // ─── 8. iCards & Quotations ──────────────────────────────────────────────────

    public function test_icards_page_loads_for_super_admin(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/icards')
            ->assertOk();
    }

    public function test_quotations_page_loads_for_super_admin(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/quotations')
            ->assertOk();
    }

    // ─── 9. Security headers ─────────────────────────────────────────────────────

    public function test_security_headers_present_on_guest_page(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_present_on_authenticated_page(): void
    {
        $response = $this->actingAs($this->makeSuperAdmin())->get('/dashboard');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    // ─── 10. Exam builder ────────────────────────────────────────────────────────

    public function test_exam_builder_index_loads_for_super_admin(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/exam-builder')
            ->assertOk();
    }

    public function test_exam_attempts_review_loads_for_super_admin(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/exam-attempts/review')
            ->assertOk();
    }
}
