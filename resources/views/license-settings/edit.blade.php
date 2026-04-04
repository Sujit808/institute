@extends('layouts.app')

@php
    $selectedModules = collect(old('enabled_modules', $license->resolvedEnabledModules()))
        ->map(fn ($module) => \App\Support\SchoolModuleRegistry::normalizePermissionKey((string) $module))
        ->unique()
        ->values()
        ->all();
    $approvalSettings = $license->resolvedApprovalSettings();
    $admissionWipLimits = $license->admissionLeadWipLimits();
    $roleLimits = $license->resolvedRoleLimits();
    $studentsLimit = $license->resolvedStudentLimit();
    $studentExceeded = $studentsLimit && ($usageSummary['students'] > $studentsLimit);
@endphp

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Super Admin Master Control</span>
            <h1 class="h2 mb-1">Modules, Approvals, Users</h1>
            <p class="text-body-secondary mb-0">Control SaaS plan access, module availability, approval workflow behavior, and user-role capacity from one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-dark" id="previewImpactBtn">Preview Impact</button>
            <button type="button" class="btn btn-outline-danger" id="rollbackLastBtn">Rollback Last Change</button>
            <a class="btn btn-outline-secondary" href="{{ route('settings.access-matrix') }}">Open Access Matrix</a>
            <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Back to Dashboard</a>
        </div>
    </div>

    <form method="POST" action="{{ route('license-settings.rollback-last') }}" id="rollbackMasterControlForm" class="d-none">
        @csrf
    </form>

    @if (!empty($recommendedPlan['label']))
        <div class="alert {{ !empty($upgradeRecommended) ? 'alert-warning' : 'alert-success' }} border-0 shadow-sm d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4" role="alert">
            <div>
                <div class="fw-semibold">Recommended Plan: {{ $recommendedPlan['label'] }}</div>
                <div class="small">
                    {{ !empty($upgradeRecommended) ? 'Current usage suggests an upgrade for safer scaling.' : 'Current plan capacity is aligned with current usage.' }}
                </div>
                @if (!empty($recommendedPlan['reasons']) && is_array($recommendedPlan['reasons']))
                    <div class="small mt-1 text-body-secondary">
                        {{ implode(' | ', array_slice($recommendedPlan['reasons'], 0, 2)) }}
                    </div>
                @endif
                @if (!empty($recommendedPlan['current_plan_issues']) && is_array($recommendedPlan['current_plan_issues']))
                    <div class="small mt-2">
                        <span class="fw-semibold">Why not current plan:</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @foreach ($recommendedPlan['current_plan_issues'] as $issue)
                            <span class="badge text-bg-light border">{{ $issue }}</span>
                        @endforeach
                    </div>
                @endif
                @if (!empty($recommendedPlan['current_plan_fixes']) && is_array($recommendedPlan['current_plan_fixes']))
                    <div class="small mt-2 fw-semibold">Auto-fix suggestions:</div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @foreach ($recommendedPlan['current_plan_fixes'] as $fix)
                            @if (($fix['type'] ?? null) === 'plan' && !empty($fix['plan_label']))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark"
                                    data-quick-fix-plan="{{ $fix['plan_label'] }}"
                                >{{ $fix['label'] }}</button>
                            @elseif (($fix['type'] ?? null) === 'student_limit' && !empty($fix['value']))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark"
                                    data-quick-fix-student-limit="{{ (int) $fix['value'] }}"
                                >{{ $fix['label'] }}</button>
                            @elseif (($fix['type'] ?? null) === 'role_limit' && !empty($fix['role']) && !empty($fix['value']))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark"
                                    data-quick-fix-role="{{ $fix['role'] }}"
                                    data-quick-fix-role-value="{{ (int) $fix['value'] }}"
                                >{{ $fix['label'] }}</button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            @if (!empty($upgradeRecommended))
                <button type="button" class="btn btn-sm btn-outline-dark" id="applyRecommendedPlanBtn" data-recommended-plan="{{ $recommendedPlan['label'] }}">Apply Recommended Plan</button>
            @endif
        </div>
    @endif

    <div class="card app-card border-0 shadow-sm mb-4 d-none" id="impactPreviewPanel">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Impact Preview</h2>
                <span class="badge text-bg-info" id="impactPlanChangeBadge">No Change</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-uppercase text-body-secondary mb-1">Disabled Modules</div>
                    <div id="impactDisabledModules" class="d-flex flex-wrap gap-2"></div>
                </div>
                <div class="col-md-6">
                    <div class="small text-uppercase text-body-secondary mb-1">Enabled Modules</div>
                    <div id="impactEnabledModules" class="d-flex flex-wrap gap-2"></div>
                </div>
                <div class="col-12">
                    <div class="small text-uppercase text-body-secondary mb-1">Affected Role Counts</div>
                    <div class="d-flex flex-wrap gap-2" id="impactRoleCounts"></div>
                </div>
                <div class="col-12">
                    <div class="small text-uppercase text-body-secondary mb-1">Approval Changes</div>
                    <div id="impactApprovalChanges" class="small text-body-secondary"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card app-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Current Usage Snapshot</h2>
                            <p class="text-body-secondary mb-0">Provisioned users and student volume against the current plan setup.</p>
                        </div>
                        <span class="badge text-bg-primary fs-6 px-3 py-2">{{ $license->planLabel() }}</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6 col-xl-3">
                            <div class="rounded border p-3 h-100 {{ $studentExceeded ? 'border-danger bg-danger-subtle' : 'bg-body-tertiary' }}">
                                <div class="small text-uppercase text-body-secondary mb-1">Students</div>
                                <div class="fs-4 fw-semibold">{{ $usageSummary['students'] }}</div>
                                <div class="small text-body-secondary">Limit: {{ $studentsLimit ? number_format($studentsLimit) : 'Unlimited' }}</div>
                                @if ($studentExceeded)
                                    <div class="small text-danger fw-semibold mt-1">Over limit by {{ number_format($usageSummary['students'] - $studentsLimit) }}</div>
                                @endif
                            </div>
                        </div>
                        @foreach (['admin' => 'Admin Users', 'hr' => 'HR Users', 'teacher' => 'Teacher Users'] as $role => $label)
                            @php
                                $roleLimit = $license->limitForRole($role);
                                $roleExceeded = $roleLimit && ($usageSummary[$role] > $roleLimit);
                            @endphp
                            <div class="col-sm-6 col-xl-3">
                                <div class="rounded border p-3 h-100 {{ $roleExceeded ? 'border-danger bg-danger-subtle' : 'bg-body-tertiary' }}">
                                    <div class="small text-uppercase text-body-secondary mb-1">{{ $label }}</div>
                                    <div class="fs-4 fw-semibold">{{ $usageSummary[$role] }}</div>
                                    <div class="small text-body-secondary">Limit: {{ $roleLimit ? number_format($roleLimit) : 'Unlimited' }}</div>
                                    @if ($roleExceeded)
                                        <div class="small text-danger fw-semibold mt-1">Over limit by {{ number_format($usageSummary[$role] - $roleLimit) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @foreach ($availablePlans as $plan)
            <div class="col-xl-4 col-md-6">
                <div class="card app-card h-100 border {{ $license->planKey() === $plan['key'] ? 'border-primary shadow-sm' : 'border-light-subtle' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <h2 class="h5 mb-0">{{ $plan['label'] }}</h2>
                            <div class="d-flex align-items-center gap-2">
                                @if (($recommendedPlan['key'] ?? null) === $plan['key'])
                                    <span class="badge text-bg-success">Recommended</span>
                                @endif
                                <span class="badge {{ $license->planKey() === $plan['key'] ? 'text-bg-primary' : 'text-bg-light border' }}">{{ count($plan['modules']) }} modules</span>
                            </div>
                        </div>
                        <p class="text-body-secondary small mb-3">{{ $plan['description'] }}</p>
                        <div class="small text-body-secondary mb-2">Student limit: {{ $plan['student_limit'] ? number_format($plan['student_limit']) : 'Unlimited' }}</div>
                        <div class="small text-body-secondary mb-2">Role caps: Admin {{ $plan['role_limits']['admin'] ?? 'Unlimited' }}, HR {{ $plan['role_limits']['hr'] ?? 'Unlimited' }}, Teacher {{ $plan['role_limits']['teacher'] ?? 'Unlimited' }}</div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach ($plan['modules'] as $module)
                                <span class="badge rounded-pill text-bg-light border">{{ $module }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card app-card border-0 shadow-sm">
        <div class="card-body p-4">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('license-settings.update') }}" class="row g-4" id="masterControlForm">
                @csrf
                <input type="hidden" id="appNameSeed" value="{{ config('app.name', 'SchoolERP') }}">
                <input type="hidden" id="generateLicenseKeyUrl" value="{{ route('license-settings.generate-key') }}">
                <input type="hidden" id="impactPreviewUrl" value="{{ route('license-settings.impact-preview') }}">

                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div>
                            <h2 class="h4 mb-1">Master Control Settings</h2>
                            <p class="text-body-secondary mb-0">Choose a SaaS plan, then fine-tune modules, approvals, and role-wise user capacity.</p>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="applyPlanPresetBtn">Apply Selected Plan Preset</button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">SaaS Plan</label>
                    <select class="form-select @error('plan_name') is-invalid @enderror" id="planNameSelect" name="plan_name">
                        @foreach ($availablePlans as $plan)
                            <option value="{{ $plan['label'] }}" {{ old('plan_name', $license->planLabel()) === $plan['label'] ? 'selected' : '' }}>{{ $plan['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Starter, Professional, and Enterprise apply different defaults for modules and user caps.</div>
                    @error('plan_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-4">
                    <label class="form-label">License Key</label>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control @error('license_key') is-invalid @enderror"
                            id="licenseKeyInput"
                            name="license_key"
                            value="{{ old('license_key', $license->license_key ?? '') }}"
                            placeholder="LIC-XXXX-XXXX"
                        >
                        <button type="button" class="btn btn-outline-secondary" id="generateLicenseKeyBtn">Auto Generate</button>
                    </div>
                    <div class="form-text">Generate a plan-coded unique key for this tenant instance.</div>
                    @error('license_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" class="form-control @error('expires_at') is-invalid @enderror" name="expires_at" value="{{ old('expires_at', optional($license?->expires_at)->format('Y-m-d')) }}">
                    @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Student Limit</label>
                    <input type="number" min="1" class="form-control @error('student_limit') is-invalid @enderror" id="studentLimitInput" name="student_limit" value="{{ old('student_limit', $license->student_limit ?? '') }}" placeholder="Blank = use plan default / unlimited">
                    <div class="form-text">Blank keeps the plan default. Set a value to enforce a custom ceiling.</div>
                    @error('student_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $license->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Tenant Active</label>
                    </div>
                </div>

                <div class="col-12"><hr class="my-0"></div>

                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div>
                            <h2 class="h5 mb-1">Approval Controls</h2>
                            <p class="text-body-secondary mb-0">Turn approval workflows on or off for self-service flows.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="rounded border p-3 h-100 bg-body-tertiary">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="approvalLeaveRequests" name="approval_settings[leave_requests]" value="1" {{ old('approval_settings.leave_requests', $license->approvalRequired('leave_requests')) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="approvalLeaveRequests">Leave Requests Require Approval</label>
                        </div>
                        <div class="small text-body-secondary mt-2">Keep staff and student leave requests in a controlled approval queue.</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="rounded border p-3 h-100 bg-body-tertiary">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="approvalStudentCalendarMappings" name="approval_settings[student_calendar_mappings]" value="1" {{ old('approval_settings.student_calendar_mappings', $license->approvalRequired('student_calendar_mappings')) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="approvalStudentCalendarMappings">Student Calendar Mapping Requires Approval</label>
                        </div>
                        <div class="small text-body-secondary mt-2">If off, student holiday mapping is approved automatically in the portal.</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="rounded border p-3 h-100 bg-body-tertiary">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="approvalAdmissionDuplicateStrict" name="approval_settings[admission_duplicate_strict]" value="1" {{ old('approval_settings.admission_duplicate_strict', $license->admissionDuplicateStrict()) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="approvalAdmissionDuplicateStrict">Admission Duplicate Strict Mode</label>
                        </div>
                        <div class="small text-body-secondary mt-2">If on, conversion is blocked on duplicate detection until operator confirms or links existing student.</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="rounded border p-3 bg-body-tertiary">
                        <h3 class="h6 mb-3">Admission Kanban WIP Limits</h3>
                        <p class="small text-body-secondary mb-3">Configure stage-wise work-in-progress caps used for drag/drop warning checks in Admission CRM Kanban.</p>
                        <div class="row g-3">
                            @foreach (['new' => 'New', 'contacted' => 'Contacted', 'counselling_scheduled' => 'Counselling Scheduled', 'counselling_done' => 'Counselling Done', 'follow_up' => 'Follow Up', 'converted' => 'Converted', 'lost' => 'Lost'] as $stageKey => $stageLabel)
                                <div class="col-md-6 col-xl-3">
                                    <label class="form-label small">{{ $stageLabel }} Limit</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="5000"
                                        class="form-control @error('approval_settings.admission_wip_limits.'.$stageKey) is-invalid @enderror"
                                        id="wipLimit{{ \Illuminate\Support\Str::studly($stageKey) }}"
                                        name="approval_settings[admission_wip_limits][{{ $stageKey }}]"
                                        value="{{ old('approval_settings.admission_wip_limits.'.$stageKey, $admissionWipLimits[$stageKey] ?? null) }}"
                                    >
                                    @error('approval_settings.admission_wip_limits.'.$stageKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12"><hr class="my-0"></div>

                <div class="col-12">
                    <h2 class="h5 mb-1">User Controls</h2>
                    <p class="text-body-secondary mb-0">Set how many provisioned role accounts this tenant can create.</p>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Admin Users Limit</label>
                    <input type="number" min="1" class="form-control @error('role_limits.admin') is-invalid @enderror" id="roleLimitAdmin" name="role_limits[admin]" value="{{ old('role_limits.admin', $roleLimits['admin'] ?? '') }}" placeholder="Blank = unlimited">
                    @error('role_limits.admin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">HR Users Limit</label>
                    <input type="number" min="1" class="form-control @error('role_limits.hr') is-invalid @enderror" id="roleLimitHr" name="role_limits[hr]" value="{{ old('role_limits.hr', $roleLimits['hr'] ?? '') }}" placeholder="Blank = unlimited">
                    @error('role_limits.hr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Teacher Users Limit</label>
                    <input type="number" min="1" class="form-control @error('role_limits.teacher') is-invalid @enderror" id="roleLimitTeacher" name="role_limits[teacher]" value="{{ old('role_limits.teacher', $roleLimits['teacher'] ?? '') }}" placeholder="Blank = unlimited">
                    @error('role_limits.teacher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><hr class="my-0"></div>

                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                        <div>
                            <h2 class="h5 mb-1">Module Controls</h2>
                            <p class="text-body-secondary mb-0">Only checked modules remain available across the tenant.</p>
                        </div>
                        <div class="small text-body-secondary">Selected: <span id="selectedModuleCount">{{ count($selectedModules) }}</span> / {{ count($moduleLabels) }}</div>
                    </div>
                    @error('enabled_modules')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                </div>

                @foreach ($moduleLabels as $module => $label)
                    <div class="col-md-6 col-xl-4">
                        <label class="rounded border p-3 h-100 w-100 bg-body-tertiary d-flex align-items-start gap-3">
                            <input class="form-check-input mt-1 module-control-checkbox" type="checkbox" name="enabled_modules[]" value="{{ $module }}" {{ in_array($module, $selectedModules, true) ? 'checked' : '' }}>
                            <span>
                                <span class="fw-semibold d-block">{{ $label }}</span>
                                <span class="small text-body-secondary">Permission key: {{ $module }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3" placeholder="Operational notes, renewals, internal SaaS remarks">{{ old('notes', $license->notes ?? '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save Master Control</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<script type="application/json" id="master-control-plan-json">@json(array_values($availablePlans))</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const keyInput = document.getElementById('licenseKeyInput');
    const planInput = document.getElementById('planNameSelect');
    const trigger = document.getElementById('generateLicenseKeyBtn');
    const applyPlanPresetBtn = document.getElementById('applyPlanPresetBtn');
    const applyRecommendedPlanBtn = document.getElementById('applyRecommendedPlanBtn');
    const previewImpactBtn = document.getElementById('previewImpactBtn');
    const rollbackLastBtn = document.getElementById('rollbackLastBtn');
    const rollbackMasterControlForm = document.getElementById('rollbackMasterControlForm');
    const impactPreviewPanel = document.getElementById('impactPreviewPanel');
    const impactPlanChangeBadge = document.getElementById('impactPlanChangeBadge');
    const impactDisabledModules = document.getElementById('impactDisabledModules');
    const impactEnabledModules = document.getElementById('impactEnabledModules');
    const impactRoleCounts = document.getElementById('impactRoleCounts');
    const impactApprovalChanges = document.getElementById('impactApprovalChanges');
    const impactPreviewUrl = document.getElementById('impactPreviewUrl')?.value || '';
    const quickFixPlanButtons = Array.from(document.querySelectorAll('[data-quick-fix-plan]'));
    const quickFixStudentButtons = Array.from(document.querySelectorAll('[data-quick-fix-student-limit]'));
    const quickFixRoleButtons = Array.from(document.querySelectorAll('[data-quick-fix-role]'));

    const notifyQuickFix = function (message, type) {
        const iconType = type || 'success';
        if (typeof window.showToast === 'function') {
            window.showToast(iconType, message);
            return;
        }

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: iconType,
                title: message,
                timer: 1800,
                showConfirmButton: false,
            });
        }
    };
    const moduleCheckboxes = Array.from(document.querySelectorAll('.module-control-checkbox'));
    const selectedModuleCount = document.getElementById('selectedModuleCount');
    const planPresets = JSON.parse(document.getElementById('master-control-plan-json')?.textContent || '[]');
    const planMap = planPresets.reduce(function (carry, plan) {
        carry[plan.label] = plan;
        return carry;
    }, {});

    const updateSelectedModuleCount = function () {
        if (!selectedModuleCount) {
            return;
        }

        selectedModuleCount.textContent = String(moduleCheckboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length);
    };

    const applyPreset = function () {
        if (!planInput) {
            return;
        }

        const preset = planMap[planInput.value];
        if (!preset) {
            return;
        }

        const studentLimitInput = document.getElementById('studentLimitInput');
        const roleLimitAdmin = document.getElementById('roleLimitAdmin');
        const roleLimitHr = document.getElementById('roleLimitHr');
        const roleLimitTeacher = document.getElementById('roleLimitTeacher');
        const approvalLeaveRequests = document.getElementById('approvalLeaveRequests');
        const approvalStudentCalendarMappings = document.getElementById('approvalStudentCalendarMappings');
        const approvalAdmissionDuplicateStrict = document.getElementById('approvalAdmissionDuplicateStrict');

        if (studentLimitInput) {
            studentLimitInput.value = preset.student_limit || '';
        }

        if (roleLimitAdmin) {
            roleLimitAdmin.value = preset.role_limits.admin || '';
        }

        if (roleLimitHr) {
            roleLimitHr.value = preset.role_limits.hr || '';
        }

        if (roleLimitTeacher) {
            roleLimitTeacher.value = preset.role_limits.teacher || '';
        }

        if (approvalLeaveRequests) {
            approvalLeaveRequests.checked = !!preset.approval_settings.leave_requests;
        }

        if (approvalStudentCalendarMappings) {
            approvalStudentCalendarMappings.checked = !!preset.approval_settings.student_calendar_mappings;
        }

        if (approvalAdmissionDuplicateStrict) {
            approvalAdmissionDuplicateStrict.checked = !!preset.approval_settings.admission_duplicate_strict;
        }

        const wipLimitMap = (preset.approval_settings && preset.approval_settings.admission_wip_limits) || {};
        Object.keys(wipLimitMap).forEach(function (stageKey) {
            const fieldId = 'wipLimit' + stageKey.split('_').map(function (part) {
                return part.charAt(0).toUpperCase() + part.slice(1);
            }).join('');
            const input = document.getElementById(fieldId);
            if (input) {
                input.value = wipLimitMap[stageKey] || '';
            }
        });

        moduleCheckboxes.forEach(function (checkbox) {
            checkbox.checked = preset.modules.indexOf(checkbox.value) !== -1;
        });

        updateSelectedModuleCount();
    };

    moduleCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateSelectedModuleCount);
    });
    updateSelectedModuleCount();

    if (applyPlanPresetBtn) {
        applyPlanPresetBtn.addEventListener('click', applyPreset);
    }

    if (applyRecommendedPlanBtn && planInput) {
        applyRecommendedPlanBtn.addEventListener('click', function () {
            const recommendedLabel = applyRecommendedPlanBtn.getAttribute('data-recommended-plan');
            if (!recommendedLabel || !planMap[recommendedLabel]) {
                return;
            }

            planInput.value = recommendedLabel;
            applyPreset();
        });
    }

    quickFixPlanButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const planLabel = button.getAttribute('data-quick-fix-plan');
            if (!planInput || !planLabel || !planMap[planLabel]) {
                return;
            }

            planInput.value = planLabel;
            applyPreset();
            notifyQuickFix('Applied plan quick fix: ' + planLabel + '. Save to persist changes.');
        });
    });

    quickFixStudentButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const value = button.getAttribute('data-quick-fix-student-limit');
            const studentLimitInput = document.getElementById('studentLimitInput');
            if (!studentLimitInput || !value) {
                return;
            }

            studentLimitInput.value = value;
            notifyQuickFix('Student limit prefilled to ' + value + '. Save to persist changes.');
        });
    });

    quickFixRoleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const role = button.getAttribute('data-quick-fix-role');
            const value = button.getAttribute('data-quick-fix-role-value');
            if (!role || !value) {
                return;
            }

            const targetInput = role === 'admin'
                ? document.getElementById('roleLimitAdmin')
                : role === 'hr'
                    ? document.getElementById('roleLimitHr')
                    : document.getElementById('roleLimitTeacher');

            if (targetInput) {
                targetInput.value = value;
                notifyQuickFix((role || '').toUpperCase() + ' limit prefilled to ' + value + '. Save to persist changes.');
            }
        });
    });

    if (rollbackLastBtn && rollbackMasterControlForm) {
        rollbackLastBtn.addEventListener('click', async function () {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                const result = await window.Swal.fire({
                    icon: 'warning',
                    title: 'Rollback last master control change?',
                    text: 'This will restore the previous snapshot state.',
                    showCancelButton: true,
                    confirmButtonText: 'Rollback',
                    cancelButtonText: 'Cancel',
                });

                if (!result.isConfirmed) {
                    return;
                }
            }

            rollbackMasterControlForm.submit();
        });
    }

    if (previewImpactBtn && impactPreviewUrl) {
        previewImpactBtn.addEventListener('click', async function () {
            const form = document.getElementById('masterControlForm');
            if (!form) {
                return;
            }

            const payload = new FormData(form);

            try {
                const response = await fetch(impactPreviewUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: payload,
                });

                const data = await response.json();
                if (!response.ok || !data.impact) {
                    notifyQuickFix('Unable to generate impact preview.', 'error');
                    return;
                }

                const impact = data.impact;
                const fromPlan = impact.plan_from || 'Current';
                const toPlan = impact.plan_to || 'Preview';

                impactPlanChangeBadge.textContent = fromPlan + ' -> ' + toPlan;
                impactPlanChangeBadge.className = 'badge text-bg-info';

                const disabled = Array.isArray(impact.disabled_modules) ? impact.disabled_modules : [];
                const enabled = Array.isArray(impact.enabled_modules) ? impact.enabled_modules : [];
                impactDisabledModules.innerHTML = disabled.length
                    ? disabled.map(function (item) { return '<span class="badge text-bg-danger">' + item + '</span>'; }).join('')
                    : '<span class="small text-body-secondary">No module will be disabled.</span>';
                impactEnabledModules.innerHTML = enabled.length
                    ? enabled.map(function (item) { return '<span class="badge text-bg-success">' + item + '</span>'; }).join('')
                    : '<span class="small text-body-secondary">No new module will be enabled.</span>';

                const roleCounts = impact.affected_role_counts || {};
                impactRoleCounts.innerHTML = [
                    'Admin: ' + (roleCounts.admin || 0),
                    'HR: ' + (roleCounts.hr || 0),
                    'Teacher: ' + (roleCounts.teacher || 0),
                ].map(function (item) {
                    return '<span class="badge text-bg-light border">' + item + '</span>';
                }).join('');

                const approval = impact.approval_changes || {};
                const leaveFrom = approval.leave_requests && approval.leave_requests.from ? 'ON' : 'OFF';
                const leaveTo = approval.leave_requests && approval.leave_requests.to ? 'ON' : 'OFF';
                const calendarFrom = approval.student_calendar_mappings && approval.student_calendar_mappings.from ? 'ON' : 'OFF';
                const calendarTo = approval.student_calendar_mappings && approval.student_calendar_mappings.to ? 'ON' : 'OFF';
                const duplicateStrictFrom = approval.admission_duplicate_strict && approval.admission_duplicate_strict.from ? 'ON' : 'OFF';
                const duplicateStrictTo = approval.admission_duplicate_strict && approval.admission_duplicate_strict.to ? 'ON' : 'OFF';
                impactApprovalChanges.textContent =
                    'Leave approvals: ' + leaveFrom + ' -> ' + leaveTo
                    + ' | Student calendar approvals: ' + calendarFrom + ' -> ' + calendarTo
                    + ' | Admission duplicate strict: ' + duplicateStrictFrom + ' -> ' + duplicateStrictTo;

                impactPreviewPanel.classList.remove('d-none');
                notifyQuickFix('Impact preview generated.', 'info');
            } catch (error) {
                notifyQuickFix('Unable to generate impact preview.', 'error');
            }
        });
    }

    if (!keyInput || !trigger) {
        return;
    }

    const appName = document.getElementById('appNameSeed')?.value || 'SchoolERP';
    const generateUrl = document.getElementById('generateLicenseKeyUrl')?.value || '';
    

    const normalizeChunk = function (value, fallback, length) {
        const cleaned = String(value || '')
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
        const source = cleaned || fallback;

        return source.slice(0, length).padEnd(length, 'X');
    };

    const randomChunk = function (length) {
        const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let output = '';

        for (let i = 0; i < length; i += 1) {
            output += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        }

        return output;
    };

    trigger.addEventListener('click', async function () {
        trigger.disabled = true;
        trigger.textContent = 'Generating...';

        try {
            if (generateUrl) {
                const response = await fetch(generateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        app_name: appName,
                        plan_name: planInput ? planInput.value : '',
                    }),
                });

                if (response.ok) {
                    const payload = await response.json();
                    if (payload.license_key) {
                        keyInput.value = payload.license_key;
                        return;
                    }
                }
            }
        } catch (error) {
            // Fallback to client-side generation if API call fails.
        } finally {
            trigger.disabled = false;
            trigger.textContent = 'Auto Generate';
        }

        const now = new Date();
        const year = String(now.getFullYear());
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const appChunk = normalizeChunk(appName, 'SCHOOL', 6);
        const planChunk = normalizeChunk(planInput ? planInput.value : '', 'STD', 3);

        keyInput.value = [
            appChunk,
            planChunk,
            year,
            month,
            randomChunk(4),
            randomChunk(4),
        ].join('-');
    });
});
</script>
