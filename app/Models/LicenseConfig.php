<?php

namespace App\Models;

use App\Support\SchoolModuleRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LicenseConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key',
        'plan_name',
        'student_limit',
        'expires_at',
        'is_active',
        'enabled_modules',
        'approval_settings',
        'role_limits',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'is_active' => 'boolean',
            'student_limit' => 'integer',
            'enabled_modules' => 'array',
            'approval_settings' => 'array',
            'role_limits' => 'array',
        ];
    }

    public static function current(): ?self
    {
        if (! Schema::hasTable('license_configs')) {
            return null;
        }

        return static::query()->latest('id')->first();
    }

    public static function managedModuleLabels(): array
    {
        return collect(SchoolModuleRegistry::lookupPermissions())
            ->reject(fn (string $label, string $module): bool => $module === 'audit-logs')
            ->all();
    }

    public static function normalizedPlanKey(?string $planName): string
    {
        return match (strtolower(trim((string) $planName))) {
            'starter', 'start', 'basic' => 'starter',
            'professional', 'pro' => 'professional',
            'enterprise', 'ent' => 'enterprise',
            default => 'starter',
        };
    }

    public static function availablePlanPresets(): array
    {
        $allModules = array_keys(static::managedModuleLabels());
        $defaultLeadWipLimits = static::defaultAdmissionLeadWipLimits();

        return [
            'starter' => [
                'key' => 'starter',
                'label' => 'Starter',
                'description' => 'Core academics and day-to-day operations for a small institute.',
                'modules' => [
                    'students',
                    'admission-leads',
                    'staff',
                    'classes',
                    'sections',
                    'subjects',
                    'attendance',
                    'results',
                    'fees',
                    'timetable',
                    'leaves',
                    'calendar',
                    'holidays',
                    'notifications',
                ],
                'student_limit' => 500,
                'role_limits' => [
                    'admin' => 1,
                    'hr' => 1,
                    'teacher' => 20,
                ],
                'approval_settings' => [
                    'leave_requests' => true,
                    'student_calendar_mappings' => true,
                    'admission_wip_limits' => $defaultLeadWipLimits,
                    'admission_duplicate_strict' => true,
                ],
            ],
            'professional' => [
                'key' => 'professional',
                'label' => 'Professional',
                'description' => 'Expanded academic, finance, and document workflows for growing schools.',
                'modules' => [
                    'students',
                    'admission-leads',
                    'staff',
                    'classes',
                    'sections',
                    'subjects',
                    'exams',
                    'exam-questions',
                    'exam-papers',
                    'study-materials',
                    'results',
                    'attendance',
                    'fees',
                    'timetable',
                    'notifications',
                    'holidays',
                    'leaves',
                    'calendar',
                    'icards',
                    'biometric-devices',
                ],
                'student_limit' => 2500,
                'role_limits' => [
                    'admin' => 5,
                    'hr' => 5,
                    'teacher' => 150,
                ],
                'approval_settings' => [
                    'leave_requests' => true,
                    'student_calendar_mappings' => true,
                    'admission_wip_limits' => $defaultLeadWipLimits,
                    'admission_duplicate_strict' => true,
                ],
            ],
            'enterprise' => [
                'key' => 'enterprise',
                'label' => 'Enterprise',
                'description' => 'Full platform access including integrations, quotations, and biometric operations.',
                'modules' => $allModules,
                'student_limit' => null,
                'role_limits' => [
                    'admin' => null,
                    'hr' => null,
                    'teacher' => null,
                ],
                'approval_settings' => [
                    'leave_requests' => true,
                    'student_calendar_mappings' => true,
                    'admission_wip_limits' => $defaultLeadWipLimits,
                    'admission_duplicate_strict' => true,
                ],
            ],
        ];
    }

    public static function defaultAdmissionLeadWipLimits(): array
    {
        return [
            'new' => 40,
            'contacted' => 35,
            'counselling_scheduled' => 25,
            'counselling_done' => 25,
            'follow_up' => 30,
            'converted' => 100,
            'lost' => 100,
        ];
    }

    public function planKey(): string
    {
        return static::normalizedPlanKey($this->plan_name);
    }

    public function planLabel(): string
    {
        return static::availablePlanPresets()[$this->planKey()]['label'] ?? 'Starter';
    }

    public function resolvedPlanPreset(): array
    {
        return static::availablePlanPresets()[$this->planKey()] ?? static::availablePlanPresets()['starter'];
    }

    public function resolvedEnabledModules(): array
    {
        $availableModules = array_keys(static::managedModuleLabels());
        $presetModules = $this->resolvedPlanPreset()['modules'] ?? [];
        $configuredModules = $this->enabled_modules ?? $presetModules;

        $modules = collect(is_array($configuredModules) ? $configuredModules : [])
            ->map(fn ($module) => SchoolModuleRegistry::normalizePermissionKey((string) $module))
            ->filter(fn (string $module): bool => in_array($module, $availableModules, true))
            ->unique()
            ->values()
            ->all();

        return $modules !== [] ? $modules : $presetModules;
    }

    public function moduleEnabled(string $module): bool
    {
        $module = SchoolModuleRegistry::normalizePermissionKey($module);

        if ($module === 'audit-logs') {
            return true;
        }

        return in_array($module, $this->resolvedEnabledModules(), true);
    }

    public function resolvedApprovalSettings(): array
    {
        $defaults = [
            'leave_requests' => true,
            'student_calendar_mappings' => true,
            'admission_wip_limits' => static::defaultAdmissionLeadWipLimits(),
            'admission_duplicate_strict' => true,
        ];

        $presetSettings = $this->resolvedPlanPreset()['approval_settings'] ?? [];
        $configuredSettings = is_array($this->approval_settings) ? $this->approval_settings : [];

        $settings = array_merge($defaults, $presetSettings, $configuredSettings);
        $settings['admission_wip_limits'] = array_merge(
            static::defaultAdmissionLeadWipLimits(),
            is_array($presetSettings['admission_wip_limits'] ?? null) ? $presetSettings['admission_wip_limits'] : [],
            is_array($configuredSettings['admission_wip_limits'] ?? null) ? $configuredSettings['admission_wip_limits'] : []
        );

        return $settings;
    }

    public function admissionLeadWipLimits(): array
    {
        $defaults = static::defaultAdmissionLeadWipLimits();
        $configured = $this->resolvedApprovalSettings()['admission_wip_limits'] ?? [];

        if (! is_array($configured)) {
            return $defaults;
        }

        return collect($defaults)->mapWithKeys(function (int $fallback, string $stage) use ($configured): array {
            $value = $configured[$stage] ?? $fallback;
            $normalized = is_numeric($value) ? (int) $value : $fallback;

            return [$stage => $normalized > 0 ? $normalized : $fallback];
        })->all();
    }

    public function admissionDuplicateStrict(): bool
    {
        return (bool) ($this->resolvedApprovalSettings()['admission_duplicate_strict'] ?? true);
    }

    public function approvalRequired(string $key): bool
    {
        return (bool) ($this->resolvedApprovalSettings()[$key] ?? false);
    }

    public function resolvedRoleLimits(): array
    {
        $defaults = [
            'admin' => null,
            'hr' => null,
            'teacher' => null,
        ];

        $presetLimits = $this->resolvedPlanPreset()['role_limits'] ?? [];
        $configuredLimits = is_array($this->role_limits) ? $this->role_limits : [];

        return array_merge($defaults, $presetLimits, $configuredLimits);
    }

    public function limitForRole(string $role): ?int
    {
        $limit = $this->resolvedRoleLimits()[$role] ?? null;

        if ($limit === null || $limit === '') {
            return null;
        }

        $normalizedLimit = (int) $limit;

        return $normalizedLimit > 0 ? $normalizedLimit : null;
    }

    public function resolvedStudentLimit(): ?int
    {
        if ($this->student_limit) {
            return (int) $this->student_limit;
        }

        return $this->resolvedPlanPreset()['student_limit'] ?? null;
    }

    public function currentUsageSummary(): array
    {
        return [
            'students' => Student::query()->count(),
            'admin' => User::query()->where('role', 'admin')->count(),
            'hr' => User::query()->where('role', 'hr')->count(),
            'teacher' => User::query()->where('role', 'teacher')->count(),
        ];
    }

    public static function planRank(string $planKey): int
    {
        return match ($planKey) {
            'starter' => 1,
            'professional' => 2,
            'enterprise' => 3,
            default => 1,
        };
    }

    public static function recommendPlanForUsage(array $usage, array $requiredModules = [], ?string $currentPlanKey = null): array
    {
        $plans = static::availablePlanPresets();
        $orderedPlanKeys = ['starter', 'professional', 'enterprise'];
        $moduleFloor = static::minimumPlanForModules($requiredModules);
        $startIndex = array_search($moduleFloor['key'], $orderedPlanKeys, true);
        $startIndex = $startIndex === false ? 0 : $startIndex;
        $currentPlanIssues = static::currentPlanIssues($currentPlanKey, $usage, $requiredModules);
        $currentPlanFixes = static::currentPlanFixSuggestions($currentPlanKey, $usage, $requiredModules);

        foreach (array_slice($orderedPlanKeys, $startIndex) as $planKey) {
            $plan = $plans[$planKey] ?? null;
            if (! $plan) {
                continue;
            }

            if (static::planSupportsUsage($plan, $usage)) {
                $reasons = static::recommendationReasons($plan, $usage);
                if (! empty($moduleFloor['reason'])) {
                    array_unshift($reasons, $moduleFloor['reason']);
                }

                return [
                    'key' => $plan['key'],
                    'label' => $plan['label'],
                    'reasons' => $reasons,
                    'current_plan_issues' => $currentPlanIssues,
                    'current_plan_fixes' => $currentPlanFixes,
                ];
            }
        }

        $fallback = $plans['enterprise'];
        $fallbackReasons = ['Usage exceeds Starter/Professional role or student caps.'];
        if (! empty($moduleFloor['reason'])) {
            array_unshift($fallbackReasons, $moduleFloor['reason']);
        }

        return [
            'key' => $fallback['key'],
            'label' => $fallback['label'],
            'reasons' => $fallbackReasons,
            'current_plan_issues' => $currentPlanIssues,
            'current_plan_fixes' => $currentPlanFixes,
        ];
    }

    private static function currentPlanFixSuggestions(?string $currentPlanKey, array $usage, array $requiredModules): array
    {
        if (! $currentPlanKey) {
            return [];
        }

        $plans = static::availablePlanPresets();
        $currentPlan = $plans[$currentPlanKey] ?? null;
        if (! $currentPlan) {
            return [];
        }

        $fixes = [];
        $normalizedModules = collect($requiredModules)
            ->map(fn ($module) => SchoolModuleRegistry::normalizePermissionKey((string) $module))
            ->filter(fn (string $module): bool => $module !== '' && $module !== 'audit-logs')
            ->unique()
            ->values()
            ->all();

        $missingModules = array_values(array_diff($normalizedModules, collect($currentPlan['modules'] ?? [])->values()->all()));
        if ($missingModules !== []) {
            $minimumPlan = static::minimumPlanForModules($normalizedModules);
            $minimumPlanLabel = $plans[$minimumPlan['key']]['label'] ?? 'Enterprise';

            $fixes[] = [
                'type' => 'plan',
                'label' => 'Apply '.$minimumPlanLabel.' plan to include required modules.',
                'plan_label' => $minimumPlanLabel,
            ];
        }

        $studentLimit = $currentPlan['student_limit'] ?? null;
        if ($studentLimit !== null && ((int) ($usage['students'] ?? 0) > (int) $studentLimit)) {
            $fixes[] = [
                'type' => 'student_limit',
                'label' => 'Set student limit to at least '.(int) $usage['students'].'.',
                'value' => (int) $usage['students'],
            ];
        }

        foreach (['admin', 'hr', 'teacher'] as $role) {
            $roleLimit = $currentPlan['role_limits'][$role] ?? null;
            $used = (int) ($usage[$role] ?? 0);

            if ($roleLimit !== null && $used > (int) $roleLimit) {
                $fixes[] = [
                    'type' => 'role_limit',
                    'label' => 'Set '.strtoupper($role).' user limit to at least '.$used.'.',
                    'role' => $role,
                    'value' => $used,
                ];
            }
        }

        return $fixes;
    }

    private static function currentPlanIssues(?string $currentPlanKey, array $usage, array $requiredModules): array
    {
        if (! $currentPlanKey) {
            return [];
        }

        $plans = static::availablePlanPresets();
        $plan = $plans[$currentPlanKey] ?? null;
        if (! $plan) {
            return [];
        }

        $issues = [];
        $normalizedModules = collect($requiredModules)
            ->map(fn ($module) => SchoolModuleRegistry::normalizePermissionKey((string) $module))
            ->filter(fn (string $module): bool => $module !== '' && $module !== 'audit-logs')
            ->unique()
            ->values()
            ->all();

        $missingModules = array_values(array_diff($normalizedModules, collect($plan['modules'] ?? [])->values()->all()));
        if ($missingModules !== []) {
            $issues[] = 'Missing modules: '.implode(', ', $missingModules);
        }

        $studentLimit = $plan['student_limit'] ?? null;
        if ($studentLimit !== null && ((int) ($usage['students'] ?? 0) > (int) $studentLimit)) {
            $issues[] = 'Student cap exceeded by '.(((int) $usage['students']) - ((int) $studentLimit));
        }

        foreach (['admin' => 'Admin', 'hr' => 'HR', 'teacher' => 'Teacher'] as $role => $label) {
            $roleLimit = $plan['role_limits'][$role] ?? null;
            if ($roleLimit !== null && ((int) ($usage[$role] ?? 0) > (int) $roleLimit)) {
                $issues[] = $label.' cap exceeded by '.(((int) $usage[$role]) - ((int) $roleLimit));
            }
        }

        return $issues;
    }

    private static function minimumPlanForModules(array $requiredModules): array
    {
        $normalizedModules = collect($requiredModules)
            ->map(fn ($module) => SchoolModuleRegistry::normalizePermissionKey((string) $module))
            ->filter(fn (string $module): bool => $module !== '' && $module !== 'audit-logs')
            ->unique()
            ->values()
            ->all();

        if ($normalizedModules === []) {
            return ['key' => 'starter', 'reason' => null];
        }

        $plans = static::availablePlanPresets();
        foreach (['starter', 'professional', 'enterprise'] as $planKey) {
            $plan = $plans[$planKey] ?? null;
            if (! $plan) {
                continue;
            }

            $planModules = collect($plan['modules'] ?? [])->values()->all();
            $missing = array_values(array_diff($normalizedModules, $planModules));

            if ($missing === []) {
                return [
                    'key' => $planKey,
                    'reason' => 'Enabled modules align with '.$plan['label'].' plan coverage.',
                ];
            }
        }

        return [
            'key' => 'enterprise',
            'reason' => 'Enabled modules require Enterprise-level module coverage.',
        ];
    }

    private static function planSupportsUsage(array $plan, array $usage): bool
    {
        $studentLimit = $plan['student_limit'] ?? null;
        if ($studentLimit !== null && ((int) ($usage['students'] ?? 0) > (int) $studentLimit)) {
            return false;
        }

        foreach (['admin', 'hr', 'teacher'] as $role) {
            $roleLimit = $plan['role_limits'][$role] ?? null;
            if ($roleLimit !== null && ((int) ($usage[$role] ?? 0) > (int) $roleLimit)) {
                return false;
            }
        }

        return true;
    }

    private static function recommendationReasons(array $plan, array $usage): array
    {
        return [
            'Students '.$usage['students'].' within limit '.($plan['student_limit'] ? number_format((int) $plan['student_limit']) : 'Unlimited'),
            'Admin users '.$usage['admin'].' within limit '.(($plan['role_limits']['admin'] ?? null) !== null ? (string) $plan['role_limits']['admin'] : 'Unlimited'),
            'HR users '.$usage['hr'].' within limit '.(($plan['role_limits']['hr'] ?? null) !== null ? (string) $plan['role_limits']['hr'] : 'Unlimited'),
            'Teacher users '.$usage['teacher'].' within limit '.(($plan['role_limits']['teacher'] ?? null) !== null ? (string) $plan['role_limits']['teacher'] : 'Unlimited'),
        ];
    }
}
