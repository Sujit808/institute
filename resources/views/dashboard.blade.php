@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="hero-panel p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="eyebrow text-white-50">Overview</span>
                    <h1 class="display-6 fw-semibold text-white mb-2">School / College Management Dashboard</h1>
                    <p class="text-white-50 mb-0">Track attendance, fees, exams, notifications, and upcoming events from one role-aware dashboard.</p>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        @foreach ($stats as $stat)
                            <div class="col-6">
                                <div class="metric-card metric-card-inverse h-100">
                                    <span class="metric-label">{{ $stat['label'] }}</span>
                                    <div class="metric-value">{{ $stat['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="eyebrow">Insights</span>
                                <h2 class="h5 mb-0">Attendance, Fees, and Results</h2>
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
            <div class="col-xl-4">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="eyebrow">Quick Links</span>
                        <h2 class="h5 mb-3">Open a module</h2>
                        <div class="quick-links">
                            @foreach ($quickLinks as $link)
                                <a class="quick-link" href="{{ $link['route'] }}">{{ $link['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="eyebrow">Schedule</span>
                        <h2 class="h5 mb-3">Upcoming Holidays / Events</h2>
                        <div class="stack-list">
                            @forelse ($upcomingItems as $item)
                                <div class="stack-item">
                                    <div>
                                        <div class="fw-semibold">{{ $item['title'] }}</div>
                                        <div class="small text-body-secondary">{{ $item['date'] }}</div>
                                    </div>
                                    <span class="badge text-bg-light border">{{ $item['type'] }}</span>
                                </div>
                            @empty
                                <p class="text-body-secondary mb-0">No upcoming items.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="eyebrow">Notifications</span>
                        <h2 class="h5 mb-3">Latest Notice Board</h2>
                        <div class="stack-list">
                            @forelse ($notifications as $notification)
                                <div class="stack-item align-items-start">
                                    <div>
                                        <div class="fw-semibold">{{ $notification->title }}</div>
                                        <div class="small text-body-secondary">{{ $notification->message }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge text-bg-light border d-block mb-1">{{ ucfirst($notification->audience) }}</span>
                                        @if($notification->academic_class_id)
                                            <span class="badge text-bg-light border">Class Targeted</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-body-secondary mb-0">No notifications available.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="dashboard-chart-data">@json($chartData)</script>
@endsection
