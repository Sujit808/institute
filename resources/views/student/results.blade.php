@extends('layouts.app')

@section('content')
@php
    $averageValue = (float) $summary['average'];
    $avgGradeColorClass = $averageValue >= 90 ? 'text-success' : ($averageValue >= 70 ? 'text-primary' : ($averageValue >= 50 ? 'text-warning' : 'text-danger'));
@endphp
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Academic Performance</span>
            <h1 class="h3 mb-1">Exam Results</h1>
            <p class="text-body-secondary mb-0">View your exam results, scores, and performance analytics.</p>
        </div>
        @if($results->count() > 0)
            <a href="{{ route('student.results.pdf') }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf me-2"></i>Download Result PDF
            </a>
        @endif
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Total Exams</span>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Overall Average</span>
                    <h4 class="mb-0" style="color: #1167b1;">{{ $summary['average'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Average Grade</span>
                    <h4 class="mb-0">
                        @php
                            $avgGrade = match(true) {
                                $summary['average'] >= 90 => 'A+',
                                $summary['average'] >= 80 => 'A',
                                $summary['average'] >= 70 => 'B',
                                $summary['average'] >= 60 => 'C',
                                $summary['average'] >= 50 => 'D',
                                default => 'F'
                            };
                        @endphp
                        <span class="{{ $avgGradeColorClass }}">{{ $avgGrade }}</span>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- All Results Table -->
    @if($results->count() > 0)
        <div class="card app-card mb-4">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0">All Results</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam</th>
                            <th>Subject</th>
                            <th class="text-end">Marks</th>
                            <th>Grade</th>
                            <th>Percentage</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php
                                $resultMarks = max(0, min(100, (float) $result->marks_obtained));
                                $resultBadgeClass = $resultMarks >= 90 ? 'bg-success' : ($resultMarks >= 70 ? 'bg-primary' : ($resultMarks >= 50 ? 'bg-warning text-dark' : 'bg-danger'));
                            @endphp
                            <tr>
                                <td class="fw-500">{{ optional($result->exam)->name ?? 'Exam' }}</td>
                                <td>{{ optional($result->subject)->name ?? 'Subject' }}</td>
                                <td class="text-end fw-500">{{ $result->marks_obtained }}/100</td>
                                <td>
                                    <span class="badge {{ $resultBadgeClass }}">
                                        {{ $result->grade ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress" style="width: 60px; height: 4px;">
                                            <div class="progress-bar js-result-progress" data-progress="{{ $resultMarks }}"></div>
                                        </div>
                                        <span>{{ $result->marks_obtained }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if($result->remarks)
                                        <small>{{ $result->remarks }}</small>
                                    @else
                                        <small class="text-body-secondary">—</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Subject-wise Performance -->
        @if($subjectData->count() > 0)
            <div class="card app-card mb-4">
                <div class="card-header border-0 bg-transparent">
                    <h5 class="mb-0">Performance by Subject</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($subjectData as $subject)
                            @php
                                $subjectAverage = max(0, min(100, (float) $subject['average']));
                                $subjectBadgeClass = $subjectAverage >= 90 ? 'bg-success' : ($subjectAverage >= 70 ? 'bg-primary' : ($subjectAverage >= 50 ? 'bg-warning text-dark' : 'bg-danger'));
                                $subjectProgressClass = $subjectAverage >= 90 ? 'bg-success' : ($subjectAverage >= 70 ? 'bg-primary' : ($subjectAverage >= 50 ? 'bg-warning' : 'bg-danger'));
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="p-3 border rounded-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">{{ $subject['subject'] }}</h6>
                                        <span class="badge {{ $subjectBadgeClass }}">
                                            {{ $subject['grade'] }}
                                        </span>
                                    </div>
                                    <div class="progress mb-2" style="height: 6px;">
                                        <div class="progress-bar js-result-progress {{ $subjectProgressClass }}" data-progress="{{ $subjectAverage }}"></div>
                                    </div>
                                    <div class="text-center">
                                        <strong>{{ $subject['average'] }}/100</strong>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Performance Chart (HTML Canvas) -->
        <div class="card app-card">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0">Performance Chart</h5>
            </div>
            <div class="card-body">
                <canvas
                    id="performanceChart"
                    style="height: 300px;"
                    data-labels='@json($subjectData->pluck("subject")->values()->all())'
                    data-values='@json($subjectData->pluck("average")->values()->all())'
                ></canvas>
            </div>
        </div>
    @else
        <div class="card app-card">
            <div class="card-body p-4 text-center text-body-secondary">
                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="mt-3 mb-0">No exam results available yet.</p>
            </div>
        </div>
    @endif

    <!-- Grade Scale Info -->
    <div class="alert alert-info mt-4" role="alert">
        <h6 class="fw-bold mb-2">📋 Grading Scale</h6>
        <div class="row row-cols-3 row-cols-md-5 gap-3">
            <div>
                <span class="badge bg-success" style="width: 100%;">A+ : 90-100</span>
            </div>
            <div>
                <span class="badge bg-primary" style="width: 100%;">A : 80-89</span>
            </div>
            <div>
                <span class="badge" style="background-color: #1167b1; width: 100%;">B : 70-79</span>
            </div>
            <div>
                <span class="badge bg-warning" style="width: 100%;">C : 60-69</span>
            </div>
            <div>
                <span class="badge bg-danger" style="width: 100%;">F : Below 60</span>
            </div>
        </div>
    </div>
</div>

@if($results->count() > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-result-progress').forEach(function (element) {
                element.style.width = element.dataset.progress + '%';
            });

            const canvas = document.getElementById('performanceChart');
            const ctx = canvas.getContext('2d');
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const data = JSON.parse(canvas.dataset.values || '[]');

            // Color based on marks
            const colors = data.map(mark => {
                if (mark >= 90) return '#28a745'; // Green
                if (mark >= 70) return '#1167b1'; // Blue
                if (mark >= 50) return '#ff9500'; // Orange
                return '#dc3545'; // Red
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Subject Average Score',
                        data: data,
                        backgroundColor: colors,
                        borderColor: colors,
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'x',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endif
@endsection
