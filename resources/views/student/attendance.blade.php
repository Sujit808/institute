@extends('layouts.app')

@section('content')
@php
    $attendancePercentage = max(0, min(100, (float) $summary['percentage']));
@endphp
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Attendance Tracking</span>
            <h1 class="h3 mb-1">Your Attendance</h1>
            <p class="text-body-secondary mb-0">View your daily attendance record and monthly statistics.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Total Days</span>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Present</span>
                    <h4 class="mb-0" style="color: #28a745;">{{ $summary['present'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Absent</span>
                    <h4 class="mb-0" style="color: #dc3545;">{{ $summary['absent'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card text-center">
                <div class="card-body p-4">
                    <div style="font-size: 32px; color: #1167b1; margin-bottom: 4px;">{{ $summary['percentage'] }}%</div>
                    <small class="text-body-secondary">Attendance Rate</small>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar js-progress-bar bg-primary" data-progress="{{ $attendancePercentage }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    @if($monthlyData->count() > 0)
        <div class="card app-card mb-4">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0">Monthly Attendance Summary</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center">Total</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyData as $month)
                            <tr>
                                <td class="fw-500">{{ $month['month'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $month['present'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $month['absent'] }}</span>
                                </td>
                                <td class="text-center">{{ $month['present'] + $month['absent'] }}</td>
                                <td>
                                    @php
                                        $monthPercentage = max(0, min(100, (float) $month['percentage']));
                                    @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress" style="width: 80px; height: 4px;">
                                            <div class="progress-bar js-progress-bar" data-progress="{{ $monthPercentage }}"></div>
                                        </div>
                                        <span>{{ $month['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Daily Attendance Record -->
    <div class="card app-card">
        <div class="card-header border-0 bg-transparent">
            <h5 class="mb-0">Daily Records</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Day</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td class="fw-500">{{ $attendance->attendance_date->format('d M Y') }}</td>
                            <td>
                                @if($attendance->status === 'present')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Present
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Absent
                                    </span>
                                @endif
                            </td>
                            <td>{{ $attendance->attendance_date->format('l') }}</td>
                            <td>
                                @if($attendance->attendance_method)
                                    <small class="text-body-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $attendance->attendance_method)) }}
                                    </small>
                                @else
                                    <small class="text-body-secondary">Manual</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-4">
                                No attendance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Statistics Info -->
    @if($summary['total'] > 0)
        <div class="alert alert-info mt-4" role="alert">
            <h6 class="fw-bold mb-2">📊 Attendance Information</h6>
            <ul class="mb-0 ps-3">
                <li>Regular attendance is important for your academic progress.</li>
                <li>Maintain at least 75% attendance to be eligible for exams.</li>
                <li>For any discrepancies, contact your class teacher.</li>
            </ul>
        </div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-progress-bar').forEach(function (element) {
            element.style.width = element.dataset.progress + '%';
        });
    });
</script>
@endsection
