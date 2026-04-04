@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="hero-panel p-4 p-lg-5 mb-4 text-white">
        <span class="eyebrow text-white-50">Exam Result</span>
        <h1 class="h3 mb-2">{{ $exam->name }}</h1>
        <p class="mb-0 text-white-50">You scored {{ $attempt->score }} marks with {{ $attempt->correct_answers }} correct answers.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Set</div><div class="metric-value">{{ $attempt->set_code }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Questions</div><div class="metric-value">{{ $attempt->total_questions }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Score</div><div class="metric-value">{{ $attempt->score }}</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="metric-label">Accuracy</div><div class="metric-value">{{ $percentage }}%</div></div></div>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Answer Review</h2>
            <div class="stack-list">
                @foreach ($attempt->answers as $answer)
                    <div class="stack-item align-items-start flex-column">
                        <div class="fw-semibold">{{ $answer->question->question_text }}</div>
                        <div class="small text-body-secondary">Selected: {{ $answer->selected_option ?: 'Not Answered' }} | Correct: {{ $answer->question->correct_option }}</div>
                        <div class="small {{ $answer->is_correct ? 'text-success' : 'text-danger' }}">{{ $answer->is_correct ? 'Correct' : 'Incorrect' }} | Marks: {{ $answer->marks_awarded }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection