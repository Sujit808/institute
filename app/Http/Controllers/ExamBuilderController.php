<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamBuilderController extends Controller
{
    public function index(): View
    {
        $exams = Exam::query()->with('academicClass')->latest('start_date')->get();

        return view('exam-builder.index', compact('exams'));
    }

    public function show(Exam $exam): View
    {
        $exam->load(['academicClass', 'questions.subject', 'papers']);
        $subjects = Subject::query()
            ->where('academic_class_id', $exam->academic_class_id)
            ->orWhereHas('academicClasses', fn ($query) => $query->where('academic_classes.id', $exam->academic_class_id))
            ->orderBy('name')
            ->get();

        $questionsBySet = $exam->questions
            ->sortBy(['set_code', 'subject_id', 'question_order', 'id'])
            ->groupBy('set_code');

        $papersBySet = $exam->papers
            ->sortBy('set_code')
            ->keyBy('set_code');

        return view('exam-builder.show', compact('exam', 'subjects', 'questionsBySet', 'papersBySet'));
    }

    public function updateExam(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'negative_mark_per_wrong' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'question_sets' => ['nullable', 'array'],
            'question_sets.*' => ['in:A,B,C,D,E'],
        ]);

        $exam->update([
            'duration_minutes' => $validated['duration_minutes'] ?? $exam->duration_minutes,
            'negative_mark_per_wrong' => $validated['negative_mark_per_wrong'] ?? 0,
            'question_sets' => $validated['question_sets'] ?? $exam->question_sets,
        ]);

        return back()->with('status', 'Exam settings updated.');
    }

    public function storeQuestion(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $this->validateQuestion($request);
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;
        $validated['exam_id'] = $exam->id;

        ExamQuestion::query()->create($validated);

        return back()->with('status', 'Question added to exam builder.');
    }

    public function importQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'bulk_rows' => ['nullable', 'string', 'required_without:csv_file'],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:4096', 'required_without:bulk_rows'],
            'force_set_code' => ['nullable', 'string', 'in:A,B,C,D,E'],
        ]);

        $forceSet = isset($validated['force_set_code']) && $validated['force_set_code'] !== ''
            ? strtoupper($validated['force_set_code'])
            : null;

        $subjects = Subject::query()
            ->where('academic_class_id', $exam->academic_class_id)
            ->orWhereHas('academicClasses', fn ($query) => $query->where('academic_classes.id', $exam->academic_class_id))
            ->get()
            ->keyBy(fn (Subject $subject) => mb_strtolower(trim($subject->name)));

        $lines = preg_split('/\r\n|\n|\r/', trim((string) ($validated['bulk_rows'] ?? ''))) ?: [];
        $csvRows = $this->csvRows($request->file('csv_file'));
        $lines = array_values(array_filter(array_merge($lines, $csvRows), fn (string $line) => trim($line) !== ''));
        $payloads = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $delimiter = $this->detectDelimiter($line);
            $parts = array_map('trim', str_getcsv($line, $delimiter));

            // Skip header row
            if ($index === 0 && in_array(strtolower($parts[0] ?? ''), ['set', 'set_code', 'subject'], true)) {
                continue;
            }

            // When a set is forced, rows have 8 or 9 cols (no set_code column)
            // When no set is forced, rows have 9 or 10 cols (set_code first)
            $setForced = $forceSet !== null;
            $minCols = $setForced ? 8 : 9;
            $maxCols = $setForced ? 9 : 10;

            if (count($parts) < $minCols || count($parts) > $maxCols) {
                $hint = $setForced
                    ? 'subject | question | option_a | option_b | option_c | option_d | correct_option | marks | order(optional)'
                    : 'set | subject | question | option_a | option_b | option_c | option_d | correct_option | marks | order(optional)';
                throw ValidationException::withMessages([
                    'bulk_rows' => 'Line '.($index + 1).' column count mismatch. Expected: '.$hint.'.',
                ]);
            }

            if ($setForced) {
                [$subjectName, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $marks] = array_slice($parts, 0, 8);
                $questionOrder = $parts[8] ?? null;
                $normalizedSet = $forceSet;
            } else {
                [$setCode, $subjectName, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $marks] = array_slice($parts, 0, 9);
                $questionOrder = $parts[9] ?? null;
                $normalizedSet = strtoupper($setCode);
            }

            $normalizedCorrect = strtoupper($correctOption);

            if (! in_array($normalizedSet, ['A', 'B', 'C', 'D', 'E'], true)) {
                throw ValidationException::withMessages(['bulk_rows' => 'Invalid set code on line '.($index + 1).'.']);
            }

            if (! in_array($normalizedCorrect, ['A', 'B', 'C', 'D'], true)) {
                throw ValidationException::withMessages(['bulk_rows' => 'Invalid correct option on line '.($index + 1).'.']);
            }

            if (! is_numeric($marks)) {
                throw ValidationException::withMessages(['bulk_rows' => 'Marks must be numeric on line '.($index + 1).'.']);
            }

            $subject = null;
            if ($subjectName !== '') {
                $subject = $subjects->get(mb_strtolower($subjectName));

                if (! $subject) {
                    throw ValidationException::withMessages(['bulk_rows' => 'Unknown subject "'.$subjectName.'" on line '.($index + 1).'.']);
                }
            }

            $payloads[] = [
                'exam_id' => $exam->id,
                'subject_id' => $subject?->id,
                'set_code' => $normalizedSet,
                'question_text' => $questionText,
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'correct_option' => $normalizedCorrect,
                'marks' => (int) $marks,
                'question_order' => $questionOrder !== null && $questionOrder !== '' ? (int) $questionOrder : null,
                'status' => 'active',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($payloads === []) {
            throw ValidationException::withMessages(['bulk_rows' => 'No import rows were found.']);
        }

        DB::transaction(function () use ($payloads) {
            ExamQuestion::query()->insert($payloads);
        });

        return back()->with('status', count($payloads).' questions imported successfully.');
    }

    public function updateQuestion(Request $request, Exam $exam, ExamQuestion $question): RedirectResponse
    {
        abort_if($question->exam_id !== $exam->id, 404);

        $validated = $this->validateQuestion($request);
        $validated['updated_by'] = $request->user()->id;
        $question->update($validated);

        return back()->with('status', 'Question updated.');
    }

    public function destroyQuestion(Exam $exam, ExamQuestion $question): RedirectResponse
    {
        abort_if($question->exam_id !== $exam->id, 404);
        $question->delete();

        return back()->with('status', 'Question deleted.');
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'set_code' => ['required', 'in:A,B,C,D,E'],
            'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'in:A,B,C,D'],
            'marks' => ['required', 'integer', 'min:1', 'max:100'],
            'question_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    private function csvRows(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [];
        }

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $rows[] = implode(' | ', array_map(fn ($value) => trim((string) $value), $data));
        }

        fclose($handle);

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        if (str_contains($line, "\t")) {
            return "\t";
        }

        if (str_contains($line, '|')) {
            return '|';
        }

        return ',';
    }
}
