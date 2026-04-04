@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <span class="eyebrow">Online Exam</span>
            <h1 class="h3 mb-1">{{ $exam->name }}</h1>
            <p class="text-body-secondary mb-0">Assigned Set <strong>{{ $assignedSet }}</strong> | Duration {{ $durationMinutes }} minutes | Negative Marking {{ number_format((float) ($exam->negative_mark_per_wrong ?? 0), 2) }}</p>
        </div>
        <div class="d-flex flex-column gap-2 align-items-lg-end">
            <div class="metric-card text-center">
                <div class="metric-label">Time Left</div>
                <div class="metric-value" id="examTimer" data-ends-at="{{ $endsAt->timestamp }}">--:--</div>
            </div>
            <div class="small text-body-secondary" id="autosaveStatus">Answers are synced automatically.</div>
        </div>
    </div>

    <form method="POST" action="{{ route('student.exams.submit', $exam->id) }}" id="studentExamForm" data-autosave-url="{{ route('student.exams.autosave', $exam->id) }}" data-monitor-url="{{ route('student.exams.monitor', $exam->id) }}">
        @csrf
        <div class="row g-4">
            @php($currentSubject = null)
            @foreach ($questions as $index => $question)
                @php($subjectName = optional($question->subject)->name ?? 'General')
                @if ($currentSubject !== $subjectName)
                    @php($currentSubject = $subjectName)
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                            <div>
                                <span class="eyebrow">Section</span>
                                <h2 class="h5 mb-0">{{ $subjectName }}</h2>
                            </div>
                            <span class="badge text-bg-light border">Subject Wise Questions</span>
                        </div>
                    </div>
                @endif
                <div class="col-12">
                    <div class="card app-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <h3 class="h5 mb-0">Q{{ $index + 1 }}. {{ $question->question_text }}</h3>
                                <span class="badge text-bg-light border">{{ $question->marks }} Mark(s)</span>
                            </div>
                            <div class="vstack gap-2">
                                @foreach (['A', 'B', 'C', 'D'] as $option)
                                    @php($field = 'option_'.strtolower($option))
                                    <label class="stack-item justify-content-start">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" @checked(($savedAnswers[$question->id] ?? null) === $option)>
                                        <span><strong>{{ $option }}.</strong> {{ $question->{$field} }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg">Submit Exam</button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timer = document.getElementById('examTimer');
            const form = document.getElementById('studentExamForm');
            const autosaveStatus = document.getElementById('autosaveStatus');
            if (!timer || !form) return;

            const endsAt = Number(timer.dataset.endsAt || 0) * 1000;
            const autosaveUrl = form.dataset.autosaveUrl;
            const monitorUrl = form.dataset.monitorUrl;
            let autosaveHandle = null;
            let saveInFlight = false;
            let tabWarningLocked = false;

            const collectAnswers = () => {
                const answers = {};
                form.querySelectorAll('input[type="radio"]:checked').forEach((input) => {
                    const match = input.name.match(/answers\[(\d+)\]/);
                    if (match) {
                        answers[match[1]] = input.value;
                    }
                });

                return answers;
            };

            const setAutosaveText = (message, isError = false) => {
                if (!autosaveStatus) return;
                autosaveStatus.textContent = message;
                autosaveStatus.classList.toggle('text-danger', isError);
                autosaveStatus.classList.toggle('text-body-secondary', !isError);
            };

            const redirectLocked = () => {
                window.alert('Your attempt has been locked due to repeated tab switching.');
                window.location.href = "{{ route('student.exams') }}";
            };

            const saveAnswers = async () => {
                if (!autosaveUrl || saveInFlight) return;

                saveInFlight = true;
                setAutosaveText('Saving answers...');

                try {
                    const response = await fetch(autosaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ answers: collectAnswers() }),
                        keepalive: true,
                    });

                    if (response.status === 423) {
                        throw new Error('locked');
                    }

                    if (!response.ok) {
                        throw new Error('Autosave failed');
                    }

                    const data = await response.json();
                    if (data.locked) {
                        redirectLocked();
                        return;
                    }
                    setAutosaveText(`Saved at ${data.saved_at}`);
                } catch (error) {
                    if (error?.message === 'locked') {
                        redirectLocked();
                        return;
                    }
                    setAutosaveText('Autosave failed. Keep answering, retry will continue.', true);
                } finally {
                    saveInFlight = false;
                }
            };

            const reportTabSwitch = async () => {
                if (!monitorUrl || tabWarningLocked) return;

                try {
                    const response = await fetch(monitorUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ event: 'tab_switch' }),
                        keepalive: true,
                    });

                    const data = await response.json();
                    if (response.status === 423 || data.locked) {
                        tabWarningLocked = true;
                        redirectLocked();
                        return;
                    }

                    setAutosaveText(`Warning: tab switch detected. ${data.remaining} warning(s) left.`, true);
                } catch (error) {
                    setAutosaveText('Unable to verify tab switch warning.', true);
                }
            };

            const queueAutosave = () => {
                window.clearTimeout(autosaveHandle);
                autosaveHandle = window.setTimeout(saveAnswers, 500);
            };

            const tick = () => {
                const remaining = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
                const mins = String(Math.floor(remaining / 60)).padStart(2, '0');
                const secs = String(remaining % 60).padStart(2, '0');
                timer.textContent = `${mins}:${secs}`;
                if (remaining <= 0) {
                    form.submit();
                }
            };

            form.addEventListener('change', (event) => {
                if (event.target.matches('input[type="radio"]')) {
                    queueAutosave();
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    reportTabSwitch();
                }
            });

            window.addEventListener('beforeunload', () => {
                if (!autosaveUrl) return;

                fetch(autosaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ answers: collectAnswers() }),
                    keepalive: true,
                });
            });

            tick();
            setInterval(tick, 1000);
            setInterval(saveAnswers, 15000);
        });
    </script>
</div>
@endsection