@extends('layouts.app')

@section('content')
@php
    use App\Models\BiometricDevice;
    $statusColors  = ['active' => 'success', 'inactive' => 'secondary'];
    $forColors     = ['student' => 'info', 'staff' => 'warning'];
    $fingerLabels  = ['0'=>'Right Thumb','1'=>'Right Index','2'=>'Right Middle','3'=>'Right Ring','4'=>'Right Pinky','5'=>'Left Thumb','6'=>'Left Index','7'=>'Left Middle','8'=>'Left Ring','9'=>'Left Pinky'];
    $devices       = BiometricDevice::orderBy('device_name')->get();
    $filterDevice  = request()->query('device_id');
    $filteredRecs  = $filterDevice
        ? collect($records)->where('biometric_device_id', (int)$filterDevice)->values()
        : collect($records);
    $studentCount  = collect($records)->where('enrollment_for', 'student')->count();
    $staffCount    = collect($records)->where('enrollment_for', 'staff')->count();
    $activeCount   = collect($records)->where('status', 'active')->count();
@endphp

<div class="container-fluid px-4 py-4" data-module-page data-module="biometric-enrollments">

    {{-- ── Header ── --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Biometrics</span>
            <h1 class="h2 mb-1"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Biometric Enrollments</h1>
            <p class="text-body-secondary mb-0">Map machine Punch IDs to students and staff for automated attendance capture.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('biometric-devices.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-hdd-network"></i> Manage Devices
            </a>
            <button class="btn btn-primary" type="button" data-open-create-modal>
                <i class="bi bi-plus-circle"></i> New Enrollment
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('biometric-enrollments.export.excel') }}">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export
            </a>
        </div>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary fs-4"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ count($records) }}</div>
                        <div class="small text-body-secondary">Total Enrollments</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info fs-4"><i class="bi bi-mortarboard"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $studentCount }}</div>
                        <div class="small text-body-secondary">Students</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning fs-4"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $staffCount }}</div>
                        <div class="small text-body-secondary">Staff Members</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success fs-4"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="fs-3 fw-bold text-success">{{ $activeCount }}</div>
                        <div class="small text-body-secondary">Active</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── How It Works Banner ── --}}
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-md-row flex-column align-items-md-center gap-3 py-3">
            <i class="bi bi-info-circle-fill text-primary fs-3 flex-shrink-0"></i>
            <div class="small text-body-secondary">
                <strong class="text-body">How punch IDs work:</strong>
                Each person enrolled in a biometric machine has an internal <strong>Punch ID</strong> (a number like <code>1042</code>).
                When the machine sends an attendance punch to the API it includes this Punch ID and the Device Code.
                This enrollment record maps that combination to the correct student or staff record.
                If no enrollment match is found, the API falls back to matching by admission/employee number.
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center py-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold text-nowrap">Filter by Device:</label>
                <select class="form-select form-select-sm" id="deviceFilter" style="min-width:200px;">
                    <option value="">All Devices</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ (int)$filterDevice === $dev->id ? 'selected' : '' }}>
                            {{ $dev->device_name }} ({{ $dev->device_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <input type="search" class="form-control form-control-sm" id="enrollSearch"
                    placeholder="Search name or punch ID…" style="min-width:200px;">
                <button class="btn btn-sm btn-primary" type="button" data-open-create-modal>
                    <i class="bi bi-plus-circle"></i> Add
                </button>
            </div>
        </div>
    </div>

    {{-- ── Enrollments Table ── --}}
    @if(count($records) > 0)
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="enrollTable">
                <thead class="table-light">
                    <tr>
                        <th>Person</th>
                        <th>Punch ID</th>
                        <th>Device</th>
                        <th>Type</th>
                        <th>Finger</th>
                        <th>Status</th>
                        <th>Enrolled</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filteredRecs as $enroll)
                    @php
                        $person = null;
                        $personSub = null;
                        if ($enroll->enrollment_for === 'student' && $enroll->student) {
                            $person = $enroll->student->full_name ?? ($enroll->student->first_name . ' ' . $enroll->student->last_name);
                            $personSub = 'Adm: ' . ($enroll->student->admission_no ?? '—');
                        } elseif ($enroll->staff) {
                            $person = $enroll->staff->full_name ?? ($enroll->staff->first_name . ' ' . $enroll->staff->last_name);
                            $personSub = 'Emp: ' . ($enroll->staff->employee_id ?? '—');
                        }
                        $devName = $enroll->device->device_name ?? '—';
                        $devCode = $enroll->device->device_code ?? '';
                    @endphp
                    <tr data-enroll-row>
                        <td>
                            <div class="fw-semibold" data-search-name>{{ $person ?? '—' }}</div>
                            <div class="small text-body-secondary" data-search-sub>{{ $personSub }}</div>
                        </td>
                        <td><code class="badge bg-dark text-white fs-6 fw-bold">{{ $enroll->punch_id }}</code></td>
                        <td data-device-id="{{ $enroll->biometric_device_id }}">
                            <div class="fw-semibold">{{ $devName }}</div>
                            <code class="small text-body-secondary">{{ $devCode }}</code>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $forColors[$enroll->enrollment_for] ?? 'secondary' }} text-capitalize">
                                {{ $enroll->enrollment_for }}
                            </span>
                        </td>
                        <td>{{ $fingerLabels[$enroll->finger_index] ?? ($enroll->finger_index ?? '—') }}</td>
                        <td>
                            <span class="badge text-bg-{{ $statusColors[$enroll->status] ?? 'secondary' }} text-capitalize">
                                {{ $enroll->status }}
                            </span>
                        </td>
                        <td class="small text-body-secondary">
                            {{ $enroll->enrolled_at ? \Carbon\Carbon::parse($enroll->enrolled_at)->format('d M Y') : '—' }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-edit-record data-module="biometric-enrollments" data-id="{{ $enroll->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    data-delete-record data-module="biometric-enrollments" data-id="{{ $enroll->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Pagination ── --}}
        @if(!empty($pagination) && $pagination['last_page'] > 1)
        <div class="card-footer border-0 d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    @if($pagination['current_page'] > 1)
                        <li class="page-item"><a class="page-link" href="#" data-page="1" data-pagination-link>First</a></li>
                        <li class="page-item"><a class="page-link" href="#" data-page="{{ $pagination['current_page'] - 1 }}" data-pagination-link>Prev</a></li>
                    @endif
                    @for($p = max(1,$pagination['current_page']-2); $p <= min($pagination['last_page'],$pagination['current_page']+2); $p++)
                        <li class="page-item {{ $p === $pagination['current_page'] ? 'active' : '' }}">
                            <a class="page-link" href="#" data-page="{{ $p }}" data-pagination-link>{{ $p }}</a>
                        </li>
                    @endfor
                    @if($pagination['current_page'] < $pagination['last_page'])
                        <li class="page-item"><a class="page-link" href="#" data-page="{{ $pagination['current_page'] + 1 }}" data-pagination-link>Next</a></li>
                        <li class="page-item"><a class="page-link" href="#" data-page="{{ $pagination['last_page'] }}" data-pagination-link>Last</a></li>
                    @endif
                </ul>
            </nav>
        </div>
        @endif
    </div>

    {{-- Hidden table wrapper for JS CRUD (used by initModuleCrud) ── --}}
    <div data-module-table-wrapper class="d-none">
        @include('modules.table', ['records' => $records, 'moduleConfig' => $moduleConfig, 'moduleKey' => $moduleKey, 'pagination' => $pagination ?? null])
    </div>

    @else
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-person-lines-fill display-4 text-body-secondary mb-3 d-block"></i>
            <h2 class="h5">No Enrollments Yet</h2>
            <p class="text-body-secondary mb-4">
                Enroll students and staff by mapping their biometric machine Punch IDs here.
                Make sure you have at least one <a href="{{ route('biometric-devices.index') }}">Biometric Device</a> added first.
            </p>
            <button class="btn btn-primary" type="button" data-open-create-modal>
                <i class="bi bi-plus-circle me-1"></i> Add First Enrollment
            </button>
        </div>
    </div>
    <div data-module-table-wrapper class="d-none">
        @include('modules.table', ['records' => $records, 'moduleConfig' => $moduleConfig, 'moduleKey' => $moduleKey, 'pagination' => $pagination ?? null])
    </div>
    @endif

    {{-- ── CRUD Modal ── --}}
    <div class="modal fade" id="moduleCrudModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="eyebrow">Biometric Enrollment</span>
                        <h2 class="h4 mb-0" data-modal-title>Add Enrollment</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert d-none" data-form-alert></div>
                    <form data-module-form enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" value="POST" data-form-method>
                        <div class="row g-3">
                            @foreach ($moduleConfig['fields'] as $field)
                                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}" data-field="{{ $field['name'] }}">
                                    <label class="form-label fw-semibold" for="field_{{ $field['name'] }}">
                                        {{ $field['label'] }}
                                        @if(!empty($field['required'])) <span class="text-danger">*</span> @endif
                                    </label>
                                    @if($field['type'] === 'textarea')
                                        <textarea class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}" rows="3"></textarea>
                                    @elseif($field['type'] === 'select')
                                        <select class="form-select" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}">
                                            <option value="">Select {{ $field['label'] }}</option>
                                            @foreach(($field['lookup'] ?? null) ? ($lookups[$field['lookup']] ?? []) : ($field['options'] ?? []) as $val => $lbl)
                                                <option value="{{ $val }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}">
                                    @endif
                                    @if(!empty($field['help']))
                                        <div class="form-text text-body-secondary small">{{ $field['help'] }}</div>
                                    @endif
                                    <div class="invalid-feedback d-block small" data-error-for="{{ $field['name'] }}"></div>
                                </div>
                            @endforeach
                        </div>
                    </form>

                    <div class="alert alert-info border-0 mt-3 small">
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Set <em>Enroll For</em> to <strong>Student</strong> to show the Student field,
                        or <strong>Staff</strong> to show the Staff field. The <em>Punch ID</em> must exactly match the
                        numeric ID stored in the machine for this person (check machine admin panel).
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-submit-module-form>
                        <i class="bi bi-check-circle me-1"></i> Save Enrollment
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="application/json" id="module-config-json">@json($moduleConfig)</script>
<script type="application/json" id="module-lookups-json">@json($lookups)</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Device filter — reload with query param
    const deviceFilter = document.getElementById('deviceFilter');
    if (deviceFilter) {
        deviceFilter.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) url.searchParams.set('device_id', this.value);
            else url.searchParams.delete('device_id');
            window.location.href = url.toString();
        });
    }

    // Client-side search across person name / sub / punch ID
    const searchInput = document.getElementById('enrollSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('[data-enroll-row]').forEach(function (row) {
                const name = (row.querySelector('[data-search-name]')?.textContent || '').toLowerCase();
                const sub  = (row.querySelector('[data-search-sub]')?.textContent  || '').toLowerCase();
                const code = (row.querySelector('code')?.textContent || '').toLowerCase();
                row.style.display = (!q || name.includes(q) || sub.includes(q) || code.includes(q)) ? '' : 'none';
            });
        });
    }

    // After CRUD refresh reload page to update table
    const tableWrapper = document.querySelector('[data-module-table-wrapper]');
    if (tableWrapper) {
        const observer = new MutationObserver(function () { window.location.reload(); });
        observer.observe(tableWrapper, { childList: true, subtree: true });
    }

    // Toggle student_id / staff_id fields based on enrollment_for select
    const enrollForSel = document.getElementById('field_enrollment_for');
    if (enrollForSel) {
        function syncEnrollFields() {
            const val = enrollForSel.value;
            const studentWrap = document.querySelector('[data-field="student_id"]');
            const staffWrap   = document.querySelector('[data-field="staff_id"]');
            if (studentWrap) studentWrap.style.display = (val === 'student' || !val) ? '' : 'none';
            if (staffWrap)   staffWrap.style.display   = (val === 'staff'   || !val) ? '' : 'none';
        }
        enrollForSel.addEventListener('change', syncEnrollFields);
        syncEnrollFields();
    }
});
</script>
@endpush
@endsection
