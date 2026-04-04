@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Admin Builder</span>
            <h1 class="h3 mb-1">Exam Builder</h1>
            <p class="text-body-secondary mb-0">Manage exam settings, sets, and questions from one dedicated workspace.</p>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($exams as $exam)
            <div class="col-xl-4 col-md-6">
                <div class="card app-card h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <span class="eyebrow mb-2">{{ optional($exam->academicClass)->name ?? 'No Class' }}</span>
                        <h2 class="h5 mb-2">{{ $exam->name }}</h2>
                        <div class="stack-list mb-3">
                            <div class="stack-item"><span>Type</span><strong>{{ ucfirst($exam->exam_type) }}</strong></div>
                            <div class="stack-item"><span>Sets</span><strong>{{ implode(', ', $exam->question_sets ?? []) ?: 'A' }}</strong></div>
                            <div class="stack-item"><span>Duration</span><strong>{{ $exam->duration_minutes ?: 30 }} mins</strong></div>
                            <div class="stack-item"><span>Negative Marking</span><strong>{{ number_format((float) ($exam->negative_mark_per_wrong ?? 0), 2) }}</strong></div>
                        </div>
                        <a href="{{ route('exam-builder.show', $exam->id) }}" class="btn btn-primary mt-auto">Open Builder</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card app-card">
                    <div class="card-body p-4 text-body-secondary">No exams available yet. Create an exam first, then open the builder.</div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection