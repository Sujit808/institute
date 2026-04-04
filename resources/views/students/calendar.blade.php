@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Student Operations</span>
            <h1 class="h3 mb-1">Student Calendar - {{ $student->full_name }}</h1>
            <p class="text-body-secondary mb-0">
                Date-wise details from {{ $joinDate->format('d M Y') }} onward.
                <span class="ms-2">Roll No: {{ $student->roll_no ?: '-' }}</span>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Back to Students
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card app-card h-100 border-0" style="background: #22c55e; color: #fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['present'] }}</div>
                    <div>Present</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card app-card h-100 border-0" style="background: #dc2626; color: #fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['absent'] }}</div>
                    <div>Absent</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card app-card h-100 border-0" style="background: #d9a13b; color: #111;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['holiday'] }}</div>
                    <div>Holiday / Weekoff</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card app-card h-100 border-0" style="background: #f59e9e; color: #111;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['leave'] }}</div>
                    <div>Leave</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-9">
            <div class="card app-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="mb-0">{{ $monthLabel }}</h5>
                        <form method="GET" action="{{ route('students.calendar', $student->id) }}" class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" name="month" style="width: 160px;">
                                @foreach ($monthOptions as $month)
                                    <option value="{{ $month['value'] }}" {{ (int) $selectedMonth === (int) $month['value'] ? 'selected' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" name="year" style="width: 110px;">
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Go</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 student-calendar-table">
                            <thead>
                                <tr class="text-center">
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                    <th>Saturday</th>
                                    <th>Sunday</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($calendarRows as $week)
                                    <tr>
                                        @foreach ($week as $cell)
                                            @php
                                                $cellClass = 'calendar-none';
                                                if ($cell['type'] === 'present') {
                                                    $cellClass = 'calendar-present';
                                                } elseif ($cell['type'] === 'absent') {
                                                    $cellClass = 'calendar-absent';
                                                } elseif ($cell['type'] === 'holiday') {
                                                    $cellClass = 'calendar-holiday';
                                                } elseif ($cell['type'] === 'weekoff') {
                                                    $cellClass = 'calendar-weekoff';
                                                } elseif ($cell['type'] === 'leave') {
                                                    $cellClass = 'calendar-leave';
                                                }
                                            @endphp
                                            <td class="calendar-cell {{ $cell['isCurrentMonth'] ? '' : 'calendar-outside' }} {{ $cell['isBeforeJoin'] ? 'calendar-before-join' : '' }} {{ $cellClass }} {{ $cell['date']->isToday() ? 'calendar-today' : '' }}">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="calendar-date">{{ $cell['date']->day }}</span>
                                                    @if ($cell['meta'])
                                                        <span class="calendar-pill">{{ $cell['meta'] }}</span>
                                                    @elseif ($cell['date']->isToday())
                                                        <span class="calendar-today-tag">Today</span>
                                                    @endif
                                                </div>
                                                <div class="calendar-label">
                                                    @if ($cell['isBeforeJoin'])
                                                        Not Joined
                                                    @elseif ($cell['label'])
                                                        {{ $cell['label'] }}
                                                    @elseif ($cell['isCurrentMonth'])
                                                        --
                                                    @endif
                                                </div>
                                                @if ($cell['inTime'] || $cell['outTime'])
                                                    <div class="calendar-time-row mt-1">
                                                        <span>{{ $cell['inTime'] ?: '--:--' }}</span>
                                                        <span>{{ $cell['outTime'] ?: '--:--' }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                        <span class="legend-item"><span class="legend-dot" style="background: #22c55e;"></span>Present</span>
                        <span class="legend-item"><span class="legend-dot" style="background: #dc2626;"></span>Absent</span>
                        <span class="legend-item"><span class="legend-dot" style="background: #d9a13b;"></span>Holiday</span>
                        <span class="legend-item"><span class="legend-dot" style="background: #7c3aed;"></span>Week Off</span>
                        <span class="legend-item"><span class="legend-dot" style="background: #f59e9e;"></span>Leave</span>
                        <span class="legend-item"><span class="legend-dot" style="background: #94a3b8;"></span>Not Joined</span>
                    </div>
                </div>
            </div>

            <div class="card app-card mt-4">
                <div class="card-header border-0 bg-transparent d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Leave Summary {{ $monthLabel }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Leave</th>
                                <th class="text-center">Opening</th>
                                <th class="text-center">Credit</th>
                                <th class="text-center">Consume</th>
                                <th class="text-center">Late Ded</th>
                                <th class="text-center">Encash</th>
                                <th class="text-center">Pending</th>
                                <th class="text-center">Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaveSummaryRows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['leave'] }}</td>
                                    <td class="text-center">{{ number_format((float) $row['opening'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['credit'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['consume'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['late_ded'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['encash'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['pending'], 2) }}</td>
                                    <td class="text-center">{{ number_format((float) $row['closing'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-body-secondary py-3">No leave summary available for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card app-card">
                <div class="card-header border-0 bg-transparent">
                    <h6 class="mb-0">Pending Approval</h6>
                </div>
                <div class="card-body">
                    @forelse ($pendingLeaves as $pending)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ ucfirst((string) $pending->leave_type) }} Leave</div>
                            <small class="text-body-secondary d-block">
                                {{ \Illuminate\Support\Carbon::parse($pending->start_date)->format('d M Y') }}
                                -
                                {{ \Illuminate\Support\Carbon::parse($pending->end_date)->format('d M Y') }}
                            </small>
                            <small class="text-warning">Status: Pending</small>
                        </div>
                    @empty
                        <p class="text-body-secondary mb-0">No pending leave request.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .student-calendar-table thead th {
        background: #4f5dd9;
        color: #fff;
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.78rem;
        border-color: rgba(255, 255, 255, 0.22);
    }

    .calendar-cell {
        min-width: 130px;
        height: 95px;
        vertical-align: top;
        font-size: 0.88rem;
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .calendar-date {
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 0.82rem;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(15, 23, 42, 0.12);
    }

    .calendar-pill {
        font-size: 0.72rem;
        background: rgba(255, 255, 255, 0.75);
        border-radius: 999px;
        padding: 0.05rem 0.42rem;
    }

    @keyframes today-wave {
        0%   { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.55); }
        60%  { box-shadow: 0 0 0 9px rgba(220, 38, 38, 0.1); }
        100% { box-shadow: 0 0 0 14px rgba(220, 38, 38, 0); }
    }

    @keyframes today-chip-pulse {
        0%, 100% { transform: scale(1);    box-shadow: 0 0 0 0   rgba(220, 38, 38, 0.65); }
        50%       { transform: scale(1.12); box-shadow: 0 0 0 5px rgba(220, 38, 38, 0); }
    }

    @keyframes today-tag-blink {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.5; }
    }

    .calendar-today-tag {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        background: #dc2626;
        color: #fff;
        border-radius: 999px;
        padding: 0.06rem 0.45rem;
        line-height: 1;
        animation: today-tag-blink 1.1s ease-in-out infinite;
    }

    .calendar-label {
        font-weight: 500;
    }

    .calendar-time-row {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.78rem;
        font-weight: 600;
        border-top: 1px dashed rgba(15, 23, 42, 0.15);
        padding-top: 0.2rem;
    }

    .calendar-cell {
        border-left: 3px solid transparent;
    }

    .calendar-present {
        background: #dcfce7;
        color: #14532d;
        border-left-color: #16a34a;
    }

    .calendar-present .calendar-date {
        background: #16a34a;
        border-color: #15803d;
        color: #fff;
    }

    .calendar-absent {
        background: #fee2e2;
        color: #7f1d1d;
        border-left-color: #dc2626;
    }

    .calendar-absent .calendar-date {
        background: #dc2626;
        border-color: #b91c1c;
        color: #fff;
    }

    .calendar-holiday {
        background: #fef3c7;
        color: #78350f;
        border-left-color: #d97706;
    }

    .calendar-holiday .calendar-date {
        background: #d97706;
        border-color: #b45309;
        color: #fff;
    }

    .calendar-weekoff {
        background: #ede9fe;
        color: #4c1d95;
        border-left-color: #7c3aed;
    }

    .calendar-weekoff .calendar-date {
        background: #7c3aed;
        border-color: #6d28d9;
        color: #fff;
    }

    .calendar-leave {
        background: #ffe4e6;
        color: #9f1239;
        border-left-color: #e11d48;
    }

    .calendar-leave .calendar-date {
        background: #e11d48;
        border-color: #be123c;
        color: #fff;
    }

    .calendar-none {
        background: #f3f4f6;
        color: #374151;
        border-left-color: #9ca3af;
    }

    .calendar-none .calendar-date {
        background: #e5e7eb;
        border-color: #cbd5e1;
        color: #374151;
    }

    .calendar-before-join {
        background: #e2e8f0;
        color: #334155;
        border-left-color: #94a3b8;
    }

    .calendar-before-join .calendar-date {
        background: #94a3b8;
        border-color: #64748b;
        color: #fff;
    }

    .calendar-outside {
        opacity: 0.7;
    }

    .calendar-today {
        position: relative;
        animation: today-wave 2s ease-out infinite;
    }

    .calendar-today .calendar-date {
        background: #dc2626 !important;
        border-color: #b91c1c !important;
        color: #fff !important;
        animation: today-chip-pulse 1.5s ease-in-out infinite;
    }

    .calendar-present .calendar-pill,
    .calendar-absent .calendar-pill,
    .calendar-holiday .calendar-pill,
    .calendar-weekoff .calendar-pill,
    .calendar-leave .calendar-pill,
    .calendar-none .calendar-pill,
    .calendar-before-join .calendar-pill {
        background: rgba(255, 255, 255, 0.85);
    }

    .legend-item {
        display: inline-block;
        margin-right: 0.9rem;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .legend-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.35rem;
        vertical-align: middle;
    }

    @media (max-width: 991.98px) {
        .calendar-cell {
            min-width: 118px;
            height: 88px;
            font-size: 0.82rem;
        }
    }
</style>
@endsection
