@extends('layouts.app')

@section('content')
<style>
.master-banner {
    background: linear-gradient(120deg, #0f172a, #1e293b);
    color: #fff;
}
.master-banner .eyebrow,
.master-banner .text-body-secondary {
    color: rgba(255, 255, 255, 0.85) !important;
}
.stat-tile {
    border-radius: 14px;
    padding: 0.8rem 0.9rem;
    color: #fff;
    min-height: 78px;
}
.master-cal-table { font-size: 11px; }
.master-cal-table thead th { white-space: nowrap; text-align: center; vertical-align: middle; padding: 6px 4px; }
.master-cal-table tbody td { text-align: center; vertical-align: middle; padding: 4px 3px; }
.master-cal-table tbody tr:hover td { background: #f8fafc; }
.master-cal-table tbody td.student-name-cell { text-align: left; white-space: nowrap; padding-left: 10px; min-width: 190px; position: sticky; left: 0; background: #fff; z-index: 2; box-shadow: 2px 0 4px rgba(0,0,0,.06); }
.master-cal-table .day-cell { width: 28px; min-width: 24px; }
.status-badge { display: inline-block; width: 20px; height: 20px; border-radius: 50%; font-size: 9px; font-weight: 700; line-height: 20px; color: #fff; }
.status-badge.present  { background: #22c55e; }
.status-badge.absent   { background: #ef4444; }
.status-badge.leave    { background: #f59e0b; color: #111; }
.status-badge.holiday  { background: #d97706; color: #fff; }
.status-badge.weekoff  { background: #7c3aed; color: #fff; }
.status-badge.none     { background: transparent; color: #d1d5db; border: 1px dashed #e5e7eb; }
.summary-col { font-weight: 600; min-width: 34px; }
.col-p  { color: #15803d; }
.col-a  { color: #b91c1c; }
.col-l  { color: #b45309; }
.col-h  { color: #374151; }
.day-head-holiday { background: #fff4db; color: #b45309; }
.day-head-weekoff { background: #f3e8ff; color: #6d28d9; }
.day-head-sunday  { background: #fef9ec; color: #b45309; }
.day-status-present { background: #ecfdf3; }
.day-status-absent { background: #fef2f2; }
.day-status-leave { background: #fff7ed; }
.day-status-holiday { background: #fff8eb; }
.day-status-weekoff { background: #f5f3ff; }
.master-cal-wrapper { overflow-x: auto; max-height: 70vh; overflow-y: auto; }
.master-cal-table thead { position: sticky; top: 0; z-index: 5; }
.master-cal-table thead th.student-name-cell { position: sticky; left: 0; z-index: 6; background: #f8fafc; }
.roll-no { font-size: 10px; color: #6b7280; }
.legend-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
    background: #fff;
    font-size: 12px;
}
.master-pagination .pagination {
    gap: 0.2rem;
    margin-bottom: 0;
}
.master-pagination .page-link {
    border-radius: 0.45rem;
    border-color: #dbe3f0;
    color: #1d4ed8;
    font-weight: 600;
    min-width: 2.2rem;
    text-align: center;
}
.master-pagination .page-item.active .page-link {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}
.master-pagination .page-item.disabled .page-link {
    color: #94a3b8;
    background: #f8fafc;
}
.master-pagination-sticky {
    position: sticky;
    bottom: 0;
    z-index: 8;
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: blur(2px);
    border-top: 1px solid #e2e8f0;
    padding: 0.55rem 0.75rem;
}
.master-pagination-meta {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
}
</style>

<div class="container-fluid px-4 py-4">
    @php
        $sessionEndDate = now()->month >= 4
            ? now()->copy()->addYear()->month(3)->endOfMonth()->toDateString()
            : now()->copy()->month(3)->endOfMonth()->toDateString();
    @endphp

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="card master-banner border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
            <span class="eyebrow">Attendance</span>
            <h1 class="h3 mb-1">Master Calendar</h1>
                <p class="text-body-secondary mb-0">Class-wise monthly attendance overview - see who is Present, Absent, or on Leave.</p>
            </div>
            <span class="badge rounded-pill text-bg-light px-3 py-2" style="font-size: 13px;">{{ $monthLabel }}</span>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('master.calendar') }}" class="row g-2 align-items-end" id="masterFilterForm" data-sections-endpoint="{{ route('master.calendar.sections') }}">
                <div class="col-sm-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Class</label>
                        <select name="class_id" class="form-select form-select-sm" id="masterClassFilter" data-selected-section="{{ (int) $selectedSectionId }}">
                        <option value="">— All Classes —</option>
                        @foreach ($classes as $cls)
                            <option value="{{ $cls->id }}" @selected($selectedClassId == $cls->id)>{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Section</label>
                        <select name="section_id" class="form-select form-select-sm" id="masterSectionFilter">
                        <option value="">— All Sections —</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec->id }}" @selected($selectedSectionId == $sec->id)>{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        @foreach ($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" @selected($selectedMonth == $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" @selected($selectedYear == $yr)>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Roll No</label>
                    <input type="text" name="roll_no" value="{{ $rollNo }}" class="form-control form-control-sm" placeholder="e.g. 10A001">
                </div>
                <div class="col-sm-1">
                    <label class="form-label form-label-sm fw-semibold mb-1">Per Page</label>
                    <select name="per_page" class="form-select form-select-sm">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($perPage ?? 25) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Apply
                    </button>
                    <a href="{{ route('master.calendar.export', request()->except('page')) }}" id="exportBtn" data-export-base="{{ route('master.calendar.export') }}" class="btn btn-outline-success btn-sm" title="Export filtered records">
                        <i class="bi bi-download"></i>
                    </a>
                    <a href="{{ route('master.calendar') }}" id="resetFilterBtn" data-reset-href="{{ route('master.calendar') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if (auth()->user()->canAccessModule('holidays'))
        <div class="card app-card border-0 shadow-sm mb-4">
            <div class="card-header border-0 bg-transparent d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Holiday / Weekoff Setup</h6>
                <small class="text-body-secondary">Weekoff selected weekdays start date se ek saal tak sab calendars par apply honge.</small>
            </div>
            <div class="card-body pt-0">
                <div id="dayOffFeedback" class="d-none mb-3"></div>
                <form method="POST" action="{{ route('master.calendar.dayoff.store') }}" class="row g-2 align-items-end" id="masterDayOffForm">
                    @csrf
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm fw-semibold mb-1">Entry Type</label>
                        <select name="entry_type" id="entryTypeSelect" class="form-select form-select-sm">
                            <option value="holiday" @selected(old('entry_type') === 'holiday')>Holiday</option>
                            <option value="weekoff" @selected(old('entry_type') === 'weekoff')>Week Off</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6" data-entry-title>
                        <label class="form-label form-label-sm fw-semibold mb-1">Title</label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title') }}" placeholder="Festival / National Holiday">
                    </div>
                    <div class="col-md-4 col-sm-12 d-none" data-entry-weekday>
                        <label class="form-label form-label-sm fw-semibold mb-1">Weekdays</label>
                        @php
                            $selectedWeekdays = collect(old('weekdays', old('weekday') ? [old('weekday')] : ['sunday']))
                                ->map(fn ($day) => strtolower((string) $day))
                                ->all();
                        @endphp
                        <div class="d-flex flex-wrap gap-2 border rounded p-2 bg-light-subtle">
                            @foreach (['sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'] as $dayKey => $dayLabel)
                                <label class="form-check form-check-inline small mb-0 me-2">
                                    <input type="checkbox" class="form-check-input" name="weekdays[]" value="{{ $dayKey }}" @checked(in_array($dayKey, $selectedWeekdays, true))>
                                    <span class="form-check-label">{{ $dayLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 d-none" data-entry-recurring>
                        <div class="form-check mt-4 pt-1">
                            <input class="form-check-input" type="checkbox" value="1" id="weekoffRecurringCheck" name="is_recurring" @checked(old('is_recurring'))>
                            <label class="form-check-label small fw-semibold" for="weekoffRecurringCheck">
                                Recurring weekoff
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 d-none" data-entry-recurring-end>
                        <label class="form-label form-label-sm fw-semibold mb-1">Recurring End</label>
                        <input type="date" name="recurring_end_date" value="{{ old('recurring_end_date') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm fw-semibold mb-1" id="dayOffStartLabel">Start Date</label>
                        <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label form-label-sm fw-semibold mb-1" id="dayOffEndLabel">End Date</label>
                        <input type="date" name="end_date" value="{{ old('end_date', old('entry_type') === 'weekoff' ? now()->copy()->addYear()->subDay()->toDateString() : now()->toDateString()) }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-1 col-sm-6">
                        <button type="submit" class="btn btn-primary btn-sm w-100" id="dayOffSaveButton">Save</button>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label form-label-sm fw-semibold mb-1">Description (Optional)</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control form-control-sm" placeholder="Optional note for this holiday/weekoff setup">
                    </div>
                    <div class="col-md-12" data-weekoff-presets>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-weekoff-preset data-weekday="sunday">
                                Every Sunday For 1 Year
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-weekoff-preset data-weekday="saturday">
                                Every Saturday For 1 Year
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-weekoff-preset data-weekday="friday">
                                Every Friday For 1 Year
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">Save without reload. Current visible calendar will update instantly.</small>
                    </div>
                </form>
            </div>
        </div>
    @endif
    <div id="masterCalendarContent">

    @php
        $overall = collect($matrix)->reduce(function ($carry, $row) {
            $carry['present'] += (int) ($row['summary']['present'] ?? 0);
            $carry['absent'] += (int) ($row['summary']['absent'] ?? 0);
            $carry['leave'] += (int) ($row['summary']['leave'] ?? 0);
            $carry['holiday'] += (int) ($row['summary']['holiday'] ?? 0);
            return $carry;
        }, ['present' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0]);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-tile" style="background: linear-gradient(130deg, #059669, #22c55e);">
                <div class="small text-white-50">Total Present Marks</div>
                <div class="fs-4 fw-semibold" id="overallPresentCount">{{ $overall['present'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile" style="background: linear-gradient(130deg, #b91c1c, #ef4444);">
                <div class="small text-white-50">Total Absent Marks</div>
                <div class="fs-4 fw-semibold" id="overallAbsentCount">{{ $overall['absent'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile" style="background: linear-gradient(130deg, #c2410c, #f59e0b);">
                <div class="small text-white-50">Total Leave Marks</div>
                <div class="fs-4 fw-semibold" id="overallLeaveCount">{{ $overall['leave'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile" style="background: linear-gradient(130deg, #475569, #64748b);">
                <div class="small text-white-50">Students (Filtered)</div>
                <div class="fs-4 fw-semibold" id="overallStudentCount">{{ $studentsPage->total() }}</div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <span class="fw-semibold text-body-secondary" style="font-size:12px">Legend:</span>
        <span class="legend-chip"><span class="status-badge present d-inline-flex align-items-center justify-content-center" style="font-size:10px">P</span>Present</span>
        <span class="legend-chip"><span class="status-badge absent d-inline-flex align-items-center justify-content-center" style="font-size:10px">A</span>Absent</span>
        <span class="legend-chip"><span class="status-badge leave d-inline-flex align-items-center justify-content-center" style="font-size:10px">L</span>Leave</span>
        <span class="legend-chip"><span class="status-badge holiday d-inline-flex align-items-center justify-content-center" style="font-size:10px">H</span>Holiday</span>
        <span class="legend-chip"><span class="status-badge weekoff d-inline-flex align-items-center justify-content-center" style="font-size:10px">W</span>Week Off</span>
        <span class="legend-chip"><span class="status-badge none d-inline-flex align-items-center justify-content-center" style="font-size:10px">-</span>No Data</span>
    </div>

    {{-- Month Title --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">{{ $monthLabel }}
            @if($selectedClassId > 0)
                <span class="badge bg-primary-subtle text-primary fw-normal ms-2" style="font-size:13px">
                    {{ $classes->find($selectedClassId)?->name ?? '' }}
                    @if($selectedSectionId > 0) &mdash; {{ $sections->find($selectedSectionId)?->name ?? '' }} @endif
                </span>
            @endif
        </h5>
        <span class="text-body-secondary" style="font-size:12px">
            Showing {{ $studentsPage->firstItem() ?? 0 }}-{{ $studentsPage->lastItem() ?? 0 }} of {{ $studentsPage->total() }} student(s)
        </span>
    </div>

    @if ($studentsPage->hasPages())
        <div class="master-pagination d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="master-pagination-meta">
                Page {{ $studentsPage->currentPage() }} of {{ $studentsPage->lastPage() }}
            </span>
            {{ $studentsPage->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif

    {{-- Calendar Table --}}
    @if (count($matrix) === 0)
        <div class="card app-card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-body-secondary">
                <i class="bi bi-calendar-x fs-1 mb-2 d-block"></i>
                No students found. Please select a class and section above.
            </div>
        </div>
    @else
    <div class="card app-card border-0 shadow-sm">
        <div class="master-cal-wrapper">
            <table class="table table-bordered align-middle mb-0 master-cal-table">
                <thead class="table-light">
                    <tr>
                        <th class="student-name-cell" style="background:#f8fafc; min-width:180px">
                            Student
                        </th>
                        @foreach ($monthDates as $date)
                            @php
                                $dk = $date->toDateString();
                                $special = $specialDates[$dk] ?? null;
                                $isHoliday = $special !== null && (($special['type'] ?? 'holiday') !== 'weekoff');
                                $isWeekoff = $special !== null && (($special['type'] ?? '') === 'weekoff');
                                $isSunday  = $date->isSunday();
                            @endphp
                            <th class="day-cell {{ $isHoliday ? 'day-head-holiday' : ($isWeekoff ? 'day-head-weekoff' : ($isSunday ? 'day-head-sunday' : '')) }}"
                                data-date="{{ $dk }}"
                                title="{{ $special['title'] ?? $date->format('l, d M') }}">
                                <div>{{ $date->format('d') }}</div>
                                <div style="font-size:9px;font-weight:400">{{ $date->format('D') }}</div>
                            </th>
                        @endforeach
                        <th class="summary-col col-p" title="Present Days">P</th>
                        <th class="summary-col col-a" title="Absent Days">A</th>
                        <th class="summary-col col-l" title="Leave Days">L</th>
                        <th class="summary-col col-h" title="Holiday Days">H</th>
                        <th class="summary-col" title="Link to Student Calendar">Cal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matrix as $row)
                        @php $student = $row['student']; @endphp
                        <tr data-student-row data-student-id="{{ $student->id }}" data-student-name="{{ $student->full_name }}" data-student-roll="{{ $student->roll_no ?: $student->admission_no ?: '#'.$student->id }}">
                            <td class="student-name-cell">
                                <div class="fw-semibold" style="font-size:12px">{{ $student->full_name }}</div>
                                <div class="roll-no">
                                    {{ $student->roll_no ?: $student->admission_no ?: '#'.$student->id }}
                                    @if($student->academicClass)
                                        &middot; {{ $student->academicClass->name }}{{ $student->section ? '/'.$student->section->name : '' }}
                                    @endif
                                </div>
                            </td>
                            @foreach ($monthDates as $date)
                                @php
                                    $dk     = $date->toDateString();
                                    $status = $row['days'][$dk] ?? 'none';
                                    $labels = ['present' => 'P', 'absent' => 'A', 'leave' => 'L', 'holiday' => 'H', 'weekoff' => 'W', 'none' => '–'];
                                @endphp
                                <td class="day-cell day-status-{{ $status }}" data-date="{{ $dk }}" data-status="{{ $status }}">
                                    <span class="status-badge {{ $status }} d-inline-flex align-items-center justify-content-center"
                                          title="{{ ucfirst($status) }} — {{ $date->format('d M') }}">
                                        {{ $labels[$status] ?? '–' }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="summary-col col-p" data-summary-kind="present">{{ $row['summary']['present'] }}</td>
                            <td class="summary-col col-a" data-summary-kind="absent">{{ $row['summary']['absent'] }}</td>
                            <td class="summary-col col-l" data-summary-kind="leave">{{ $row['summary']['leave'] }}</td>
                            <td class="summary-col col-h" data-summary-kind="holiday">{{ $row['summary']['holiday'] }}</td>
                            <td>
                                          <a href="{{ route('students.calendar', ['id' => $student->id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                                              class="btn btn-outline-primary btn-sm py-0 px-1 student-calendar-link" style="font-size:10px" title="Open individual calendar" data-student-label="{{ $student->full_name }}">
                                    <i class="bi bi-calendar3"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($studentsPage->hasPages())
        <div class="master-pagination master-pagination-sticky d-flex align-items-center justify-content-between gap-2 mt-2">
            <span class="master-pagination-meta">
                Page {{ $studentsPage->currentPage() }} of {{ $studentsPage->lastPage() }}
            </span>
            {{ $studentsPage->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
    @endif

    {{-- Quick absent list for today --}}
    @php
        $todayKey = now()->toDateString();
        $absentToday = collect($matrix)->filter(fn($row) => ($row['days'][$todayKey] ?? '') === 'absent');
        $showTodayPanel = $absentToday->count() > 0 && in_array($todayKey, collect($monthDates)->map->toDateString()->all());
    @endphp

    <div class="card app-card border-0 shadow-sm mt-4 border-start border-danger border-3 {{ $showTodayPanel ? '' : 'd-none' }}" id="absentTodayPanel">
        <div class="card-body">
            <h6 class="fw-semibold text-danger mb-2"><i class="bi bi-person-x me-1"></i>Absent Today ({{ now()->format('d M Y') }})</h6>
            <div class="d-flex flex-wrap gap-2" id="absentTodayBadges">
                @foreach ($absentToday as $row)
                          <a href="{{ route('students.calendar', $row['student']->id) }}"
                              class="badge bg-danger-subtle text-danger text-decoration-none student-calendar-link" style="font-size:12px" data-student-label="{{ $row['student']->full_name }}">
                        {{ $row['student']->full_name }}
                        <span class="text-danger-emphasis">({{ $row['student']->roll_no ?: $row['student']->admission_no }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

        </div>{{-- #masterCalendarContent --}}

</div>

<div class="modal fade" id="studentCalendarModal" tabindex="-1" aria-labelledby="studentCalendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentCalendarModalLabel">Student Calendar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentCalendarModalBody">
                <div class="text-body-secondary small">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelect = document.getElementById('masterClassFilter');
    const sectionSelect = document.getElementById('masterSectionFilter');
    const filterForm = document.getElementById('masterFilterForm');
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    const exportBtn = document.getElementById('exportBtn');
    const studentCalendarModalEl = document.getElementById('studentCalendarModal');
    const studentCalendarModalBody = document.getElementById('studentCalendarModalBody');
    const studentCalendarModalLabel = document.getElementById('studentCalendarModalLabel');
    const studentCalendarModal = studentCalendarModalEl && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(studentCalendarModalEl)
        : null;
    const contentRootSelector = '#masterCalendarContent';

    if (!classSelect || !sectionSelect) {
        return;
    }

    const selectedSection = Number(classSelect.dataset.selectedSection || 0);
    const endpoint = classSelect.closest('form')?.dataset.sectionsEndpoint || '';

    const syncExportLink = (params) => {
        if (!exportBtn) {
            return;
        }

        const exportParams = new URLSearchParams(params);
        exportParams.delete('page');
        const base = exportBtn.dataset.exportBase || exportBtn.getAttribute('href') || '';
        exportBtn.setAttribute('href', exportParams.toString() ? `${base}?${exportParams.toString()}` : base);
    };

    const replaceCalendarContentFromHtml = (html) => {
        const parser = new DOMParser();
        const nextDoc = parser.parseFromString(html, 'text/html');
        const nextRoot = nextDoc.querySelector(contentRootSelector);
        const currentRoot = document.querySelector(contentRootSelector);

        if (!nextRoot || !currentRoot) {
            return false;
        }

        currentRoot.innerHTML = nextRoot.innerHTML;

        const nextBadge = nextDoc.querySelector('.master-banner .badge');
        const currentBadge = document.querySelector('.master-banner .badge');
        if (nextBadge && currentBadge) {
            currentBadge.textContent = nextBadge.textContent;
        }

        return true;
    };

    const loadCalendarContent = async (targetUrl, pushHistory = true) => {
        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) {
                window.location.href = targetUrl;
                return;
            }

            const html = await response.text();
            const replaced = replaceCalendarContentFromHtml(html);
            if (!replaced) {
                window.location.href = targetUrl;
                return;
            }

            if (pushHistory) {
                window.history.pushState({ masterCalendarPjax: true }, '', targetUrl);
            }
        } catch (error) {
            console.error('Master calendar pjax load failed:', error);
            window.location.href = targetUrl;
        }
    };

    const renderOptions = (sections, keepSectionId = 0) => {
        const options = ['<option value="">— All Sections —</option>'];
        sections.forEach((section) => {
            const selected = keepSectionId > 0 && Number(section.id) === keepSectionId ? ' selected' : '';
            options.push(`<option value="${section.id}"${selected}>${section.name}</option>`);
        });
        sectionSelect.innerHTML = options.join('');
    };

    const setLoading = () => {
        sectionSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
    };

    const setEmpty = () => {
        sectionSelect.disabled = false;
        sectionSelect.innerHTML = '<option value="">No sections found</option>';
    };

    const setError = () => {
        sectionSelect.disabled = false;
        sectionSelect.innerHTML = '<option value="">Unable to load sections</option>';
    };

    const loadSections = async (classId, keepSectionId = 0) => {
        setLoading();

        try {
            const params = new URLSearchParams();
            if (classId) {
                params.set('class_id', String(classId));
            }

            const response = await fetch(`${endpoint}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                setError();
                return;
            }

            const payload = await response.json();
            const sections = Array.isArray(payload.sections) ? payload.sections : [];
            if (sections.length === 0) {
                setEmpty();
                return;
            }

            sectionSelect.disabled = false;
            renderOptions(sections, keepSectionId);
        } catch (error) {
            console.error('Master calendar section load failed:', error);
            setError();
        }
    };

    classSelect.addEventListener('change', function () {
        if (!endpoint) {
            return;
        }
        const classId = Number(this.value || 0);
        loadSections(classId, 0);
    });

    if (endpoint && Number(classSelect.value || 0) > 0) {
        loadSections(Number(classSelect.value || 0), selectedSection);
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams(new FormData(filterForm));
            const url = `${filterForm.action}?${params.toString()}`;
            syncExportLink(params);
            loadCalendarContent(url, true);
        });
    }

    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function (event) {
            event.preventDefault();

            if (filterForm) {
                filterForm.reset();
            }

            const url = this.dataset.resetHref || this.getAttribute('href') || "{{ route('master.calendar') }}";
            syncExportLink(new URLSearchParams());
            loadCalendarContent(url, true);
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', async function (event) {
            event.preventDefault();
            const exportUrl = this.getAttribute('href');
            if (!exportUrl) {
                return;
            }

            const originalHtml = this.innerHTML;
            this.classList.add('disabled');
            this.setAttribute('aria-disabled', 'true');
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            try {
                const response = await fetch(exportUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/csv,application/vnd.ms-excel,application/octet-stream,*/*',
                    },
                });

                if (!response.ok) {
                    window.location.href = exportUrl;
                    return;
                }

                const blob = await response.blob();
                const disposition = response.headers.get('content-disposition') || '';
                const filenameMatch = disposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i);
                const filename = decodeURIComponent(filenameMatch?.[1] || filenameMatch?.[2] || 'master-calendar-export.csv');

                const objectUrl = URL.createObjectURL(blob);
                const tempLink = document.createElement('a');
                tempLink.href = objectUrl;
                tempLink.download = filename;
                document.body.appendChild(tempLink);
                tempLink.click();
                tempLink.remove();
                URL.revokeObjectURL(objectUrl);
            } catch (error) {
                console.error('Master calendar export failed:', error);
                window.location.href = exportUrl;
            } finally {
                this.classList.remove('disabled');
                this.removeAttribute('aria-disabled');
                this.innerHTML = originalHtml;
            }
        });
    }

    const openStudentCalendarModal = async (url, title = 'Student Calendar') => {
        if (!studentCalendarModalBody || !studentCalendarModal) {
            window.location.href = url;
            return;
        }

        if (studentCalendarModalLabel) {
            studentCalendarModalLabel.textContent = title;
        }
        studentCalendarModalBody.innerHTML = '<div class="text-body-secondary small">Loading...</div>';
        studentCalendarModal.show();

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) {
                studentCalendarModal.hide();
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('.container-fluid') || doc.querySelector('.container') || doc.body;
            studentCalendarModalBody.innerHTML = content ? content.innerHTML : '<div class="text-danger small">Unable to load student calendar.</div>';

            const heading = doc.querySelector('h1.h3');
            if (heading && studentCalendarModalLabel) {
                studentCalendarModalLabel.textContent = heading.textContent.trim();
            }
        } catch (error) {
            console.error('Student calendar modal load failed:', error);
            studentCalendarModal.hide();
            window.location.href = url;
        }
    };

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.master-pagination a.page-link');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        event.preventDefault();
        loadCalendarContent(href, true);
    });

    document.addEventListener('click', function (event) {
        const studentLink = event.target.closest('.student-calendar-link');
        if (!studentLink) {
            return;
        }

        const href = studentLink.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        event.preventDefault();
        const label = studentLink.dataset.studentLabel || 'Student Calendar';
        openStudentCalendarModal(href, `Student Calendar - ${label}`);
    });

    if (studentCalendarModalBody) {
        studentCalendarModalBody.addEventListener('submit', function (event) {
            const form = event.target.closest('form');
            if (!form) {
                return;
            }

            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method !== 'GET') {
                return;
            }

            event.preventDefault();
            const formAction = form.getAttribute('action') || window.location.href;
            const params = new URLSearchParams(new FormData(form));
            const targetUrl = `${formAction}${formAction.includes('?') ? '&' : '?'}${params.toString()}`;
            openStudentCalendarModal(targetUrl, studentCalendarModalLabel?.textContent || 'Student Calendar');
        });

        studentCalendarModalBody.addEventListener('click', function (event) {
            const modalLink = event.target.closest('a[href]');
            if (!modalLink) {
                return;
            }

            if (modalLink.hasAttribute('data-bs-dismiss')) {
                return;
            }

            const href = modalLink.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            if (modalLink.target && modalLink.target !== '_self') {
                return;
            }

            if (modalLink.hasAttribute('download')) {
                return;
            }

            const absoluteUrl = new URL(href, window.location.origin);
            if (absoluteUrl.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            openStudentCalendarModal(absoluteUrl.toString(), studentCalendarModalLabel?.textContent || 'Student Calendar');
        });
    }

    window.addEventListener('popstate', function () {
        loadCalendarContent(window.location.href, false);
    });

    const entryTypeSelect = document.getElementById('entryTypeSelect');
    const titleFieldWrap = document.querySelector('[data-entry-title]');
    const weekdayFieldWrap = document.querySelector('[data-entry-weekday]');
    const recurringWrap = document.querySelector('[data-entry-recurring]');
    const recurringEndWrap = document.querySelector('[data-entry-recurring-end]');
    const recurringCheckbox = document.getElementById('weekoffRecurringCheck');
    const dayOffForm = document.getElementById('masterDayOffForm');
    const weekdayCheckboxes = dayOffForm?.querySelectorAll('[name="weekdays[]"]') || [];
    const recurringEndInput = dayOffForm?.querySelector('[name="recurring_end_date"]');
    const startDateInput = dayOffForm?.querySelector('[name="start_date"]');
    const endDateInput = dayOffForm?.querySelector('[name="end_date"]');
    const startLabel = document.getElementById('dayOffStartLabel');
    const endLabel = document.getElementById('dayOffEndLabel');
    const presetButtons = document.querySelectorAll('[data-weekoff-preset]');
    const feedbackBox = document.getElementById('dayOffFeedback');
    const saveButton = document.getElementById('dayOffSaveButton');
    const absentTodayPanel = document.getElementById('absentTodayPanel');
    const absentTodayBadges = document.getElementById('absentTodayBadges');
    const todayKey = '{{ now()->toDateString() }}';
    const labels = { present: 'P', absent: 'A', leave: 'L', holiday: 'H', weekoff: 'W', none: '–' };

    const formatDateInput = (date) => {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    const autoFillOneYearEndDate = (force = false) => {
        if (!startDateInput || !endDateInput || entryTypeSelect?.value !== 'weekoff' || !startDateInput.value) {
            return;
        }

        if (!force && endDateInput.dataset.userEdited === '1') {
            return;
        }

        const startDate = new Date(`${startDateInput.value}T00:00:00`);
        if (Number.isNaN(startDate.getTime())) {
            return;
        }

        startDate.setFullYear(startDate.getFullYear() + 1);
        startDate.setDate(startDate.getDate() - 1);
        endDateInput.value = formatDateInput(startDate);
        endDateInput.dataset.autoFilled = '1';
    };

    const showFeedback = (type, message) => {
        if (!feedbackBox) {
            return;
        }

        feedbackBox.className = `alert alert-${type === 'error' ? 'danger' : 'success'} mb-3`;
        feedbackBox.textContent = message;
        feedbackBox.classList.remove('d-none');
    };

    const resetFeedback = () => {
        if (!feedbackBox) {
            return;
        }

        feedbackBox.className = 'd-none mb-3';
        feedbackBox.textContent = '';
    };

    const syncEntryTypeFields = () => {
        if (!entryTypeSelect || !titleFieldWrap || !weekdayFieldWrap || !recurringWrap || !recurringEndWrap) {
            return;
        }

        const isWeekoff = entryTypeSelect.value === 'weekoff';
        titleFieldWrap.classList.toggle('d-none', isWeekoff);
        weekdayFieldWrap.classList.toggle('d-none', !isWeekoff);
        recurringWrap.classList.add('d-none');
        recurringEndWrap.classList.add('d-none');

        if (recurringCheckbox) {
            recurringCheckbox.checked = false;
        }
        if (recurringEndInput) {
            recurringEndInput.value = '';
        }

        if (startLabel) {
            startLabel.textContent = isWeekoff ? 'Applicable From' : 'Start Date';
        }
        if (endLabel) {
            endLabel.textContent = isWeekoff ? 'Applicable Till' : 'End Date';
        }

        if (isWeekoff) {
            autoFillOneYearEndDate(true);
        }
    };

    const updateHeaderForDate = (dateKey, entryType, title) => {
        const header = document.querySelector(`th[data-date="${dateKey}"]`);
        if (!header) {
            return;
        }

        header.classList.remove('day-head-holiday', 'day-head-weekoff', 'day-head-sunday');
        if (entryType === 'weekoff') {
            header.classList.add('day-head-weekoff');
        } else if (entryType === 'holiday') {
            header.classList.add('day-head-holiday');
        }
        header.setAttribute('title', title || header.getAttribute('title') || dateKey);
    };

    const updateCellStatus = (cell, status) => {
        cell.dataset.status = status;
        cell.className = `day-cell day-status-${status}`;
        const badge = cell.querySelector('.status-badge');
        if (badge) {
            badge.className = `status-badge ${status} d-inline-flex align-items-center justify-content-center`;
            badge.textContent = labels[status] || '–';
            const cellDate = cell.dataset.date || '';
            badge.setAttribute('title', `${status.charAt(0).toUpperCase() + status.slice(1)} — ${cellDate}`);
        }
    };

    const recomputeRowSummary = (row) => {
        const counters = { present: 0, absent: 0, leave: 0, holiday: 0 };
        row.querySelectorAll('td[data-date][data-status]').forEach((cell) => {
            const status = cell.dataset.status || 'none';
            if (status === 'present') {
                counters.present += 1;
            } else if (status === 'absent') {
                counters.absent += 1;
            } else if (status === 'leave') {
                counters.leave += 1;
            } else if (status === 'holiday' || status === 'weekoff') {
                counters.holiday += 1;
            }
        });

        Object.entries(counters).forEach(([key, value]) => {
            const target = row.querySelector(`[data-summary-kind="${key}"]`);
            if (target) {
                target.textContent = String(value);
            }
        });
    };

    const recomputeOverallSummary = () => {
        const totals = { present: 0, absent: 0, leave: 0 };
        document.querySelectorAll('tr[data-student-row]').forEach((row) => {
            totals.present += Number(row.querySelector('[data-summary-kind="present"]')?.textContent || 0);
            totals.absent += Number(row.querySelector('[data-summary-kind="absent"]')?.textContent || 0);
            totals.leave += Number(row.querySelector('[data-summary-kind="leave"]')?.textContent || 0);
        });

        const presentNode = document.getElementById('overallPresentCount');
        const absentNode = document.getElementById('overallAbsentCount');
        const leaveNode = document.getElementById('overallLeaveCount');
        if (presentNode) presentNode.textContent = String(totals.present);
        if (absentNode) absentNode.textContent = String(totals.absent);
        if (leaveNode) leaveNode.textContent = String(totals.leave);
    };

    const updateAbsentTodayPanel = () => {
        if (!absentTodayPanel || !absentTodayBadges) {
            return;
        }

        const badges = [];
        document.querySelectorAll('tr[data-student-row]').forEach((row) => {
            const todayCell = row.querySelector(`td[data-date="${todayKey}"]`);
            if (!todayCell || todayCell.dataset.status !== 'absent') {
                return;
            }

            const studentId = row.dataset.studentId || '';
            const studentName = row.dataset.studentName || 'Student';
            const studentRoll = row.dataset.studentRoll || studentId;
            badges.push(`<a href="/students/${studentId}/calendar" class="badge bg-danger-subtle text-danger text-decoration-none student-calendar-link" style="font-size:12px" data-student-label="${studentName}">${studentName}<span class="text-danger-emphasis">(${studentRoll})</span></a>`);
        });

        absentTodayBadges.innerHTML = badges.join('');
        absentTodayPanel.classList.toggle('d-none', badges.length === 0);
    };

    const applySavedDatesToCalendar = (createdDates, entryType, title) => {
        if (!Array.isArray(createdDates) || createdDates.length === 0) {
            return;
        }

        const visibleDateSet = new Set(createdDates);
        visibleDateSet.forEach((dateKey) => updateHeaderForDate(dateKey, entryType, title));

        document.querySelectorAll('tr[data-student-row]').forEach((row) => {
            row.querySelectorAll('td[data-date]').forEach((cell) => {
                if (visibleDateSet.has(cell.dataset.date || '')) {
                    updateCellStatus(cell, entryType);
                }
            });
            recomputeRowSummary(row);
        });

        recomputeOverallSummary();
        updateAbsentTodayPanel();
    };

    if (entryTypeSelect) {
        syncEntryTypeFields();
        entryTypeSelect.addEventListener('change', function () {
            if (endDateInput) {
                endDateInput.dataset.userEdited = '0';
            }
            syncEntryTypeFields();
        });
    }

    if (startDateInput) {
        startDateInput.addEventListener('change', function () {
            if (endDateInput && endDateInput.dataset.autoFilled === '1') {
                endDateInput.dataset.userEdited = '0';
            }
            autoFillOneYearEndDate(true);
        });
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', function () {
            this.dataset.userEdited = '1';
            this.dataset.autoFilled = '0';
        });
    }

    presetButtons.forEach((button) => {
        button.addEventListener('click', function () {
            if (!entryTypeSelect || !weekdayCheckboxes || !startDateInput) {
                return;
            }

            entryTypeSelect.value = 'weekoff';
            const targetWeekday = this.dataset.weekday || 'sunday';
            weekdayCheckboxes.forEach((checkbox) => {
                checkbox.checked = checkbox.value === targetWeekday;
            });

            if (!startDateInput.value) {
                startDateInput.value = formatDateInput(new Date());
            }

            if (endDateInput) {
                endDateInput.dataset.userEdited = '0';
            }
            syncEntryTypeFields();
        });
    });

    if (dayOffForm) {
        dayOffForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            resetFeedback();

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            }

            try {
                const response = await fetch(dayOffForm.action, {
                    method: 'POST',
                    body: new FormData(dayOffForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errorText = payload?.message || Object.values(payload?.errors || {}).flat().join(' ') || 'Unable to save day off setup.';
                    showFeedback('error', errorText);
                    return;
                }

                showFeedback('success', payload.message || 'Saved successfully.');
                applySavedDatesToCalendar(payload.created_dates || [], payload.entry_type || 'weekoff', payload.title || 'Week Off');
            } catch (error) {
                console.error('Master day off save failed:', error);
                showFeedback('error', 'Unable to save right now. Please try again.');
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                }
            }
        });
    }
});
</script>
@endsection
