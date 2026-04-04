@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="hero-panel p-4 p-lg-5 mb-4 text-white">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="eyebrow text-white-50">Student Portal</span>
                <h1 class="h3 mb-2">Welcome, {{ $student->full_name }}</h1>
                <p class="mb-4 text-white-50">Track attendance, fee receipts, exam performance, and study material from one place.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-sm" href="{{ route('student.attendance') }}">Attendance</a>
                    <a class="btn btn-outline-light btn-sm" href="{{ route('student.fees') }}">Fee Receipts</a>
                    <a class="btn btn-outline-light btn-sm" href="{{ route('student.results') }}">Result Insights</a>
                    <a class="btn btn-outline-light btn-sm" href="{{ route('student.books') }}">Open Library</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="rounded-4 border border-white border-opacity-25 bg-white bg-opacity-10 p-4">
                    <div class="small text-white-50 mb-2">Today at a glance</div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Class</span>
                        <strong>{{ optional($student->academicClass)->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Section</span>
                        <strong>{{ optional($student->section)->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Roll No</span>
                        <strong>{{ $student->roll_no }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Guardian</span>
                        <strong>{{ $student->guardian_name ?: 'N/A' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-sm-6 col-xl-4">
                <div class="metric-card h-100">
                    <div class="metric-label">{{ $stat['label'] }}</div>
                    <div class="metric-value">{{ $stat['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @foreach ($moduleCards as $card)
            <div class="col-md-6 col-xl-3">
                <a class="text-decoration-none" href="{{ $card['route'] }}">
                    <div class="card app-card h-100 student-module-card student-module-card-{{ $card['tone'] }}">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="small text-body-secondary mb-2">{{ $card['title'] }}</div>
                                    <div class="h4 mb-1 text-body-emphasis">{{ $card['value'] }}</div>
                                </div>
                                <span class="student-module-icon text-{{ $card['tone'] }}">
                                    <i class="bi {{ $card['icon'] }}"></i>
                                </span>
                            </div>
                            <div class="small fw-semibold mb-2 text-body-emphasis">{{ $card['meta'] }}</div>
                            <div class="small text-body-secondary mb-2">{{ $card['detail'] }}</div>
                            <p class="small text-body-secondary mb-3">{{ $card['description'] }}</p>
                            <span class="small fw-semibold text-{{ $card['tone'] }}">{{ $card['cta'] }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Profile Snapshot</h2>
                    <div class="stack-list">
                        <div class="stack-item"><span>Admission No</span><strong>{{ $student->admission_no }}</strong></div>
                        <div class="stack-item"><span>Class</span><strong>{{ optional($student->academicClass)->name ?? 'N/A' }}</strong></div>
                        <div class="stack-item"><span>Section</span><strong>{{ optional($student->section)->name ?? 'N/A' }}</strong></div>
                        <div class="stack-item"><span>Guardian</span><strong>{{ $student->guardian_name ?: 'N/A' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Quick Access</h2>
                    <div class="quick-links">
                        <a class="quick-link" href="{{ route('student.profile') }}">My Details</a>
                        <a class="quick-link" href="{{ route('student.attendance') }}">Attendance</a>
                        <a class="quick-link" href="{{ route('student.fees') }}">Fee Receipts</a>
                        <a class="quick-link" href="{{ route('student.results') }}">Results</a>
                        <a class="quick-link" href="{{ route('student.exams') }}">My Exams</a>
                        <a class="quick-link" href="{{ route('student.books') }}">Books & Materials</a>
                        <a class="quick-link" href="{{ route('student.password.edit') }}">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <span class="eyebrow">Notice Board</span>
                            <h2 class="h5 mb-0">Exam & Result Notifications</h2>
                        </div>
                        <span class="badge text-bg-light border">Class / Section Filtered</span>
                    </div>
                    <div class="stack-list">
                        @forelse ($notifications as $notification)
                            <div class="stack-item align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $notification->title }}</div>
                                    <div class="small text-body-secondary">{{ $notification->message }}</div>
                                    <div class="small text-body-secondary mt-1">{{ $notification->publish_date?->format('d M Y') }}</div>
                                </div>
                                <span class="badge text-bg-light border">{{ ucfirst($notification->audience) }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">No notifications available right now.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow">Live Insights</span>
                            <h2 class="h5 mb-0">Attendance, Fee Dues, and Result Trends</h2>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4"><canvas id="attendanceChart"></canvas></div>
                        <div class="col-md-4"><canvas id="feesChart"></canvas></div>
                        <div class="col-md-4"><canvas id="resultsChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="dashboard-chart-data">@json($chartData)</script>
<style>
    .student-module-card {
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        border: 1px solid rgba(15, 23, 42, 0.08);
    }

    .student-module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    }

    .student-module-card-success:hover {
        border-color: rgba(22, 163, 74, 0.35);
    }

    .student-module-card-warning:hover {
        border-color: rgba(245, 158, 11, 0.35);
    }

    .student-module-card-primary:hover {
        border-color: rgba(17, 103, 177, 0.35);
    }

    .student-module-card-info:hover {
        border-color: rgba(13, 202, 240, 0.35);
    }

    .student-module-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.05);
        font-size: 1.25rem;
    }
</style>
@endsection