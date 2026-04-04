<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LicenseConfig;
use App\Models\MasterControlSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterControlFeatureTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_super_admin_can_generate_master_control_impact_preview(): void
    {
        $admin = $this->makeSuperAdmin();

        LicenseConfig::query()->create([
            'license_key' => 'ENT-KEY-001',
            'plan_name' => 'Enterprise',
            'is_active' => true,
            'enabled_modules' => ['students', 'exams'],
            'approval_settings' => [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
            ],
            'role_limits' => [
                'admin' => null,
                'hr' => null,
                'teacher' => null,
            ],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('license-settings.impact-preview'), [
            'plan_name' => 'Starter',
            'enabled_modules' => ['students'],
            'approval_settings' => [
                'student_calendar_mappings' => 1,
            ],
            'is_active' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('impact.plan_from', 'Enterprise')
            ->assertJsonPath('impact.plan_to', 'Starter')
            ->assertJsonPath('impact.approval_changes.leave_requests.from', true)
            ->assertJsonPath('impact.approval_changes.leave_requests.to', false);

        $this->assertContains('exams', $response->json('impact.disabled_modules'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'master-control',
            'action' => 'preview',
            'user_id' => $admin->id,
        ]);
    }

    public function test_non_super_admin_cannot_preview_master_control_impact(): void
    {
        $teacher = $this->makeTeacher();

        $this->actingAs($teacher)
            ->postJson(route('license-settings.impact-preview'), [
                'plan_name' => 'Starter',
            ])
            ->assertForbidden();
    }

    public function test_rollback_restores_previous_master_control_snapshot(): void
    {
        $admin = $this->makeSuperAdmin();

        LicenseConfig::query()->create([
            'license_key' => 'STARTER-001',
            'plan_name' => 'Starter',
            'student_limit' => 500,
            'is_active' => true,
            'enabled_modules' => ['students', 'staff'],
            'approval_settings' => [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
            ],
            'role_limits' => [
                'admin' => 1,
                'hr' => 1,
                'teacher' => 20,
            ],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('license-settings.update'), [
            'plan_name' => 'Professional',
            'is_active' => 1,
            'enabled_modules' => ['students', 'staff', 'exams'],
            'approval_settings' => [
                'leave_requests' => 1,
                'student_calendar_mappings' => 1,
            ],
            'role_limits' => [
                'admin' => 5,
                'hr' => 5,
                'teacher' => 150,
            ],
            'student_limit' => 2500,
            'notes' => 'Move to professional',
        ])->assertRedirect();

        $this->assertDatabaseHas('license_configs', [
            'plan_name' => 'Professional',
        ]);

        $this->assertGreaterThanOrEqual(1, MasterControlSnapshot::query()->count());

        $this->actingAs($admin)
            ->post(route('license-settings.rollback-last'))
            ->assertRedirect();

        $this->assertDatabaseHas('license_configs', [
            'plan_name' => 'Starter',
            'student_limit' => 500,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'master-control',
            'action' => 'rollback',
            'user_id' => $admin->id,
        ]);

        $this->assertTrue(AuditLog::query()->where('module', 'master-control')->where('action', 'update')->exists());
    }

    public function test_disabled_module_blocks_guarded_exam_builder_route_for_super_admin(): void
    {
        $admin = $this->makeSuperAdmin();

        LicenseConfig::query()->create([
            'license_key' => 'STRICT-001',
            'plan_name' => 'Starter',
            'is_active' => true,
            'enabled_modules' => ['students', 'staff'],
            'approval_settings' => [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
            ],
            'role_limits' => [
                'admin' => 1,
                'hr' => 1,
                'teacher' => 20,
            ],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('exam-builder.index'))
            ->assertForbidden();
    }
}
