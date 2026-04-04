@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Admin Review</span>
            <h1 class="h3 mb-1">Flagged Exam Attempts</h1>
            <p class="text-body-secondary mb-0">Locked attempts and tab-switch violations are listed here.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric-card"><div class="metric-label">Flagged Attempts</div><div class="metric-value">{{ $summary['total_flagged'] }}</div></div>
        </div>
        <div class="col-md-4">
            <div class="metric-card"><div class="metric-label">Locked Attempts</div><div class="metric-value">{{ $summary['locked'] }}</div></div>
        </div>
        <div class="col-md-4">
            <div class="metric-card"><div class="metric-label">Total Violations</div><div class="metric-value">{{ $summary['total_violations'] }}</div></div>
        </div>
    </div>

    <div class="card app-card mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('exam-attempts.review.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Filter by Exam</label>
                    <select name="exam_id" class="form-select">
                        <option value="">All Exams</option>
                        @foreach ($exams as $exam)
                            <option value="{{ $exam->id }}" @selected((int) $examId === (int) $exam->id)>{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('exam-attempts.review.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table align-middle app-table mb-0">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Student</th>
                        <th>Set</th>
                        <th>Status</th>
                        <th>Tab Switch Count</th>
                        <th>Locked At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt->exam->name ?? 'N/A' }}</td>
                            <td>{{ $attempt->student->full_name ?? 'N/A' }}</td>
                            <td>{{ $attempt->set_code }}</td>
                            <td><span class="badge text-bg-light border text-capitalize">{{ $attempt->status }}</span></td>
                            <td>{{ $attempt->tab_switch_count }}</td>
                            <td>{{ optional($attempt->locked_at)->format('d M Y h:i A') ?: '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('exam-attempts.review.show', $attempt->id) }}" class="btn btn-sm btn-outline-primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-body-secondary">No flagged attempts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attempts->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
