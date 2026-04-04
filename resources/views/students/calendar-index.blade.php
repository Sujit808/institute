@extends('layouts.app')

@section('content')
<style>
    .calendar-shell {
        background: #f8fbff;
        border: 1px solid #dbe7f3;
        border-radius: 14px;
        padding: 20px;
    }

    .calendar-filter,
    .calendar-table-wrap {
        background: #ffffff;
        border: 1px solid #dce7f3;
        border-radius: 12px;
    }

    .calendar-filter {
        padding: 14px;
    }

    .calendar-filter .form-label {
        font-size: 0.8rem;
        color: #475569;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .calendar-filter .form-control,
    .calendar-filter .form-select {
        border: 1px solid #cbd9e8;
        border-radius: 8px;
        font-size: 0.88rem;
    }

    .calendar-filter .form-control:focus,
    .calendar-filter .form-select:focus {
        border-color: #1d67c1;
        box-shadow: 0 0 0 3px rgba(29, 103, 193, 0.14);
    }

    .calendar-table-wrap {
        margin-top: 14px;
        overflow: hidden;
    }

    .calendar-table thead th {
        background: #ecf2f8;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #dce7f3;
    }

    .calendar-table tbody td {
        font-size: 0.9rem;
        color: #475569;
        border-color: #edf2f8;
    }

    .calendar-table tbody tr:hover {
        background: #f8fbff;
    }

    .student-chip {
        display: inline-block;
        color: #1e293b;
        font-weight: 600;
    }

    .student-chip::before {
        content: "";
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #1d67c1;
        display: inline-block;
        margin-right: 8px;
        vertical-align: middle;
    }

    .calendar-pagination {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .calendar-pagination-summary {
        margin: 0;
        font-size: 0.88rem;
        color: #64748b;
    }

    .calendar-pagination-summary strong {
        color: #1e293b;
    }

    .calendar-pagination .pagination {
        margin: 0;
    }

    .calendar-pagination .page-link {
        border-radius: 8px;
        border: 1px solid #cfe0f1;
        color: #1d67c1;
        min-width: 36px;
        text-align: center;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 7px 11px;
        box-shadow: none;
    }

    .calendar-pagination .page-item.active .page-link {
        color: #ffffff;
        border-color: #1d67c1;
        background: #1d67c1;
    }

    .empty-state {
        padding: 42px 18px;
        text-align: center;
        color: #64748b;
        background: #f3f8fd;
    }

    @media (max-width: 768px) {
        .calendar-shell {
            padding: 14px;
        }

        .calendar-pagination {
            justify-content: center;
            text-align: center;
        }

        .calendar-pagination-summary {
            width: 100%;
        }
    }
</style>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
    <div>
        <span class="eyebrow">Student Operations</span>
        <h1 class="h3 mb-1">My Calendar</h1>
        <p class="text-body-secondary mb-0">Select a student to open date-wise calendar details.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">
        <i class="bi bi-arrow-left me-1"></i>Back to Students
    </a>
</div>

<div class="calendar-shell">
    <form
        id="calendarFilterForm"
        method="GET"
        action="{{ route('students.calendar.index') }}"
        class="calendar-filter"
        data-sections-endpoint="{{ route('students.calendar.sections') }}"
    >
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label for="calendarClassFilter" class="form-label">Class</label>
                <select
                    name="class_id"
                    id="calendarClassFilter"
                    class="form-select form-select-sm"
                    data-selected-section="{{ (int) $selectedSectionId }}"
                >
                    <option value="">All Classes</option>
                    @foreach ($classes as $classId => $className)
                        <option value="{{ $classId }}" @selected((int) $selectedClassId === (int) $classId)>{{ $className }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <label for="calendarSectionFilter" class="form-label">Section</label>
                <select name="section_id" id="calendarSectionFilter" class="form-select form-select-sm">
                    <option value="">All Sections</option>
                    @foreach ($sections as $sectionId => $sectionName)
                        <option value="{{ $sectionId }}" @selected((int) $selectedSectionId === (int) $sectionId)>{{ $sectionName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 col-sm-8">
                <label for="calendarSearch" class="form-label">Search</label>
                <input
                    id="calendarSearch"
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    class="form-control form-control-sm"
                    placeholder="Name, Roll No, Admission No"
                >
            </div>

            <div class="col-md-2 col-sm-4">
                <label for="calendarPerPage" class="form-label">Per Page</label>
                <select name="per_page" id="calendarPerPage" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="10" @selected($perPage === 10)>10</option>
                    <option value="20" @selected($perPage === 20)>20</option>
                    <option value="50" @selected($perPage === 50)>50</option>
                    <option value="100" @selected($perPage === 100)>100</option>
                </select>
            </div>

            <div class="col-md-1 col-sm-12 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
        </div>
    </form>

    <div class="calendar-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle mb-0 calendar-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td><span class="student-chip">{{ $student->full_name }}</span></td>
                            <td>{{ $student->roll_no ?: '-' }}</td>
                            <td>{{ $student->admission_no ?: '-' }}</td>
                            <td>{{ optional($student->academicClass)->name ?: '-' }}</td>
                            <td>{{ optional($student->section)->name ?: '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('students.calendar', ['id' => $student->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-calendar3 me-1"></i>Open Calendar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="empty-state">
                                    No students available for calendar view.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="p-3 border-top">
                <div class="calendar-pagination">
                    <p class="calendar-pagination-summary">
                        Showing <strong>{{ $students->firstItem() }}</strong> to <strong>{{ $students->lastItem() }}</strong> of <strong>{{ $students->total() }}</strong> students
                    </p>
                    <div>
                        {{ $students->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('calendarFilterForm');
    var classSelect = document.getElementById('calendarClassFilter');
    var sectionSelect = document.getElementById('calendarSectionFilter');
    var perPageSelect = document.getElementById('calendarPerPage');

    if (!form || !classSelect || !sectionSelect || !perPageSelect) {
        return;
    }

    var selectedSection = Number(classSelect.getAttribute('data-selected-section') || 0);
    var endpoint = form.getAttribute('data-sections-endpoint') || '';

    function renderOptions(sections, keepSectionId) {
        var keep = Number(keepSectionId || 0);
        var html = ['<option value="">All Sections</option>'];

        for (var i = 0; i < sections.length; i++) {
            var section = sections[i] || {};
            var sectionId = Number(section.id || 0);
            var selected = keep > 0 && sectionId === keep ? ' selected' : '';
            html.push('<option value="' + sectionId + '"' + selected + '>' + (section.name || '') + '</option>');
        }

        sectionSelect.innerHTML = html.join('');
        sectionSelect.disabled = false;
    }

    function setSectionStatus(label, disabled) {
        var shouldDisable = typeof disabled === 'undefined' ? true : !!disabled;
        sectionSelect.innerHTML = '<option value="">' + label + '</option>';
        sectionSelect.disabled = shouldDisable;
    }

    function loadSections(classId, keepSectionId) {
        if (!endpoint) {
            return;
        }

        var keep = Number(keepSectionId || 0);
        var classValue = Number(classId || 0);
        var requestUrl = endpoint;

        setSectionStatus('Loading sections...', true);

        if (classValue > 0) {
            requestUrl += (endpoint.indexOf('?') === -1 ? '?' : '&') + 'class_id=' + encodeURIComponent(String(classValue));
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', requestUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                setSectionStatus('Unable to load sections', false);
                return;
            }

            var payload;
            try {
                payload = JSON.parse(xhr.responseText || '{}');
            } catch (e) {
                setSectionStatus('Unable to load sections', false);
                return;
            }

            var sections = payload && Object.prototype.toString.call(payload.sections) === '[object Array]'
                ? payload.sections
                : [];

            if (!sections.length) {
                setSectionStatus('No sections found', false);
                return;
            }

            renderOptions(sections, keep);
        };

        xhr.onerror = function () {
            setSectionStatus('Unable to load sections', false);
        };

        xhr.send();
    }

    classSelect.addEventListener('change', function () {
        loadSections(Number(this.value || 0), 0);
    });

    perPageSelect.addEventListener('change', function () {
        form.submit();
    });

    if (endpoint && Number(classSelect.value || 0) > 0) {
        loadSections(Number(classSelect.value || 0), selectedSection);
    }
});
</script>
@endsection
