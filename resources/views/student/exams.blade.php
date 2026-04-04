@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Exam Access</span>
            <h1 class="h3 mb-1">My Exams</h1>
            <p class="text-body-secondary mb-0">Your assigned question set is <strong>Set {{ $assignedSet }}</strong>, generated roll-wise.</p>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($exams as $examData)
            <div class="col-xl-6">
                <div class="card app-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 mb-1">{{ $examData['exam']->name }}</h2>
                                <div class="text-body-secondary small">{{ optional($examData['exam']->academicClass)->name ?? 'N/A' }}</div>
                            </div>
                            <span class="badge text-bg-light border">Set {{ $examData['assigned_set'] }}</span>
                        </div>
                        <div class="stack-list">
                            <div class="stack-item"><span>Exam Type</span><strong>{{ ucfirst($examData['exam']->exam_type) }}</strong></div>
                            <div class="stack-item"><span>Date</span><strong>{{ optional($examData['exam']->start_date)->format('d M Y') }} - {{ optional($examData['exam']->end_date)->format('d M Y') }}</strong></div>
                            <div class="stack-item"><span>Status</span><strong>{{ ucfirst($examData['exam']->status) }}</strong></div>
                            @if ($examData['attempt'])
                                <div class="stack-item"><span>Attempt</span><strong>{{ ucfirst($examData['attempt']->status) }}</strong></div>
                            @endif
                        </div>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            @if (($examData['attempt']?->status ?? null) === 'submitted')
                                <a class="btn btn-success btn-sm" href="{{ route('student.exams.result', [$examData['exam']->id, $examData['attempt']->id]) }}">View Result</a>
                            @elseif (($examData['attempt']?->status ?? null) === 'locked')
                                <span class="btn btn-outline-danger btn-sm disabled">Attempt Locked</span>
                            @else
                                <a class="btn btn-primary btn-sm" href="{{ route('student.exams.start', $examData['exam']->id) }}">Start Online Exam</a>
                            @endif
                            @if ($examData['paper'])
                                <a class="btn btn-primary btn-sm" href="{{ route('student.exam-papers.download', $examData['paper']->id) }}">Open My Paper</a>
                            @else
                                <span class="btn btn-outline-secondary btn-sm disabled">Paper not uploaded yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card app-card"><div class="card-body p-4 text-body-secondary">No exams are available for your class yet.</div></div>
            </div>
        @endforelse
    </div>
</div>
@endsection