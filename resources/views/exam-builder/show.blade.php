@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Admin Builder</span>
            <h1 class="h3 mb-1">{{ $exam->name }}</h1>
            <p class="text-body-secondary mb-0">{{ optional($exam->academicClass)->name ?? 'No Class' }} | Dedicated builder for question sets and settings.</p>
        </div>
        <a href="{{ route('exam-builder.index') }}" class="btn btn-outline-secondary">Back to Exams</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Exam Settings</h2>
                    <form method="POST" action="{{ route('exam-builder.settings.update', $exam->id) }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="1" max="300" value="{{ old('duration_minutes', $exam->duration_minutes ?: 30) }}">
                        </div>
                        <div>
                            <label class="form-label">Negative Mark Per Wrong</label>
                            <input type="number" name="negative_mark_per_wrong" class="form-control" min="0" max="100" step="0.01" value="{{ old('negative_mark_per_wrong', $exam->negative_mark_per_wrong ?? 0) }}">
                        </div>
                        <div>
                            <label class="form-label d-block">Active Sets</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $setCode)
                                    <label class="stack-item justify-content-start px-3 py-2">
                                        <input type="checkbox" name="question_sets[]" value="{{ $setCode }}" @checked(in_array($setCode, old('question_sets', $exam->question_sets ?? []), true))>
                                        <span>Set {{ $setCode }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Add Question</h2>
                    <form method="POST" action="{{ route('exam-builder.questions.store', $exam->id) }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Set Code</label>
                            <select name="set_code" class="form-select" required>
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $setCode)
                                    <option value="{{ $setCode }}">Set {{ $setCode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select">
                                <option value="">General</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Question Order</label>
                            <input type="number" name="question_order" class="form-control" min="1" placeholder="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Question</label>
                            <textarea name="question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option A</label>
                            <input type="text" name="option_a" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option B</label>
                            <input type="text" name="option_b" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Correct Option</label>
                            <select name="correct_option" class="form-select" required>
                                @foreach (['A', 'B', 'C', 'D'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marks</label>
                            <input type="number" name="marks" class="form-control" min="1" max="100" value="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Import --}}
    <div class="card app-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <span class="eyebrow">Bulk Upload</span>
                    <h2 class="h5 mb-0">Import Questions (Set-wise)</h2>
                </div>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#bulkImportPanel">
                    Toggle Import Panel
                </button>
            </div>

            <div class="collapse" id="bulkImportPanel" data-has-import-errors="{{ ($errors->has('bulk_rows') || $errors->has('csv_file') || $errors->has('force_set_code')) ? '1' : '0' }}">
                @if ($errors->has('bulk_rows') || $errors->has('csv_file') || $errors->has('force_set_code'))
                    <div class="alert alert-danger alert-dismissible mb-3">
                        {{ $errors->first('bulk_rows') ?: $errors->first('csv_file') ?: $errors->first('force_set_code') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('exam-builder.questions.import', $exam->id) }}" enctype="multipart/form-data" class="vstack gap-3">
                    @csrf

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Upload For Set</label>
                            <select name="force_set_code" class="form-select" id="importSetSelect">
                                <option value="">— Select Set —</option>
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $s)
                                    <option value="{{ $s }}" @selected(old('force_set_code') === $s)>Set {{ $s }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">All imported questions will be assigned this set.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Upload CSV / TXT File <span class="text-body-secondary fw-normal">(optional)</span></label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,.txt">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Or Paste Questions Below</label>
                        <textarea name="bulk_rows" class="form-control font-monospace" rows="8"
                            placeholder="subject | question text | option_a | option_b | option_c | option_d | correct_option | marks&#10;Math | 2+2 equals? | 3 | 4 | 5 | 6 | B | 1&#10;Science | Water formula? | H2O | CO2 | O2 | N | A | 1">{{ old('bulk_rows') }}</textarea>
                    </div>

                    <div class="p-3 rounded-3 border bg-body-tertiary small" id="importFormatHint">
                        <strong>Format (8 columns, pipe/tab/comma separated):</strong><br>
                        <code>subject | question | option_a | option_b | option_c | option_d | correct_option | marks</code><br>
                        <span class="text-body-secondary">correct_option must be A, B, C or D &nbsp;·&nbsp; subject name must match exam subjects &nbsp;·&nbsp; Leave subject blank for General</span>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upload me-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                            Import Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="vstack gap-4">
        @forelse ($questionsBySet as $setCode => $questions)
            <div class="card app-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <span class="eyebrow">Question Set</span>
                            <h2 class="h5 mb-0">Set {{ $setCode }}</h2>
                        </div>
                        <span class="badge text-bg-light border">{{ $questions->count() }} Question(s)</span>
                    </div>

                    <div class="vstack gap-3">
                        @foreach ($questions as $question)
                            <div class="border rounded-4 p-3 bg-body-tertiary">
                                <form method="POST" action="{{ route('exam-builder.questions.update', [$exam->id, $question->id]) }}" class="row g-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-3">
                                        <label class="form-label">Subject</label>
                                        <select name="subject_id" class="form-select">
                                            <option value="">General</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}" @selected((int) $question->subject_id === (int) $subject->id)>{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Set</label>
                                        <select name="set_code" class="form-select">
                                            @foreach (['A', 'B', 'C', 'D', 'E'] as $optionSet)
                                                <option value="{{ $optionSet }}" @selected($question->set_code === $optionSet)>Set {{ $optionSet }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Order</label>
                                        <input type="number" name="question_order" class="form-control" min="1" value="{{ $question->question_order }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Marks</label>
                                        <input type="number" name="marks" class="form-control" min="1" max="100" value="{{ $question->marks }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" @selected($question->status === 'active')>Active</option>
                                            <option value="inactive" @selected($question->status === 'inactive')>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Question</label>
                                        <textarea name="question_text" class="form-control" rows="2" required>{{ $question->question_text }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Option A</label>
                                        <input type="text" name="option_a" class="form-control" value="{{ $question->option_a }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Option B</label>
                                        <input type="text" name="option_b" class="form-control" value="{{ $question->option_b }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Option C</label>
                                        <input type="text" name="option_c" class="form-control" value="{{ $question->option_c }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Option D</label>
                                        <input type="text" name="option_d" class="form-control" value="{{ $question->option_d }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Correct Option</label>
                                        <select name="correct_option" class="form-select">
                                            @foreach (['A', 'B', 'C', 'D'] as $option)
                                                <option value="{{ $option }}" @selected($question->correct_option === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                        <div class="small text-body-secondary">{{ optional($question->subject)->name ?? 'General' }}</div>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('exam-builder.questions.destroy', [$exam->id, $question->id]) }}" class="mt-2" onsubmit="return confirm('Delete this question?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete Question</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="card app-card">
                <div class="card-body p-4 text-body-secondary">No questions added yet. Use the form above to start building this exam.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const setSelect = document.getElementById('importSetSelect');
    const hint = document.getElementById('importFormatHint');
    const panel = document.getElementById('bulkImportPanel');
    const hasImportErrors = panel && panel.dataset.hasImportErrors === '1';

    // Auto-open panel if there were validation errors
    if (hasImportErrors && panel) {
        const bs = bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
        bs.show();
    }

    function updateHint() {
        if (!hint || !setSelect) return;
        const set = setSelect.value;
        if (set) {
            hint.innerHTML = '<strong>Format for Set ' + set + ' (8 columns, pipe/tab/comma separated):</strong><br>'
                + '<code>subject | question | option_a | option_b | option_c | option_d | correct_option | marks</code><br>'
                + '<span class="text-body-secondary">correct_option must be A, B, C or D &nbsp;·&nbsp; set_code column NOT needed (Set ' + set + ' will be applied to all rows) &nbsp;·&nbsp; Leave subject blank for General</span>';
        } else {
            hint.innerHTML = '<strong>Format — mixed sets (9 columns):</strong><br>'
                + '<code>set_code | subject | question | option_a | option_b | option_c | option_d | correct_option | marks</code><br>'
                + '<span class="text-body-secondary">set_code must be A–E &nbsp;·&nbsp; correct_option must be A–D</span>';
        }
    }

    if (setSelect) {
        setSelect.addEventListener('change', updateHint);
        updateHint();
    }
})();
</script>
@endpush