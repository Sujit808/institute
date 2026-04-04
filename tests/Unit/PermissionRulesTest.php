<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Tests\TestCase;

class PermissionRulesTest extends TestCase
{
    public function test_hr_default_permissions_include_attendance_results_and_icards(): void
    {
        $permissions = SchoolModuleRegistry::defaultPermissionsForRole('hr');

        $this->assertContains('attendance', $permissions);
        $this->assertContains('results', $permissions);
        $this->assertContains('icards', $permissions);
    }

    public function test_teacher_default_permissions_include_materials_exam_papers_and_icards(): void
    {
        $permissions = SchoolModuleRegistry::defaultPermissionsForRole('teacher');

        $this->assertContains('study-materials', $permissions);
        $this->assertContains('exam-papers', $permissions);
        $this->assertContains('icards', $permissions);
    }

    public function test_biometric_enrollments_permission_is_aliased_to_biometric_devices(): void
    {
        $this->assertSame('biometric-devices', SchoolModuleRegistry::normalizePermissionKey('biometric-enrollments'));
    }

    public function test_hr_user_can_access_attendance_but_not_students_when_missing_permission(): void
    {
        $user = new User([
            'role' => 'hr',
            'permissions' => ['attendance'],
        ]);

        $this->assertTrue($user->canAccessModule('attendance'));
        $this->assertFalse($user->canAccessModule('students'));
    }

    public function test_teacher_user_can_access_newly_enabled_modules(): void
    {
        $user = new User([
            'role' => 'teacher',
            'permissions' => [],
        ]);

        $this->assertTrue($user->canAccessModule('study-materials'));
        $this->assertTrue($user->canAccessModule('exam-papers'));
        $this->assertTrue($user->canAccessModule('icards'));
    }
}
