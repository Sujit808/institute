@php
    $leaveApprovalRequired = $moduleKey === 'leaves'
        ? (\App\Models\LicenseConfig::current()?->approvalRequired('leave_requests') ?? true)
        : false;
@endphp

<div
    class="container-fluid px-4 py-4"
    data-module-page
    data-module="{{ $moduleKey }}"
    @if ($moduleKey === 'leaves')
        data-leave-approval-required="{{ $leaveApprovalRequired ? '1' : '0' }}"
    @endif
    @if ($moduleKey === 'attendance' && is_array(session('attendance_import_toast')))
        data-module-toast-type="{{ session('attendance_import_toast.type', 'success') }}"
        data-module-toast-message="{{ session('attendance_import_toast.message', session('status')) }}"
    @elseif ($moduleKey === 'students' && is_array(session('student_import_toast')))
        data-module-toast-type="{{ session('student_import_toast.type', 'success') }}"
        data-module-toast-message="{{ session('student_import_toast.message', session('status')) }}"
    @elseif ($moduleKey === 'attendance' && session('status'))
        data-module-toast-type="success"
        data-module-toast-message="{{ session('status') }}"
    @elseif ($moduleKey === 'students' && session('status'))
        data-module-toast-type="success"
        data-module-toast-message="{{ session('status') }}"
    @elseif ($moduleKey === 'attendance' && $errors->any())
        data-module-toast-type="error"
        data-module-toast-message="{{ $errors->first() }}"
    @elseif ($moduleKey === 'students' && $errors->any())
        data-module-toast-type="error"
        data-module-toast-message="{{ $errors->first() }}"
    @endif
