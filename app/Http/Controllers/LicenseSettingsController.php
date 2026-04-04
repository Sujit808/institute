<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LicenseConfig;
use App\Models\MasterControlSnapshot;
use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenseSettingsController extends Controller
{
    public function edit(): View
    {
        $license = LicenseConfig::current() ?? new LicenseConfig([
            'plan_name' => 'Starter',
            'is_active' => true,
        ]);
        $usageSummary = $license->currentUsageSummary();
        $recommendedPlan = LicenseConfig::recommendPlanForUsage($usageSummary, $license->resolvedEnabledModules(), $license->planKey());
        $currentPlanRank = LicenseConfig::planRank($license->planKey());
        $recommendedPlanRank = LicenseConfig::planRank((string) ($recommendedPlan['key'] ?? 'starter'));

        return view('license-settings.edit', [
            'license' => $license,
            'availablePlans' => LicenseConfig::availablePlanPresets(),
            'moduleLabels' => LicenseConfig::managedModuleLabels(),
            'usageSummary' => $usageSummary,
            'recommendedPlan' => $recommendedPlan,
            'upgradeRecommended' => $recommendedPlanRank > $currentPlanRank,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $currentLicense = LicenseConfig::current();
        $planLabels = collect(LicenseConfig::availablePlanPresets())->pluck('label')->values()->all();
        $managedModules = array_keys(LicenseConfig::managedModuleLabels());

        $validated = $request->validate([
            'license_key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('license_configs', 'license_key')->ignore($currentLicense?->id),
            ],
            'plan_name' => ['required', 'string', Rule::in($planLabels)],
            'student_limit' => ['nullable', 'integer', 'min:1', 'max:200000'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'enabled_modules' => ['nullable', 'array'],
            'enabled_modules.*' => ['string', Rule::in($managedModules)],
            'approval_settings' => ['nullable', 'array'],
            'approval_settings.leave_requests' => ['nullable', 'boolean'],
            'approval_settings.student_calendar_mappings' => ['nullable', 'boolean'],
            'approval_settings.admission_duplicate_strict' => ['nullable', 'boolean'],
            'approval_settings.admission_wip_limits' => ['nullable', 'array'],
            'approval_settings.admission_wip_limits.new' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'approval_settings.admission_wip_limits.contacted' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'approval_settings.admission_wip_limits.counselling_scheduled' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'approval_settings.admission_wip_limits.counselling_done' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'approval_settings.admission_wip_limits.follow_up' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'approval_settings.admission_wip_limits.converted' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'approval_settings.admission_wip_limits.lost' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'role_limits' => ['nullable', 'array'],
            'role_limits.admin' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'role_limits.hr' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'role_limits.teacher' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $license = $currentLicense ?? new LicenseConfig;
        $beforeState = $this->masterControlState($license);
        $selectedPlan = LicenseConfig::availablePlanPresets()[LicenseConfig::normalizedPlanKey($validated['plan_name'])] ?? LicenseConfig::availablePlanPresets()['starter'];
        $enabledModules = collect($request->input('enabled_modules', $selectedPlan['modules']))
            ->map(fn ($module) => (string) $module)
            ->filter(fn (string $module): bool => in_array($module, $managedModules, true))
            ->unique()
            ->values()
            ->all();

        $approvalSettings = [
            'leave_requests' => $request->boolean('approval_settings.leave_requests'),
            'student_calendar_mappings' => $request->boolean('approval_settings.student_calendar_mappings'),
            'admission_duplicate_strict' => $request->boolean('approval_settings.admission_duplicate_strict', true),
            'admission_wip_limits' => collect(array_keys(LicenseConfig::defaultAdmissionLeadWipLimits()))
                ->mapWithKeys(function (string $stage) use ($request): array {
                    $raw = $request->input('approval_settings.admission_wip_limits.'.$stage);
                    return [$stage => $raw === null || $raw === '' ? null : (int) $raw];
                })
                ->all(),
        ];

        $roleLimits = collect(['admin', 'hr', 'teacher'])->mapWithKeys(function (string $role) use ($request) {
            $rawValue = $request->input('role_limits.'.$role);

            return [$role => $rawValue === null || $rawValue === '' ? null : (int) $rawValue];
        })->all();

        $license->fill([
            'license_key' => $validated['license_key'] ?? null,
            'plan_name' => $selectedPlan['label'],
            'student_limit' => $validated['student_limit'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => (bool) ($request->boolean('is_active')),
            'enabled_modules' => $enabledModules,
            'approval_settings' => $approvalSettings,
            'role_limits' => $roleLimits,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()->id,
            'created_by' => $license->exists ? $license->created_by : $request->user()->id,
        ]);

        $license->save();

        $afterState = $this->masterControlState($license);
        $this->snapshot($license, $beforeState, $request->user()->id, 'Before master control update');
        $this->audit($request, 'update', 'Master control updated', $beforeState, $afterState);

        return back()->with('status', 'Master Control settings updated successfully.');
    }

    public function impactPreview(Request $request): JsonResponse
    {
        $current = LicenseConfig::current() ?? new LicenseConfig([
            'plan_name' => 'Starter',
            'is_active' => true,
        ]);

        $preview = $this->previewState($request, $current);
        $impact = $this->calculateImpact($this->masterControlState($current), $preview);

        $this->audit($request, 'preview', 'Master control impact preview generated', $this->masterControlState($current), $preview);

        return response()->json([
            'impact' => $impact,
        ]);
    }

    public function rollbackLast(Request $request): RedirectResponse
    {
        $snapshot = MasterControlSnapshot::query()->latest('id')->first();
        if (! $snapshot || ! is_array($snapshot->snapshot)) {
            return back()->with('status', 'No previous master control snapshot available to rollback.');
        }

        $license = LicenseConfig::current() ?? new LicenseConfig;
        $beforeState = $this->masterControlState($license);

        $rollbackPayload = Arr::only($snapshot->snapshot, [
            'license_key',
            'plan_name',
            'student_limit',
            'expires_at',
            'is_active',
            'enabled_modules',
            'approval_settings',
            'role_limits',
            'notes',
        ]);

        $license->fill(array_merge($rollbackPayload, [
            'updated_by' => $request->user()->id,
            'created_by' => $license->exists ? $license->created_by : $request->user()->id,
        ]));
        $license->save();

        $afterState = $this->masterControlState($license);
        $this->snapshot($license, $beforeState, $request->user()->id, 'Before rollback to last snapshot');
        $this->audit($request, 'rollback', 'Master control rolled back to last snapshot', $beforeState, $afterState);

        return back()->with('status', 'Master Control rolled back successfully.');
    }

    public function generateKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_name' => ['nullable', 'string', 'max:100'],
            'app_name' => ['nullable', 'string', 'max:100'],
        ]);

        $key = $this->generateUniqueLicenseKey(
            $validated['app_name'] ?? config('app.name', 'SchoolERP'),
            $validated['plan_name'] ?? 'Starter'
        );

        return response()->json([
            'license_key' => $key,
        ]);
    }

    private function generateUniqueLicenseKey(string $appName, string $planName): string
    {
        $year = date('Y');
        $month = date('m');
        $appChunk = $this->normalizeChunk($appName, 'SCHOOL', 6);
        $planChunk = $this->normalizeChunk($planName, 'STD', 3);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = implode('-', [
                $appChunk,
                $planChunk,
                $year,
                $month,
                $this->randomChunk(4),
                $this->randomChunk(4),
            ]);

            if (! LicenseConfig::query()->where('license_key', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Unable to generate unique license key. Please retry.');
    }

    private function normalizeChunk(string $value, string $fallback, int $length): string
    {
        $cleaned = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value) ?? '');
        $source = $cleaned !== '' ? $cleaned : $fallback;

        return str_pad(substr($source, 0, $length), $length, 'X');
    }

    private function randomChunk(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;
        $result = '';

        for ($index = 0; $index < $length; $index++) {
            $result .= $alphabet[random_int(0, $maxIndex)];
        }

        return $result;
    }

    private function masterControlState(?LicenseConfig $license): array
    {
        if (! $license || ! $license->exists) {
            return [
                'license_key' => null,
                'plan_name' => 'Starter',
                'student_limit' => null,
                'expires_at' => null,
                'is_active' => true,
                'enabled_modules' => [],
                'approval_settings' => [
                    'leave_requests' => true,
                    'student_calendar_mappings' => true,
                    'admission_duplicate_strict' => true,
                    'admission_wip_limits' => LicenseConfig::defaultAdmissionLeadWipLimits(),
                ],
                'role_limits' => [
                    'admin' => null,
                    'hr' => null,
                    'teacher' => null,
                ],
                'notes' => null,
            ];
        }

        return [
            'license_key' => $license->license_key,
            'plan_name' => $license->plan_name,
            'student_limit' => $license->student_limit,
            'expires_at' => optional($license->expires_at)->format('Y-m-d'),
            'is_active' => (bool) $license->is_active,
            'enabled_modules' => $license->resolvedEnabledModules(),
            'approval_settings' => $license->resolvedApprovalSettings(),
            'role_limits' => $license->resolvedRoleLimits(),
            'notes' => $license->notes,
        ];
    }

    private function previewState(Request $request, LicenseConfig $current): array
    {
        $plans = LicenseConfig::availablePlanPresets();
        $selectedLabel = (string) $request->input('plan_name', $current->planLabel());
        $selectedPlan = $plans[LicenseConfig::normalizedPlanKey($selectedLabel)] ?? $plans['starter'];
        $managedModules = array_keys(LicenseConfig::managedModuleLabels());

        $modules = collect($request->input('enabled_modules', $selectedPlan['modules']))
            ->map(fn ($module) => SchoolModuleRegistry::normalizePermissionKey((string) $module))
            ->filter(fn ($module) => in_array($module, $managedModules, true))
            ->unique()
            ->values()
            ->all();

        return [
            'license_key' => (string) $request->input('license_key', $current->license_key),
            'plan_name' => $selectedPlan['label'],
            'student_limit' => $request->filled('student_limit') ? (int) $request->input('student_limit') : null,
            'expires_at' => $request->input('expires_at') ?: optional($current->expires_at)->format('Y-m-d'),
            'is_active' => $request->boolean('is_active'),
            'enabled_modules' => $modules,
            'approval_settings' => [
                'leave_requests' => $request->boolean('approval_settings.leave_requests'),
                'student_calendar_mappings' => $request->boolean('approval_settings.student_calendar_mappings'),
                'admission_duplicate_strict' => $request->boolean('approval_settings.admission_duplicate_strict', true),
                'admission_wip_limits' => collect(array_keys(LicenseConfig::defaultAdmissionLeadWipLimits()))
                    ->mapWithKeys(function (string $stage) use ($request): array {
                        $raw = $request->input('approval_settings.admission_wip_limits.'.$stage);

                        return [$stage => $raw === null || $raw === '' ? null : (int) $raw];
                    })
                    ->all(),
            ],
            'role_limits' => [
                'admin' => $request->input('role_limits.admin') !== null && $request->input('role_limits.admin') !== '' ? (int) $request->input('role_limits.admin') : null,
                'hr' => $request->input('role_limits.hr') !== null && $request->input('role_limits.hr') !== '' ? (int) $request->input('role_limits.hr') : null,
                'teacher' => $request->input('role_limits.teacher') !== null && $request->input('role_limits.teacher') !== '' ? (int) $request->input('role_limits.teacher') : null,
            ],
            'notes' => (string) $request->input('notes', $current->notes),
        ];
    }

    private function calculateImpact(array $current, array $preview): array
    {
        $disabledModules = array_values(array_diff($current['enabled_modules'] ?? [], $preview['enabled_modules'] ?? []));
        $enabledModules = array_values(array_diff($preview['enabled_modules'] ?? [], $current['enabled_modules'] ?? []));

        $affectedRoles = [
            'admin' => User::query()->where('role', 'admin')->count(),
            'hr' => User::query()->where('role', 'hr')->count(),
            'teacher' => User::query()->where('role', 'teacher')->count(),
        ];

        $routeImpact = collect($disabledModules)->mapWithKeys(function (string $module): array {
            return [$module => [
                '/'.$module,
                '/'.$module.'/export/pdf',
                '/'.$module.'/export/excel',
            ]];
        })->all();

        return [
            'plan_from' => $current['plan_name'] ?? 'Starter',
            'plan_to' => $preview['plan_name'] ?? 'Starter',
            'disabled_modules' => $disabledModules,
            'enabled_modules' => $enabledModules,
            'affected_role_counts' => $affectedRoles,
            'route_impact' => $routeImpact,
            'approval_changes' => [
                'leave_requests' => [
                    'from' => (bool) ($current['approval_settings']['leave_requests'] ?? true),
                    'to' => (bool) ($preview['approval_settings']['leave_requests'] ?? true),
                ],
                'student_calendar_mappings' => [
                    'from' => (bool) ($current['approval_settings']['student_calendar_mappings'] ?? true),
                    'to' => (bool) ($preview['approval_settings']['student_calendar_mappings'] ?? true),
                ],
                'admission_wip_limits' => [
                    'from' => $current['approval_settings']['admission_wip_limits'] ?? LicenseConfig::defaultAdmissionLeadWipLimits(),
                    'to' => $preview['approval_settings']['admission_wip_limits'] ?? LicenseConfig::defaultAdmissionLeadWipLimits(),
                ],
                'admission_duplicate_strict' => [
                    'from' => (bool) ($current['approval_settings']['admission_duplicate_strict'] ?? true),
                    'to' => (bool) ($preview['approval_settings']['admission_duplicate_strict'] ?? true),
                ],
            ],
        ];
    }

    private function snapshot(LicenseConfig $license, array $state, int $userId, string $summary): void
    {
        MasterControlSnapshot::create([
            'license_config_id' => $license->id,
            'snapshot' => $state,
            'change_summary' => $summary,
            'changed_by' => $userId,
        ]);
    }

    private function audit(Request $request, string $action, string $description, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'module' => 'master-control',
            'action' => $action,
            'description' => $description,
            'auditable_type' => LicenseConfig::class,
            'auditable_id' => LicenseConfig::current()?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
    }
}
