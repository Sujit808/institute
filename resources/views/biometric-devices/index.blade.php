@extends('layouts.app')

@section('content')
@php
    use App\Models\BiometricEnrollment;
    $statusColors  = ['active' => 'success', 'inactive' => 'secondary', 'maintenance' => 'warning'];
    $typeIcons     = ['fingerprint' => 'bi-fingerprint', 'face' => 'bi-person-bounding-box', 'card' => 'bi-credit-card-2-front', 'multi' => 'bi-layers'];
    $typeLabels    = ['fingerprint' => 'Fingerprint', 'face' => 'Face Recognition', 'card' => 'RFID / Card', 'multi' => 'Multi-Modal'];
    $methodColors  = ['push_api' => 'primary', 'pull_sdk' => 'info', 'adms' => 'warning'];
    $methodLabels  = ['push_api' => 'Push API', 'pull_sdk' => 'Pull SDK', 'adms' => 'ADMS'];
    $totalDevices  = count($records);
    $activeDevices = collect($records)->where('status', 'active')->count();
    $totalEnrolled = BiometricEnrollment::count();
    $webhookToken  = config('services.attendance_integration.webhook_token', '');
@endphp

<div class="container-fluid px-4 py-4" data-module-page data-module="biometric-devices">

    {{-- ── Header ── --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Biometrics &amp; Integration</span>
            <h1 class="h2 mb-1"><i class="bi bi-fingerprint me-2 text-primary"></i>Biometric Devices</h1>
            <p class="text-body-secondary mb-0">Register machines, assign Punch IDs to students/staff, and sync attendance automatically.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('biometric-enrollments.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-person-lines-fill"></i> Manage Enrollments
            </a>
            <button class="btn btn-primary" type="button" data-open-create-modal>
                <i class="bi bi-plus-circle"></i> Add Device
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('biometric-devices.export.excel') }}">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export
            </a>
        </div>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary fs-4"><i class="bi bi-hdd-network"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $totalDevices }}</div>
                        <div class="small text-body-secondary">Total Devices</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success fs-4"><i class="bi bi-activity"></i></div>
                    <div>
                        <div class="fs-3 fw-bold text-success">{{ $activeDevices }}</div>
                        <div class="small text-body-secondary">Active Devices</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info fs-4"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $totalEnrolled }}</div>
                        <div class="small text-body-secondary">Total Enrollments</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning fs-4"><i class="bi bi-plug"></i></div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $webhookToken ? 'Set' : 'Not Set' }}</div>
                        <div class="small text-body-secondary">API Token</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── API Integration Info ── --}}
    @if (!$webhookToken)
    <div class="alert alert-warning border-0 shadow-sm d-flex gap-3 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0 mt-1"></i>
        <div>
            <strong>ATTENDANCE_WEBHOOK_TOKEN is not set.</strong> Biometric machines cannot push data without it.
            Add <code>ATTENDANCE_WEBHOOK_TOKEN=your_secret_here</code> to your <code>.env</code> file and restart the server.
        </div>
    </div>
    @endif

    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-header border-0 pb-0 pt-3 px-3 d-flex align-items-center justify-content-between">
            <h2 class="h6 mb-0 fw-bold"><i class="bi bi-plug-fill me-2 text-primary"></i>API Integration Setup</h2>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#apiGuide">
                <i class="bi bi-chevron-down"></i> Toggle
            </button>
        </div>
        <div class="collapse show" id="apiGuide">
            <div class="card-body pt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="small text-body-secondary mb-2">
                            <strong>Machine Punch URL</strong> — set this as the "Server URL" in your biometric machine settings:
                        </p>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-globe2"></i></span>
                            <input type="text" class="form-control font-monospace small" id="punchUrl"
                                value="{{ rtrim(config('app.url'), '/') }}/api/attendance/punch" readonly>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('punchUrl').value)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <p class="small text-body-secondary mt-3 mb-2">
                            <strong>Required Header</strong> — add this to every request:
                        </p>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-monospace">X-Attendance-Token</span>
                            <input type="password" class="form-control font-monospace small" id="webhookToken"
                                value="{{ $webhookToken ?: '(not configured)' }}" readonly>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="var el=document.getElementById('webhookToken');el.type=el.type==='password'?'text':'password'">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="small text-body-secondary mb-2"><strong>Sample JSON Payload (POST)</strong></p>
                        <pre class="bg-dark text-success rounded-3 p-3 small mb-0" style="font-size:0.72rem;overflow-x:auto;">{
  "attendance_for":    "student",
  "attendance_method": "biometric_machine",
  "external_user_code": "1042",
  "biometric_device_id": "BIO-MG-01",
  "attendance_date":   "{{ now()->toDateString() }}",
  "captured_at":       "{{ now()->toIso8601String() }}",
  "status":            "present",
  "biometric_log_id":  "LOG-9987"
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Device Cards Grid ── --}}
    @if(count($records) > 0)
    <div class="row g-3 mb-4" id="device-cards-grid">
        @foreach($records as $device)
        @php
            $enrollCount = \App\Models\BiometricEnrollment::where('biometric_device_id', $device->id)->count();
            $statusColor = $statusColors[$device->status] ?? 'secondary';
            $typeIcon    = $typeIcons[$device->device_type ?? 'fingerprint'] ?? 'bi-hdd';
            $typeLabel   = $typeLabels[$device->device_type ?? 'fingerprint'] ?? ucfirst($device->device_type);
            $methodLabel = $methodLabels[$device->communication ?? 'push_api'] ?? ucfirst($device->communication);
            $methodColor = $methodColors[$device->communication ?? 'push_api'] ?? 'secondary';
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary fs-4">
                                <i class="bi {{ $typeIcon }}"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $device->device_name }}</div>
                                <code class="small text-body-secondary">{{ $device->device_code }}</code>
                            </div>
                        </div>
                        <span class="badge text-bg-{{ $statusColor }} text-capitalize">{{ $device->status }}</span>
                    </div>

                    <div class="row g-2 mb-3 small">
                        @if($device->brand)
                        <div class="col-6">
                            <span class="text-body-secondary">Brand</span>
                            <div class="fw-semibold">{{ $device->brand }} {{ $device->model_no }}</div>
                        </div>
                        @endif
                        @if($device->ip_address)
                        <div class="col-6">
                            <span class="text-body-secondary">IP / Port</span>
                            <div class="fw-semibold font-monospace">{{ $device->ip_address }}:{{ $device->port ?? 4370 }}</div>
                        </div>
                        @endif
                        @if($device->location)
                        <div class="col-6">
                            <span class="text-body-secondary"><i class="bi bi-geo-alt"></i> Location</span>
                            <div class="fw-semibold">{{ $device->location }}</div>
                        </div>
                        @endif
                        <div class="col-6">
                            <span class="text-body-secondary">Type</span>
                            <div class="fw-semibold">{{ $typeLabel }}</div>
                        </div>
                        <div class="col-6">
                            <span class="text-body-secondary">Integration</span>
                            <span class="badge text-bg-{{ $methodColor }} bg-opacity-75">{{ $methodLabel }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-body-secondary">Enrollments</span>
                            <div class="fw-bold text-primary">{{ $enrollCount }} <span class="fw-normal text-body-secondary">people</span></div>
                        </div>
                    </div>

                    @if($device->notes)
                    <p class="small text-body-secondary mb-3 border-top pt-2">{{ Str::limit($device->notes, 80) }}</p>
                    @endif

                    <div class="d-flex gap-2 border-top pt-3">
                        <a href="{{ route('biometric-enrollments.index') }}?device_id={{ $device->id }}"
                           class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-person-lines-fill"></i> Enrollments
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-edit-record data-module="biometric-devices" data-id="{{ $device->id }}">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            data-delete-record data-module="biometric-devices" data-id="{{ $device->id }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Pagination (if needed) ── --}}
    @if(!empty($pagination) && $pagination['last_page'] > 1)
    <div class="d-flex justify-content-center mb-4">
        <nav aria-label="Device pagination">
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

    {{-- hidden table wrapper used by JS CRUD delete/edit table refresh ── --}}
    <div data-module-table-wrapper class="d-none">
        @include('modules.table', ['records' => $records, 'moduleConfig' => $moduleConfig, 'moduleKey' => $moduleKey, 'pagination' => $pagination ?? null])
    </div>

    @else
    {{-- ── Empty State ── --}}
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-fingerprint display-4 text-body-secondary mb-3 d-block"></i>
            <h2 class="h5">No Biometric Devices Yet</h2>
            <p class="text-body-secondary mb-4">
                Add your first biometric machine to enable automatic attendance tracking via fingerprint, face recognition, or RFID card.
            </p>
            <button class="btn btn-primary" type="button" data-open-create-modal>
                <i class="bi bi-plus-circle me-1"></i> Add First Device
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
                        <span class="eyebrow">Biometric Device</span>
                        <h2 class="h4 mb-0" data-modal-title>Add Device</h2>
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
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-submit-module-form>
                        <i class="bi bi-check-circle me-1"></i> Save Device
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
// After CRUD save/delete, refresh device cards by reloading the page
(function () {
    const orig = window.showToast;
    document.addEventListener('DOMContentLoaded', function () {
        const tableWrapper = document.querySelector('[data-module-table-wrapper]');
        if (!tableWrapper) return;

        // Observe table wrapper to detect CRUD refresh and reload cards grid
        const observer = new MutationObserver(function () {
            // Reload page to refresh device cards + enrollment counts
            window.location.reload();
        });
        observer.observe(tableWrapper, { childList: true, subtree: true });
    });
})();
</script>
@endpush
@endsection
