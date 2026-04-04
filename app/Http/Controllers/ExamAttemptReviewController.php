<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ExamAttemptReviewController extends Controller
{
    public function index(Request $request): View
    {
        $examId = $request->integer('exam_id') ?: null;

        $attempts = ExamAttempt::query()
            ->with(['exam', 'student'])
            ->where(function ($query) {
                $query->where('status', 'locked')
                    ->orWhere('tab_switch_count', '>', 0);
            })
            ->when($examId, fn ($query) => $query->where('exam_id', $examId))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = ExamAttempt::query()->where(function ($query) {
            $query->where('status', 'locked')
                ->orWhere('tab_switch_count', '>', 0);
        });

        if ($examId) {
            $summaryQuery->where('exam_id', $examId);
        }

        $summary = [
            'total_flagged' => (clone $summaryQuery)->count(),
            'locked' => (clone $summaryQuery)->where('status', 'locked')->count(),
            'total_violations' => (int) ((clone $summaryQuery)->sum('tab_switch_count')),
        ];

        $exams = Exam::query()->orderBy('name')->get(['id', 'name']);

        return view('exam-attempts-review.index', compact('attempts', 'summary', 'exams', 'examId'));
    }

    public function show(ExamAttempt $attempt): View
    {
        $attempt->load(['exam', 'student', 'answers.question.subject']);

        return view('exam-attempts-review.show', compact('attempt'));
    }
}
