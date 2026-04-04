@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Attempt Review</span>
            <h1 class="h3 mb-1">{{ $attempt->exam->name ?? 'Exam' }} - {{ $attempt->student->full_name ?? 'Student' }}</h1>
            <p class="text-body-secondary mb-0">Violation count: <strong>{{ $attempt->tab_switch_count }}</strong></p>
        </div>
        <a href="{{ route('exam-attempts.review.index', ['exam_id' => $attempt->exam_id]) }}" class="btn btn-outline-secondary">Back to Review</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Status</div><div class="metric-value text-capitalize">{{ $attempt->status }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Set</div><div class="metric-value">{{ $attempt->set_code }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Score</div><div class="metric-value">{{ $attempt->score }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Locked At</div><div class="metric-value small">{{ optional($attempt->locked_at)->format('d M Y h:i A') ?: '-' }}</div></div></div>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Answer Audit</h2>
            <div class="table-responsive">
                <table class="table align-middle app-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Question</th>
                            <th>Selected</th>
                            <th>Correct</th>
                            <th>Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attempt->answers as $index => $answer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ optional($answer->question->subject)->name ?? 'General' }}</td>
                                <td>{{ $answer->question->question_text ?? 'N/A' }}</td>
                                <td>{{ $answer->selected_option ?: '-' }}</td>
                                <td>{{ $answer->question->correct_option ?? '-' }}</td>
                                <td>{{ $answer->marks_awarded }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-body-secondary">No answer records found for this attempt.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
