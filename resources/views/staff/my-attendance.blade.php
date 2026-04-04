@extends('layouts.app')

@section('content')
@php
    $attendancePercentage = max(0, min(100, (float) ($summary['percentage'] ?? 0)));
@endphp
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Staff Attendance</span>
            <h1 class="h3 mb-1">My Attendance</h1>
            <p class="text-body-secondary mb-0">Monthly personal attendance details for {{ $staffName }}.</p>
        </div>
        <span class="badge text-bg-light border px-3 py-2">{{ $monthLabel }}</span>
    </div>

    @if (! $hasStaffMapping)
        <div class="alert alert-warning" role="alert">
            Staff mapping not found for this account. Please contact admin.
        </div>
    @endif

    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('my.attendance') }}" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label form-label-sm fw-semibold mb-1">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        @foreach ($monthOptions as $month)
                            <option value="{{ $month['value'] }}" @selected((int) $selectedMonth === (int) $month['value'])>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label form-label-sm fw-semibold mb-1">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label form-label-sm fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel me-1"></i>Apply
                    </button>
                    <a href="{{ route('my.attendance') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#0ea5e9; color:#fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['total'] }}</div>
                    <div>Total</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#22c55e; color:#fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['present'] }}</div>
                    <div>Present</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#f59e0b; color:#111;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['late'] }}</div>
                    <div>Late</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#dc2626; color:#fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['absent'] }}</div>
                    <div>Absent</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#64748b; color:#fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['leave'] }}</div>
                    <div>Leave</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card app-card h-100 border-0" style="background:#4f46e5; color:#fff;">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-semibold">{{ $summary['percentage'] }}%</div>
                    <div>Rate</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Daily Records</h5>
            <small class="text-body-secondary">In/Out timings from capture payload</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Method</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $status = strtolower((string) $row['status']);
                            $badgeClass = 'bg-secondary';
                            if (in_array($status, ['present'], true)) {
                                $badgeClass = 'bg-success';
                            } elseif ($status === 'late') {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($status === 'absent') {
                                $badgeClass = 'bg-danger';
                            } elseif ($status === 'leave') {
                                $badgeClass = 'bg-info text-dark';
                            }
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row['date']->format('d M Y') }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                            <td>{{ $row['in_time'] ?: '--:--' }}</td>
                            <td>{{ $row['out_time'] ?: '--:--' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $row['method'])) }}</td>
                            <td>{{ $row['remarks'] !== '' ? $row['remarks'] : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">No attendance records found for selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer border-0 bg-transparent">
            <div class="progress" style="height: 6px;">
                <div class="progress-bar js-attendance-progress" role="progressbar" style="width: 0%;" data-progress="{{ $attendancePercentage }}" aria-valuenow="{{ $attendancePercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-body-secondary">Attendance rate for selected month: {{ $attendancePercentage }}%</small>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-attendance-progress').forEach(function (bar) {
        const progress = Number(bar.dataset.progress || 0);
        bar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
    });
});
</script>
@endsection