>
    @if ($moduleKey === 'fees')
        <style>
            .fee-summary-card {
                border: 1px solid #dce7f3;
                border-radius: 14px;
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            }
            .fee-summary-label {
                color: #64748b;
                font-size: .78rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                font-weight: 700;
            }
            .fee-summary-value {
                color: #1e293b;
                font-size: 1.55rem;
                font-weight: 800;
                line-height: 1.2;
            }
            .fee-installment-panel {
                border: 1px solid #dbe7f3;
                border-radius: 12px;
                background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
                padding: 14px;
            }
            .fee-mini-card {
                border: 1px solid #dce7f3;
                border-radius: 12px;
                background: #ffffff;
                padding: 12px;
            }
            .fee-amount-pill,
            .fee-paid-pill,
            .fee-due-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 6px 10px;
                font-size: .8rem;
                font-weight: 700;
                white-space: nowrap;
            }
            .fee-amount-pill {
                background: #dbeafe;
                color: #1d4ed8;
            }
            .fee-paid-pill {
                background: #dcfce7;
                color: #166534;
            }
            .fee-due-pill {
                background: #fee2e2;
                color: #991b1b;
            }
        </style>
    @endif
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">School Operations</span>
            <h1 class="h2 mb-1 d-flex align-items-center gap-2 flex-wrap">
                <span>{{ $moduleConfig['title'] }}</span>
                @if ($moduleKey === 'leaves')
                    <span class="badge text-bg-warning" data-leave-pending-badge>Pending: {{ (int) ($leavePendingCount ?? 0) }}</span>
                    @if ($leaveApprovalRequired)
                        <span class="badge text-bg-dark">Strict Approval Mode</span>
                    @endif
                @endif
            </h1>
            <p class="text-body-secondary mb-0">Manage {{ strtolower($moduleConfig['title']) }} using Ajax forms and modal-based CRUD.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($moduleKey === 'admission-leads')
                <a class="btn btn-outline-dark" href="{{ route('admission-leads.kanban') }}">Kanban View</a>
            @endif
            @unless (! empty($moduleConfig['readonly']))
                <button class="btn btn-primary" type="button" data-open-create-modal>
                    <i class="bi bi-plus-circle"></i>
                    <span>Add {{ $moduleConfig['singular'] }}</span>
                </button>
            @endunless
            <a class="btn btn-outline-secondary" href="{{ route($moduleKey.'.export.pdf') }}">Export PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route($moduleKey.'.export.excel') }}">Export Excel</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @if ($moduleKey === 'leaves' && $leaveApprovalRequired)
        <div class="alert alert-info border-0 shadow-sm" role="alert">
            Leave requests are in strict approval mode. New or edited requests stay pending, and final approve or reject actions are only available from the quick approval controls.
        </div>
    @endif

    @if ($moduleKey === 'attendance')
        @php
            $oldImportClassId = (int) old('class_id', 0);
            $oldImportSectionId = (int) old('section_id', 0);
        @endphp
        <div class="card app-card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
                    <div>
                        <h6 class="mb-1">Class-wise Attendance Excel Upload</h6>
                        <p class="text-body-secondary mb-0 small">Upload file with class/section wise attendance. Required headers: <strong>class</strong>, <strong>section</strong>, <strong>roll_no</strong>, <strong>in_time</strong>, <strong>out_time</strong>. Optional: <strong>date</strong> in <strong>dd/mm/yyyy</strong> format (e.g. <strong>26/03/2026</strong>). Form class/section values are optional filters.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('attendance.import.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template
                        </a>
                        @if (session('attendance_import_failed_rows'))
                            <a href="{{ route('attendance.import.errors') }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i>Download Failed Rows
                            </a>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('attendance.import.excel') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Class Filter (Optional)</label>
                        <select class="form-select form-select-sm @error('class_id') is-invalid @enderror" name="class_id" id="attendance-import-class">
                            <option value="">Select Class</option>
                            @foreach (($lookups['academic_classes'] ?? []) as $classId => $classLabel)
                                <option value="{{ $classId }}" @selected($oldImportClassId === (int) $classId)>{{ $classLabel }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Section Filter (Optional)</label>
                        <select class="form-select form-select-sm @error('section_id') is-invalid @enderror" name="section_id" id="attendance-import-section" data-selected-section="{{ $oldImportSectionId }}">
                            <option value="">All Sections</option>
                            @foreach (($lookups['sections_meta'] ?? []) as $sectionMeta)
                                <option
                                    value="{{ $sectionMeta['id'] }}"
                                    data-class-id="{{ $sectionMeta['academic_class_id'] }}"
                                    @selected($oldImportSectionId === (int) $sectionMeta['id'])
                                >{{ $sectionMeta['name'] }}</option>
                            @endforeach
                        </select>
                        @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Attendance Date</label>
                        <input type="date" class="form-control form-control-sm @error('attendance_date') is-invalid @enderror" name="attendance_date" value="{{ old('attendance_date', now()->toDateString()) }}" required>
                        @error('attendance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Excel File (.xlsx/.xls/.csv)</label>
                        <input type="file" class="form-control form-control-sm @error('attendance_file') is-invalid @enderror" name="attendance_file" accept=".xlsx,.xls,.csv" required>
                        @error('attendance_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const classSelect = document.getElementById('attendance-import-class');
                const sectionSelect = document.getElementById('attendance-import-section');

                if (!classSelect || !sectionSelect) {
                    return;
                }

                const sectionOptions = Array.from(sectionSelect.querySelectorAll('option')).slice(1);
                const selectedSection = Number(sectionSelect.dataset.selectedSection || 0);

                const filterSections = function (keepSelected) {
                    const classId = Number(classSelect.value || 0);
                    const currentValue = Number(sectionSelect.value || 0);

                    sectionOptions.forEach(function (option) {
                        const optionClassId = Number(option.getAttribute('data-class-id') || 0);
                        const show = classId <= 0 || optionClassId === classId;
                        option.hidden = !show;
                        option.disabled = !show;
                    });

                    const targetSection = keepSelected ? selectedSection : currentValue;
                    if (targetSection > 0) {
                        const selectedOption = sectionOptions.find(function (option) {
                            return Number(option.value || 0) === targetSection && !option.disabled;
                        });
                        sectionSelect.value = selectedOption ? String(targetSection) : '';
                    } else {
                        sectionSelect.value = '';
                    }
                };

                filterSections(true);

                classSelect.addEventListener('change', function () {
                    filterSections(false);
                });
            });
        </script>
    @endif

    @if ($moduleKey === 'students')
        @php($canManageStudentCollegeImport = auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin() || auth()->user()?->isHr())
        @if (! empty($studentCollegeStats))
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card app-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-body-secondary small text-uppercase fw-semibold mb-1">Students In View</div>
                            <div class="h3 mb-1">{{ (int) ($studentCollegeStats['total'] ?? 0) }}</div>
                            <div class="small text-body-secondary">Current student scope after filters</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card app-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-body-secondary small text-uppercase fw-semibold mb-1">Previous Filled</div>
                            <div class="h3 mb-1">{{ number_format((float) ($studentCollegeStats['previous_percentage'] ?? 0), 1) }}%</div>
                            <div class="small text-body-secondary">{{ (int) ($studentCollegeStats['previous_filled'] ?? 0) }} students have previous school/college</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card app-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-body-secondary small text-uppercase fw-semibold mb-1">Current Filled</div>
                            <div class="h3 mb-1">{{ number_format((float) ($studentCollegeStats['current_percentage'] ?? 0), 1) }}%</div>
                            <div class="small text-body-secondary">{{ (int) ($studentCollegeStats['current_filled'] ?? 0) }} students have current school/college</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card app-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-body-secondary small text-uppercase fw-semibold mb-1">Both Filled</div>
                            <div class="h3 mb-1">{{ number_format((float) ($studentCollegeStats['both_percentage'] ?? 0), 1) }}%</div>
                            <div class="small text-body-secondary">{{ (int) ($studentCollegeStats['both_filled'] ?? 0) }} students have both values</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card app-card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
                    <div>
                        <h6 class="mb-1">Student School/College Bulk Update</h6>
                        <p class="text-body-secondary mb-0 small">Upload CSV/Excel with headers like <strong>admission_no</strong> or <strong>roll_no</strong>, plus <strong>previous_school_college_name</strong> and/or <strong>current_school_college_name</strong>. Header matching is flexible for spacing/casing variants.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('students.import.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template
                        </a>
                        <a href="{{ route('students.export.editable.colleges') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Export Editable Current Data
                        </a>
                        @if ($canManageStudentCollegeImport && session('student_import_failed_rows'))
                            <a href="{{ route('students.import.errors') }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i>Download Failed Rows
                            </a>
                        @endif
                    </div>
                </div>

                @if ($canManageStudentCollegeImport)
                    <form method="POST" action="{{ route('students.import.colleges') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-lg-7">
                            <label class="form-label small mb-1">Excel File (.xlsx/.xls/.csv)</label>
                            <input type="file" class="form-control form-control-sm @error('student_update_file') is-invalid @enderror" name="student_update_file" accept=".xlsx,.xls,.csv" required>
                            @error('student_update_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-lg-3">
                            <div class="form-check mt-4 pt-1">
                                <input class="form-check-input" type="checkbox" value="1" id="clear-empty-values" name="clear_empty_values" @checked(old('clear_empty_values'))>
                                <label class="form-check-label small" for="clear-empty-values">
                                    Clear existing values when uploaded cell is blank
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-2 d-grid">
                            <button type="submit" class="btn btn-sm btn-primary">Preview Import</button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-light border mb-0 small">
                        Teachers and read-only users can review the template/export, but only Admin or HR can preview and apply bulk imports.
                    </div>
                @endif

                @if (is_array(session('student_import_preview')))
                    @php($studentImportPreview = session('student_import_preview'))
                    <div class="mt-3 border rounded p-3 bg-light-subtle">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-2">
                            <div class="small">
                                <strong>Preview Summary:</strong>
                                To Update: {{ (int) ($studentImportPreview['to_update'] ?? 0) }},
                                Unchanged: {{ (int) ($studentImportPreview['unchanged'] ?? 0) }},
                                Skipped: {{ (int) ($studentImportPreview['skipped'] ?? 0) }},
                                Not found: {{ (int) ($studentImportPreview['not_found'] ?? 0) }},
                                Ambiguous: {{ (int) ($studentImportPreview['ambiguous'] ?? 0) }}
                            </div>
                            <form method="POST" action="{{ route('students.import.colleges') }}">
                                @csrf
                                <input type="hidden" name="confirm_import" value="1">
                                <button type="submit" class="btn btn-sm btn-success">Confirm Import</button>
                            </form>
                        </div>
                        @if (! empty($studentImportPreview['clear_empty_values']))
                            <div class="small text-body-secondary mb-2">Empty uploaded cells in mapped columns will clear existing student values when you confirm this import.</div>
                        @endif
                        @if (! empty($studentImportPreview['rows']) && is_array($studentImportPreview['rows']))
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Line</th>
                                            <th>Admission No</th>
                                            <th>Roll No</th>
                                            <th>Old Previous</th>
                                            <th>New Previous</th>
                                            <th>Old Current</th>
                                            <th>New Current</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($studentImportPreview['rows'] as $previewRow)
                                            <tr>
                                                <td>{{ $previewRow['line'] ?? '' }}</td>
                                                <td>{{ $previewRow['admission_no'] ?? '' }}</td>
                                                <td>{{ $previewRow['roll_no'] ?? '' }}</td>
                                                <td>{{ $previewRow['old_previous_school'] ?? '' }}</td>
                                                <td>{{ $previewRow['new_previous_school'] ?? '' }}</td>
                                                <td>{{ $previewRow['old_current_school'] ?? '' }}</td>
                                                <td>{{ $previewRow['new_current_school'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($moduleKey === 'fees' && ! empty($feesSummary))
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card fee-summary-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fee-summary-label">Total Fee Amount</div>
                        <div class="fee-summary-value">Rs {{ number_format((float) ($feesSummary['total_amount'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card fee-summary-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fee-summary-label">Total Paid</div>
                        <div class="fee-summary-value text-success">Rs {{ number_format((float) ($feesSummary['total_paid'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card fee-summary-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fee-summary-label">Outstanding Due</div>
                        <div class="fee-summary-value text-danger">Rs {{ number_format((float) ($feesSummary['total_due'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card fee-summary-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fee-summary-label">Fee Records</div>
                        <div class="small text-body-secondary mb-1">Paid: {{ (int) ($feesSummary['paid_count'] ?? 0) }} | Partial: {{ (int) ($feesSummary['partial_count'] ?? 0) }} | Pending: {{ (int) ($feesSummary['pending_count'] ?? 0) }}</div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" data-progress-width="{{ round((((int) ($feesSummary['paid_count'] ?? 0)) / max(1, (int) (($feesSummary['paid_count'] ?? 0) + ($feesSummary['partial_count'] ?? 0) + ($feesSummary['pending_count'] ?? 0)))) * 100, 2) }}"></div>
                            <div class="progress-bar bg-warning" data-progress-width="{{ round((((int) ($feesSummary['partial_count'] ?? 0)) / max(1, (int) (($feesSummary['paid_count'] ?? 0) + ($feesSummary['partial_count'] ?? 0) + ($feesSummary['pending_count'] ?? 0)))) * 100, 2) }}"></div>
                            <div class="progress-bar bg-danger" data-progress-width="{{ round((((int) ($feesSummary['pending_count'] ?? 0)) / max(1, (int) (($feesSummary['paid_count'] ?? 0) + ($feesSummary['partial_count'] ?? 0) + ($feesSummary['pending_count'] ?? 0)))) * 100, 2) }}"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <div class="card app-card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <form id="module-search-form" class="row gy-2 gx-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search Field</label>
                    <select class="form-select form-select-sm" name="search_field" id="search-field">
                        <option value="">All Fields</option>
                        @foreach ($moduleConfig['table_columns'] as $column)
                            <option value="{{ $column['key'] }}" @selected(request('search_field') === $column['key'])>{{ $column['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search Term</label>
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Type to search..." id="search-term" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Sort By</label>
                    <select class="form-select form-select-sm" name="sort_by" id="sort-by">
                        <option value="">Default</option>
                        @foreach ($moduleConfig['table_columns'] as $column)
                            <option value="{{ $column['key'] }}" @selected(request('sort_by') === $column['key'])>{{ $column['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Order</label>
                    <select class="form-select form-select-sm" name="sort_order" id="sort-order">
                        <option value="asc" @selected(request('sort_order', 'asc') === 'asc')>Ascending</option>
                        <option value="desc" @selected(request('sort_order', 'asc') === 'desc')>Descending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Per Page</label>
                    <select class="form-select form-select-sm" name="per_page" id="per-page">
                        <option value="10" @selected((int) request('per_page', 25) === 10)>10</option>
                        <option value="25" @selected((int) request('per_page', 25) === 25)>25</option>
                        <option value="50" @selected((int) request('per_page', 25) === 50)>50</option>
                        <option value="100" @selected((int) request('per_page', 25) === 100)>100</option>
                    </select>
                </div>
                @if ($moduleKey === 'students')
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Previous School/College</label>
                        <input type="text" class="form-control form-control-sm" name="college_name" id="student-college-filter" placeholder="Filter previous school..." value="{{ request('college_name') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Current School/College</label>
                        <input type="text" class="form-control form-control-sm" name="current_college_name" id="student-current-college-filter" placeholder="Filter current school..." value="{{ request('current_college_name') }}">
                    </div>
                @endif
                @if ($moduleKey === 'fees')
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Class</label>
                        <select class="form-select form-select-sm" name="class_id" id="fee-class-filter">
                            <option value="">All Classes</option>
                            @foreach (($lookups['academic_classes'] ?? []) as $classFilterId => $classFilterLabel)
                                <option value="{{ $classFilterId }}" @selected((int) request('class_id', 0) === (int) $classFilterId)>{{ $classFilterLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Month</label>
                        <select class="form-select form-select-sm" name="fee_month" id="fee-month-filter">
                            <option value="">All Months</option>
                            @foreach (range(1, 12) as $monthNumber)
                                <option value="{{ $monthNumber }}" @selected((int) request('fee_month', 0) === $monthNumber)>{{ \Carbon\Carbon::create(null, $monthNumber, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <button type="reset" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card border-0 shadow-sm">
        <div class="card-body p-0" data-module-table-wrapper>
            @include('modules.table', ['records' => $records, 'moduleConfig' => $moduleConfig, 'moduleKey' => $moduleKey, 'pagination' => $pagination ?? null])
        </div>
    </div>

    @if ($moduleKey === 'fees')
        <div class="modal fade" id="feeDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1">Fee Payment Details</h5>
                            <div class="small text-body-secondary" data-fee-modal-subtitle>Student | Fee Type</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 bg-light h-100">
                                    <div class="small text-body-secondary">Total Fee Amount</div>
                                    <div class="fw-bold fs-5" data-fee-modal-total>Rs 0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 bg-light h-100">
                                    <div class="small text-body-secondary">Total Paid</div>
                                    <div class="fw-bold fs-5 text-success" data-fee-modal-paid>Rs 0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 bg-light h-100">
                                    <div class="small text-body-secondary">Remaining Due</div>
                                    <div class="fw-bold fs-5 text-danger" data-fee-modal-due>Rs 0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Receipt No</th>
                                        <th>Mode</th>
                                        <th>Remarks</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody data-fee-modal-payments>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-body-secondary">No installment history available.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @unless (! empty($moduleConfig['readonly']))
        <div class="modal fade" id="moduleCrudModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="eyebrow">Ajax Form</span>
                            <h2 class="h4 mb-0" data-modal-title>Add {{ $moduleConfig['singular'] }}</h2>
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
                                    <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}" data-field="{{ $field['name'] }}" @if (!empty($field['teacher_restricted'])) data-teacher-restricted @endif>
                                        <label class="form-label" for="field_{{ $field['name'] }}">{{ $field['label'] }}</label>
                                        @if ($field['type'] === 'textarea')
                                            <textarea class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}" rows="3" @if (! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif></textarea>
                                        @elseif ($field['type'] === 'select')
                                            <select class="form-select" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}{{ ! empty($field['multiple']) ? '[]' : '' }}" {{ ! empty($field['multiple']) ? 'multiple' : '' }}>
                                                <option value="">Select {{ $field['label'] }}</option>
                                                @foreach (($field['lookup'] ?? null) ? ($lookups[$field['lookup']] ?? []) : ($field['options'] ?? []) as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($field['type'] === 'checkboxes')
                                            <div class="set-toggle-group" id="field_{{ $field['name'] }}">
                                                @foreach (($field['lookup'] ?? null) ? ($lookups[$field['lookup']] ?? []) : ($field['options'] ?? []) as $value => $label)
                                                    @php($uid = 'chk_'.$field['name'].'_'.$value)
                                                    <input class="btn-check" type="checkbox" name="{{ $field['name'] }}[]" id="{{ $uid }}" value="{{ $value }}" autocomplete="off">
                                                    <label class="btn set-toggle-btn" for="{{ $uid }}">{{ $label }}</label>
                                                @endforeach
                                            </div>
                                            @if ($moduleKey === 'staff' && $field['name'] === 'permissions')
                                                <div class="mt-2 p-3 rounded border bg-light-subtle" data-permission-preset-panel>
                                                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                                        <div>
                                                            <div class="fw-semibold small">Recommended Role Permissions</div>
                                                            <div class="small text-body-secondary" data-permission-preset-summary>Select a role to auto-apply its recommended permission set.</div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-apply-permission-preset>Reset to Recommended</button>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <input class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}{{ ! empty($field['multiple']) ? '[]' : '' }}" type="{{ $field['type'] }}" {{ ! empty($field['multiple']) ? 'multiple' : '' }} @if (! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif>
                                        @endif
                                        @if (! empty($field['help']))
                                            <div class="form-text">{{ $field['help'] }}</div>
                                        @endif
                                        @if ($field['type'] === 'file')
                                            <div class="file-preview mt-2" data-file-preview="{{ $field['name'] }}"></div>
                                        @endif
                                        <div class="invalid-feedback d-block small" data-error-for="{{ $field['name'] }}"></div>
                                    </div>
                                @endforeach

                                @if ($moduleKey === 'fees')
                                    <div class="col-12" data-fee-installment-section>
                                        <div class="fee-installment-panel">
                                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
                                                <div>
                                                    <div class="fw-semibold">Installment Entry</div>
                                                    <div class="small text-body-secondary">Paid amount ko directly edit karne ke bajaye naya installment yahan se add karein.</div>
                                                </div>
                                                <div class="small text-body-secondary">Current paid and due live update honge.</div>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <div class="fee-mini-card">
                                                        <div class="small text-body-secondary">Fee Amount</div>
                                                        <div class="fw-bold fs-6" data-fee-form-total>Rs 0.00</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fee-mini-card">
                                                        <div class="small text-body-secondary">Current Paid</div>
                                                        <div class="fw-bold fs-6 text-success" data-fee-form-paid>Rs 0.00</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fee-mini-card">
                                                        <div class="small text-body-secondary">Remaining Due</div>
                                                        <div class="fw-bold fs-6 text-danger" data-fee-form-due>Rs 0.00</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-4" data-field="installment_amount">
                                                    <label class="form-label" for="field_installment_amount">Installment Amount</label>
                                                    <input class="form-control" id="field_installment_amount" name="installment_amount" type="number" min="0" step="0.01" placeholder="0.00">
                                                    <div class="form-text">Create ya edit ke waqt naya payment installment add karega.</div>
                                                    <div class="invalid-feedback d-block small" data-error-for="installment_amount"></div>
                                                </div>
                                                <div class="col-md-4" data-field="installment_date">
                                                    <label class="form-label" for="field_installment_date">Installment Date</label>
                                                    <input class="form-control" id="field_installment_date" name="installment_date" type="date">
                                                    <div class="invalid-feedback d-block small" data-error-for="installment_date"></div>
                                                </div>
                                                <div class="col-md-4 d-flex align-items-end">
                                                    <div class="small text-body-secondary">Payment mode aur remarks niche existing fee fields se hi use honge.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" data-submit-module-form>Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
</div>

<script type="application/json" id="module-config-json">@json($moduleConfig)</script>
<script type="application/json" id="module-lookups-json">@json($lookups)</script>

@if ($moduleKey === 'fees')
    <script>
        function applyFeeProgressWidths(scope) {
            const root = scope || document;
            root.querySelectorAll('[data-progress-width]').forEach(function (element) {
                const width = Number(element.getAttribute('data-progress-width') || 0);
                element.style.width = Math.max(0, Math.min(100, width)) + '%';
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyFeeProgressWidths(document);

            const tableWrapper = document.querySelector('[data-module-table-wrapper]');
            if (tableWrapper && 'MutationObserver' in window) {
                const observer = new MutationObserver(function () {
                    applyFeeProgressWidths(tableWrapper);
                });

                observer.observe(tableWrapper, { childList: true, subtree: true });
            }
        });

        document.addEventListener('show.bs.modal', function (event) {
            const trigger = event.target?.id === 'feeDetailsModal' ? event.relatedTarget : null;
            if (!trigger) {
                return;
            }

            const modal = event.target;
            const subtitle = modal.querySelector('[data-fee-modal-subtitle]');
            const total = modal.querySelector('[data-fee-modal-total]');
            const paid = modal.querySelector('[data-fee-modal-paid]');
            const due = modal.querySelector('[data-fee-modal-due]');
            const paymentsBody = modal.querySelector('[data-fee-modal-payments]');

            const student = trigger.getAttribute('data-fee-student') || 'Student';
            const feeType = trigger.getAttribute('data-fee-type') || 'Fee';
            const totalAmount = Number(trigger.getAttribute('data-fee-total') || 0).toFixed(2);
            const paidAmount = Number(trigger.getAttribute('data-fee-paid') || 0).toFixed(2);
            const dueAmount = Number(trigger.getAttribute('data-fee-due') || 0).toFixed(2);

            let payments = [];
            try {
                payments = JSON.parse(trigger.getAttribute('data-fee-payments') || '[]');
            } catch (error) {
                payments = [];
            }

            subtitle.textContent = student + ' | ' + feeType;
            total.textContent = 'Rs ' + totalAmount;
            paid.textContent = 'Rs ' + paidAmount;
            due.textContent = 'Rs ' + dueAmount;

            if (!payments.length) {
                paymentsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-body-secondary">No installment history available.</td></tr>';
                return;
            }

            paymentsBody.innerHTML = payments.map(function (payment, index) {
                return '<tr>'
                    + '<td>' + (index + 1) + '</td>'
                    + '<td>' + (payment.date || 'N/A') + '</td>'
                    + '<td>' + (payment.receipt_no || 'N/A') + '</td>'
                    + '<td>' + (payment.mode || 'N/A') + '</td>'
                    + '<td>' + (payment.remarks || '-') + '</td>'
                    + '<td class="text-end fw-semibold">Rs ' + (payment.amount || '0.00') + '</td>'
                    + '</tr>';
            }).join('');
        });
    </script>
@endif

