<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExamPaperSetupController extends Controller
{
    private const SET_LABELS = ['A', 'B', 'C', 'D', 'E'];

    public function index(): View
    {
        $classes = AcademicClass::query()->orderBy('name')->get();
        $examTypes = Exam::query()->distinct()->orderBy('exam_type')->pluck('exam_type')->filter()->values();

        return view('exam-builder.paper-setup', compact('classes', 'examTypes'));
    }

    public function classSections(Request $request): JsonResponse
    {
        $classId = $request->input('class_id');
        if (! $classId) {
            return response()->json([]);
        }
        $sections = Section::query()
            ->where('academic_class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($sections);
    }

    public function classExams(Request $request): JsonResponse
    {
        $classId = $request->input('class_id');
        $examType = $request->input('exam_type');
        if (! $classId) {
            return response()->json([]);
        }
        $exams = Exam::query()
            ->where('academic_class_id', $classId)
            ->when($examType, fn ($q) => $q->where('exam_type', $examType))
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'exam_type', 'question_sets', 'start_date']);

        return response()->json($exams);
    }

    public function assignmentPreview(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'set_count' => ['required', 'integer', 'min:1', 'max:5'],
            'section_id' => ['nullable', 'integer'],
        ]);
        $exam = Exam::findOrFail($request->input('exam_id'));
        $sets = array_slice(self::SET_LABELS, 0, (int) $request->input('set_count'));
        $sectionId = $request->input('section_id');
        $students = Student::query()
            ->where('academic_class_id', $exam->academic_class_id)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->whereNotNull('roll_no')
            ->orderByRaw("CAST(REGEXP_REPLACE(roll_no, '[^0-9]', '') AS UNSIGNED) ASC, roll_no ASC")
            ->get(['id', 'roll_no', 'name', 'section_id']);
        $rows = $students->map(function (Student $s) use ($sets) {
            $digits = preg_replace('/\D+/', '', (string) $s->roll_no);
            $set = $digits !== ''
                ? $sets[((int) $digits - 1) % count($sets)]
                : $sets[abs(crc32((string) $s->roll_no)) % count($sets)];

            return ['id' => $s->id, 'roll_no' => $s->roll_no, 'name' => $s->name, 'set' => $set];
        });

        return response()->json($rows);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate(['set_count' => ['required', 'integer', 'min:1', 'max:5']]);
        $setCount = (int) $request->input('set_count');
        $sets = array_slice(self::SET_LABELS, 0, $setCount);
        $hasAny = false;
        foreach ($sets as $setCode) {
            if ($request->hasFile("file_{$setCode}")) {
                $hasAny = true;
                break;
            }
        }
        if (! $hasAny) {
            return back()->withErrors(['file' => 'Please upload at least one paper file.'])->withInput();
        }
        DB::transaction(function () use ($request, $exam, $sets) {
            $exam->update(['question_sets' => $sets]);
            foreach ($sets as $setCode) {
                if (! $request->hasFile("file_{$setCode}")) {
                    continue;
                }
                $file = $request->file("file_{$setCode}");
                $filename = 'exam-papers/'.$exam->id.'/set-'.$setCode.'-'.time().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('', $filename, 'public');
                $existing = ExamPaper::query()
                    ->where('exam_id', $exam->id)
                    ->where('set_code', $setCode)
                    ->first();
                if ($existing) {
                    if ($existing->file_path && Storage::disk('public')->exists($existing->file_path)) {
                        Storage::disk('public')->delete($existing->file_path);
                    }
                    $existing->update([
                        'file_path' => $path,
                        'title' => $exam->name.' - Set '.$setCode,
                        'status' => 'active',
                        'updated_by' => $request->user()->id,
                    ]);
                } else {
                    ExamPaper::query()->create([
                        'exam_id' => $exam->id,
                        'set_code' => $setCode,
                        'title' => $exam->name.' - Set '.$setCode,
                        'file_path' => $path,
                        'status' => 'active',
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]);
                }
            }
        });

        return redirect()->route('exam-papers.setup')
            ->with('status', count($sets).' set paper(s) uploaded for '.$exam->name.'.');
    }

    public function destroyPaper(ExamPaper $paper): RedirectResponse
    {
        if ($paper->file_path && Storage::disk('public')->exists($paper->file_path)) {
            Storage::disk('public')->delete($paper->file_path);
        }
        $paper->delete();

        return back()->with('status', 'Paper deleted.');
    }
}
