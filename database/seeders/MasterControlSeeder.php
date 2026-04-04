<?php

namespace Database\Seeders;

use App\Models\LicenseConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterControlSeeder extends Seeder
{
    public function run(): void
    {
        $license = LicenseConfig::query()->latest('id')->first() ?? new LicenseConfig;
        $superAdminId = User::query()->where('role', 'super_admin')->value('id');
        $enterprisePlan = LicenseConfig::availablePlanPresets()['enterprise'];

        if ($license->exists && $license->enabled_modules !== null && $license->approval_settings !== null && $license->role_limits !== null) {
            return;
        }

        $license->fill([
            'license_key' => $license->license_key ?: 'MEERAH-ENT-2026-SEED-CTRL',
            'plan_name' => $license->plan_name ?: $enterprisePlan['label'],
            'student_limit' => $license->student_limit,
            'expires_at' => $license->expires_at ?: now()->addYear()->toDateString(),
            'is_active' => $license->is_active ?? true,
            'enabled_modules' => $license->enabled_modules ?? $enterprisePlan['modules'],
            'approval_settings' => $license->approval_settings ?? [
                'leave_requests' => true,
                'student_calendar_mappings' => true,
            ],
            'role_limits' => $license->role_limits ?? [
                'admin' => null,
                'hr' => null,
                'teacher' => null,
            ],
            'notes' => $license->notes ?: 'Seeded default Master Control configuration for fresh installs.',
            'created_by' => $license->created_by ?: $superAdminId,
            'updated_by' => $superAdminId,
        ]);

        $license->save();
    }
}
