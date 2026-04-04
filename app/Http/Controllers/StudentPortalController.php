<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\Fee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LicenseConfig;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\StudyMaterial;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPortalController extends Controller
{
    public function dashboard(): View
    {
        $student = $this->student();
        $examCount = Exam::query()
            ->where('academic_class_id', $student->academic_class_id)
            ->count();
        $materialCount = StudyMaterial::query()
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereNull('academic_class_id')
                    ->orWhere('academic_class_id', $student->academic_class_id);
            })
            ->count();
        $attendanceTotal = $student->attendances->count();
        $attendancePresentCount = $student->attendances->where('status', 'present')->count();
        $attendanceAbsentCount = $student->attendances->where('status', 'absent')->count();
        $attendanceRate = $attendanceTotal > 0
            ? round(($attendancePresentCount / $attendanceTotal) * 100, 2)
            : 0;
        $resultAverage = round((float) $student->results()->avg('marks_obtained'), 2);
        $resultCount = $student->results->count();
        $feePaid = round((float) $student->fees()->sum('paid_amount'), 2);
        $feeDue = max(0, round((float) $student->fees()->sum(DB::raw('amount - paid_amount')), 2));
        $feeTotal = round((float) $student->fees()->sum('amount'), 2);
        $feePaidRate = $feeTotal > 0 ? min(100, round(($feePaid / $feeTotal) * 100, 2)) : 0;
        $pendingFeeCount = $student->fees->filter(fn ($fee) => ((float) $fee->amount - (float) $fee->paid_amount) > 0)->count();
        $subjectCoverage = $student->results
            ->groupBy(fn ($result) => optional($result->subject)->name ?? 'General')
            ->count();
        $latestPayment = Payment::query()
            ->where('student_id', $student->id)
            ->latest('payment_date')
            ->latest('id')
            ->first();
        $latestResult = $student->results
            ->sortByDesc(fn ($result) => sprintf('%s-%010d', optional($result->exam)->end_date?->format('Y-m-d') ?? '', $result->id))
            ->first();
        $latestMaterial = StudyMaterial::query()
            ->with('subject')
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereNull('academic_class_id')
                    ->orWhere('academic_class_id', $student->academic_class_id);
            })
            ->latest('id')
            ->first();

        $attendanceLabels = collect(range(6, 0))
            ->map(fn (int $offset) => now()->subDays($offset)->format('d M'))
            ->values();
        $attendancePresent = collect(range(6, 0))
            ->map(function (int $offset) use ($student) {
                return (int) $student->attendances()
                    ->whereDate('attendance_date', now()->subDays($offset)->toDateString())
                    ->where('status', 'present')
                    ->exists();
            })
            ->values();
        $attendanceAbsent = $attendancePresent
            ->map(fn (int $value) => $value === 1 ? 0 : 1)
            ->values();

        $resultBySubject = $student->results
            ->groupBy(fn ($result) => optional($result->subject)->name ?? 'General')
            ->map(fn ($results, $label) => [
                'label' => $label,
                'value' => round((float) collect($results)->avg('marks_obtained'), 2),
            ])
            ->values();

        $chartData = [
            'attendance' => [
                'labels' => $attendanceLabels->all(),
                'present' => $attendancePresent->all(),
                'absent' => $attendanceAbsent->all(),
            ],
            'fees' => [
                'labels' => ['Paid', 'Due'],
                'values' => [$feePaid, $feeDue],
            ],
            'results' => [
                'labels' => $resultBySubject->pluck('label')->all(),
                'values' => $resultBySubject->pluck('value')->all(),
            ],
        ];

        $moduleCards = [
            [
                'title' => 'Attendance',
                'value' => $attendanceTotal > 0 ? $attendanceRate.'%' : 'N/A',
                'meta' => $attendancePresentCount.' present / '.$attendanceAbsentCount.' absent',
                'detail' => 'Last 7-day trend synced',
                'description' => 'Track daily records and monthly attendance trend.',
                'route' => route('student.attendance'),
                'cta' => 'Open attendance',
                'icon' => 'bi-calendar-check',
                'tone' => 'success',
            ],
            [
                'title' => 'Fee Status',
                'value' => 'Rs '.number_format($feeDue, 2),
                'meta' => $feePaidRate.'% paid'.($pendingFeeCount > 0 ? ' | '.$pendingFeeCount.' pending' : ' | Clear'),
                'detail' => $latestPayment?->payment_date ? 'Last payment '.$latestPayment->payment_date->format('d M Y') : 'No payment recorded yet',
                'description' => 'Download official receipts and review fee balances.',
                'route' => route('student.fees'),
                'cta' => 'View fees',
                'icon' => 'bi-receipt-cutoff',
                'tone' => 'warning',
            ],
            [
                'title' => 'Exam Results',
                'value' => $resultCount > 0 ? $this->getGradeFromMarks($resultAverage) : 'N/A',
                'meta' => $resultCount.' marksheets | Avg '.$resultAverage,
                'detail' => $latestResult ? ((optional($latestResult->subject)->name ?? 'Latest subject').' '.$latestResult->marks_obtained.'/100') : 'No evaluated result yet',
                'description' => 'See subject performance and interactive charts.',
                'route' => route('student.results'),
                'cta' => 'View results',
                'icon' => 'bi-bar-chart-line',
                'tone' => 'primary',
            ],
            [
                'title' => 'Study Material',
                'value' => (string) $materialCount,
                'meta' => $subjectCoverage.' subject insights | class-ready files',
                'detail' => $latestMaterial ? 'Latest: '.$latestMaterial->title : 'No material published yet',
                'description' => 'Browse books, notes, and downloadable class materials.',
                'route' => route('student.books'),
                'cta' => 'Open library',
                'icon' => 'bi-journal-arrow-down',
                'tone' => 'info',
            ],
        ];

        $notifications = SchoolNotification::query()
            ->where('status', 'published')
            ->whereDate('publish_date', '<=', now()->toDateString())
            ->whereIn('audience', ['all', 'students'])
            ->where(function ($query) use ($student) {
                $query->whereNull('academic_class_id')
                    ->orWhere('academic_class_id', $student->academic_class_id);
            })
            ->where(function ($query) use ($student) {
                $query->whereNull('section_id')
                    ->orWhere('section_id', $student->section_id);
            })
            ->latest('publish_date')
            ->limit(6)
            ->get();

        return view('student.dashboard', [
            'student' => $student,
            'stats' => [
                ['label' => 'Roll Number', 'value' => $student->roll_no],
                ['label' => 'Assigned Exam Set', 'value' => $this->assignedSet($student->roll_no)],
                ['label' => 'Available Exams', 'value' => $examCount],
                ['label' => 'Study Materials', 'value' => $materialCount],
                ['label' => 'Fee Due', 'value' => number_format($feeDue, 2)],
                ['label' => 'Average Score', 'value' => $resultAverage > 0 ? $resultAverage : 'N/A'],
            ],
            'moduleCards' => $moduleCards,
            'chartData' => $chartData,
            'notifications' => $notifications,
        ]);
    }

    public function profile(): View
    {
        return view('student.profile', ['student' => $this->student()]);
    }

    public function exams(): View
    {
        $student = $this->student();

        $exams = Exam::query()
            ->with(['academicClass'])
            ->where('academic_class_id', $student->academic_class_id)
            ->latest('start_date')
            ->get()
            ->map(function (Exam $exam) use ($student) {
                $examSets = $exam->question_sets ?: ['A', 'B', 'C', 'D', 'E'];
                $assignedSet = $this->assignedSet($student->roll_no, $examSets);

                $paper = ExamPaper::query()
                    ->where('exam_id', $exam->id)
                    ->where('set_code', $assignedSet)
                    ->where('status', 'active')
                    ->first();

                return [
                    'exam' => $exam,
                    'assigned_set' => $assignedSet,
                    'paper' => $paper,
                    'attempt' => ExamAttempt::query()
                        ->where('student_id', $student->id)
                        ->where('exam_id', $exam->id)
                        ->latest('id')
                        ->first(),
                ];
            });

        $assignedSet = $this->assignedSet($student->roll_no);

        return view('student.exams', compact('student', 'assignedSet', 'exams'));
    }

    public function startExam(Exam $exam): View|RedirectResponse
    {
        $student = $this->student();
        $this->guardStudentExam($student, $exam);
        $assignedSet = $this->assignedSet($student->roll_no, $exam->question_sets ?: ['A', 'B', 'C', 'D', 'E']);

        $completedAttempt = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'submitted')
            ->latest('id')
            ->first();

        if ($completedAttempt) {
            return redirect()->route('student.exams.result', [$exam->id, $completedAttempt->id]);
        }

        $activeAttempt = $this->activeAttempt($student, $exam);
        if ($activeAttempt?->status === 'locked') {
            return redirect()->route('student.exams')->with('status', 'Your attempt was locked due to repeated tab switching. Contact the admin.');
        }

        $questions = $this->questionsForExam($exam, $assignedSet);
        if ($questions->isEmpty()) {
            return redirect()->route('student.exams')->with('status', 'Questions are not available for your set yet.');
        }

        $attempt = $activeAttempt ?? ExamAttempt::query()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'set_code' => $assignedSet,
            'started_at' => now(),
            'status' => 'started',
        ]);

        if ($attempt->set_code !== $assignedSet || ! $attempt->started_at) {
            $attempt->forceFill([
                'set_code' => $assignedSet,
                'started_at' => $attempt->started_at ?? now(),
            ])->save();
        }

        $savedAnswers = $attempt->answers()->pluck('selected_option', 'question_id')->all();
        $durationMinutes = $exam->duration_minutes ?: 30;
        $endsAt = $attempt->started_at->copy()->addMinutes($durationMinutes);

        if (now()->greaterThanOrEqualTo($endsAt)) {
            return redirect()->route('student.exams')->with('status', 'Exam time has already expired.');
        }

        return view('student.exam-attempt', compact(
            'student',
            'exam',
            'questions',
            'attempt',
            'assignedSet',
            'durationMinutes',
            'endsAt',
            'savedAnswers'
        ));
    }

    public function monitorExam(Request $request, Exam $exam): JsonResponse
    {
        $student = $this->student();
        $this->guardStudentExam($student, $exam);

        $validated = $request->validate([
            'event' => ['required', 'in:tab_switch'],
        ]);

        $attempt = $this->activeAttempt($student, $exam);
        if (! $attempt) {
            return response()->json(['locked' => false, 'message' => 'No active attempt found.'], 404);
        }

        if ($attempt->status === 'locked') {
            return response()->json([
                'locked' => true,
                'count' => $attempt->tab_switch_count,
                'remaining' => 0,
                'message' => 'Attempt already locked.',
            ], 423);
        }

        $maxWarnings = 3;

        if ($validated['event'] === 'tab_switch') {
            $attempt->increment('tab_switch_count');
            $attempt->refresh();
        }

        if ($attempt->tab_switch_count >= $maxWarnings) {
            $attempt->update([
                'locked_at' => now(),
                'submitted_at' => now(),
                'duration_seconds' => $attempt->started_at ? $attempt->started_at->diffInSeconds(now()) : 0,
                'status' => 'locked',
            ]);

            return response()->json([
                'locked' => true,
                'count' => $attempt->tab_switch_count,
                'remaining' => 0,
                'message' => 'Attempt locked due to repeated tab switching.',
            ], 423);
        }

        return response()->json([
            'locked' => false,
            'count' => $attempt->tab_switch_count,
            'remaining' => max(0, $maxWarnings - $attempt->tab_switch_count),
            'message' => 'Tab switch warning recorded.',
        ]);
    }

    public function autosaveExam(Request $request, Exam $exam): JsonResponse
    {
        $student = $this->student();
        $this->guardStudentExam($student, $exam);
        $assignedSet = $this->assignedSet($student->roll_no, $exam->question_sets ?: ['A', 'B', 'C', 'D', 'E']);
        $questions = $this->questionsForExam($exam, $assignedSet)->keyBy('id');

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
        ]);

        $attempt = $this->activeAttempt($student, $exam);
        if ($attempt?->status === 'locked') {
            return response()->json([
                'saved' => false,
                'locked' => true,
                'message' => 'Attempt has been locked.',
            ], 423);
        }

        if (! $attempt) {
            $attempt = ExamAttempt::query()->create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'set_code' => $assignedSet,
                'started_at' => now(),
                'status' => 'started',
            ]);
        }

        foreach (($validated['answers'] ?? []) as $questionId => $selectedOption) {
            if (! $questions->has((int) $questionId)) {
                continue;
            }

            ExamAttemptAnswer::query()->updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'question_id' => (int) $questionId,
                ],
                [
                    'selected_option' => $selectedOption,
                    'is_correct' => false,
                    'marks_awarded' => 0,
                ]
            );
        }

        return response()->json([
            'saved' => true,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    public function submitExam(Request $request, Exam $exam): RedirectResponse
    {
        $student = $this->student();
        $this->guardStudentExam($student, $exam);
        $assignedSet = $this->assignedSet($student->roll_no, $exam->question_sets ?: ['A', 'B', 'C', 'D', 'E']);
        $questions = $this->questionsForExam($exam, $assignedSet);

        $attempt = $this->activeAttempt($student, $exam);
        abort_if(! $attempt, 404);

        if ($attempt->status === 'locked') {
            return redirect()->route('student.exams')->with('status', 'Your attempt was locked and cannot be submitted.');
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
        ]);

        DB::transaction(function () use ($validated, $questions, $attempt, $exam) {
            $answers = $validated['answers'] ?? [];
            $score = 0;
            $correct = 0;
            $negative = (float) ($exam->negative_mark_per_wrong ?? 0);

            foreach ($questions as $question) {
                $selected = $answers[$question->id] ?? null;
                $isCorrect = $selected !== null
                    && strtoupper((string) $selected) === strtoupper((string) $question->correct_option);
                $isWrong = $selected !== null && ! $isCorrect;
                $marksAwarded = $isCorrect ? (float) $question->marks : 0;

                if ($isWrong && $negative > 0) {
                    $marksAwarded = 0 - $negative;
                }

                $score += $marksAwarded;
                $correct += $isCorrect ? 1 : 0;

                ExamAttemptAnswer::query()->updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_option' => $selected,
                        'is_correct' => $isCorrect,
                        'marks_awarded' => round($marksAwarded, 2),
                    ]
                );
            }

            $attempt->update([
                'submitted_at' => now(),
                'duration_seconds' => $attempt->started_at ? $attempt->started_at->diffInSeconds(now()) : 0,
                'total_questions' => $questions->count(),
                'correct_answers' => $correct,
                'score' => round(max(0, $score), 2),
                'status' => 'submitted',
            ]);
        });

        return redirect()->route('student.exams.result', [$exam->id, $attempt->id])
            ->with('status', 'Exam submitted successfully.');
    }

    public function result(Exam $exam, ExamAttempt $attempt): View
    {
        $student = $this->student();
        abort_if($attempt->student_id !== $student->id || $attempt->exam_id !== $exam->id, 404);

        $attempt->load(['answers.question.subject']);

        return view('student.exam-result', [
            'student' => $student,
            'exam' => $exam,
            'attempt' => $attempt,
            'percentage' => $attempt->total_questions > 0
                ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 2)
                : 0,
        ]);
    }

    public function books(): View
    {
        $student = $this->student();
        $materials = StudyMaterial::query()
            ->with(['academicClass', 'subject'])
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereNull('academic_class_id')
                    ->orWhere('academic_class_id', $student->academic_class_id);
            })
            ->latest('id')
            ->get();

        return view('student.books', compact('student', 'materials'));
    }

    public function fees(): View
    {
        $student = $this->student();
        $fees = $student->fees()
            ->with(['payments' => fn ($query) => $query->latest('payment_date')->latest('id')])
            ->latest('id')
            ->get();
        $payments = Payment::query()
            ->with('fee')
            ->where('student_id', $student->id)
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $totalFee = round((float) $fees->sum('amount'), 2);
        $totalPaid = round((float) $fees->sum(function (Fee $fee): float {
            $fromPayments = (float) $fee->payments->sum('amount');

            return $fromPayments > 0 ? $fromPayments : (float) $fee->paid_amount;
        }), 2);
        $totalDue = max(0, round($totalFee - $totalPaid, 2));
        $paymentPercentage = $totalFee > 0 ? min(100, round(($totalPaid / $totalFee) * 100)) : 0;

        return view('student.fees', [
            'student' => $student,
            'fees' => $fees,
            'payments' => $payments,
            'summary' => [
                'total' => $totalFee,
                'paid' => $totalPaid,
                'due' => $totalDue,
                'percentage' => $paymentPercentage,
            ],
        ]);
    }

    public function downloadFeeReceipt(Fee $fee)
    {
        $student = $this->student();
        abort_if($fee->student_id !== $student->id, 404);

        $fee->loadMissing(['student.academicClass', 'student.section', 'payments']);

        $latestPayment = $fee->payments
            ->sortByDesc(fn ($payment) => sprintf('%s-%010d', (string) $payment->payment_date, $payment->id))
            ->first();

        $receiptNo = (string) ($latestPayment?->receipt_no
            ?: $fee->receipt_no
            ?: ($latestPayment
                ? 'RCPT-'.str_pad((string) $latestPayment->id, 6, '0', STR_PAD_LEFT)
                : 'RCPT-'.str_pad((string) $fee->id, 6, '0', STR_PAD_LEFT)));

        $paymentDate = $latestPayment?->payment_date ?: $fee->updated_at;

        $branding = $this->receiptBranding();

        $pdf = Pdf::loadView('fees.receipt-pdf', [
            'fee' => $fee,
            'latestPayment' => $latestPayment,
            'receiptNo' => $receiptNo,
            'paymentDate' => $paymentDate,
            'generatedAt' => now(),
            'schoolName' => $branding['schoolName'],
            'logoUrl' => $branding['logoUrl'],
            'schoolAddress' => $branding['schoolAddress'],
            'schoolPhone' => $branding['schoolPhone'],
            'schoolEmail' => $branding['schoolEmail'],
            'branchName' => $branding['branchName'],
            'branchAddress' => $branding['branchAddress'],
        ]);

        $safeReceiptNo = Str::of($receiptNo)->replaceMatches('/[^A-Za-z0-9_-]/', '')->value() ?: 'receipt';

        return $pdf->download('payment-receipt-'.$safeReceiptNo.'.pdf');
    }

    public function downloadPaymentReceipt(Payment $payment)
    {
        $student = $this->student();
        abort_if($payment->student_id !== $student->id, 404);

        $payment->loadMissing(['fee.student.academicClass', 'fee.student.section']);
        $fee = $payment->fee;
        abort_if(! $fee || $fee->student_id !== $student->id, 404);

        $receiptNo = (string) ($payment->receipt_no ?: $this->generateReceiptNoFromPayment($payment));
        $branding = $this->receiptBranding();

        $pdf = Pdf::loadView('fees.receipt-pdf', [
            'fee' => $fee,
            'latestPayment' => $payment,
            'receiptNo' => $receiptNo,
            'paymentDate' => $payment->payment_date ?: $payment->updated_at,
            'generatedAt' => now(),
            'schoolName' => $branding['schoolName'],
            'logoUrl' => $branding['logoUrl'],
            'schoolAddress' => $branding['schoolAddress'],
            'schoolPhone' => $branding['schoolPhone'],
            'schoolEmail' => $branding['schoolEmail'],
            'branchName' => $branding['branchName'],
            'branchAddress' => $branding['branchAddress'],
        ]);

        $safeReceiptNo = Str::of($receiptNo)->replaceMatches('/[^A-Za-z0-9_-]/', '')->value() ?: 'receipt';

        return $pdf->download('payment-receipt-'.$safeReceiptNo.'.pdf');
    }

    public function attendance(): View
    {
        $student = $this->student();
        $attendances = $student->attendances()
            ->orderByDesc('attendance_date')
            ->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        // Group by month for chart
        $attendanceByMonth = $attendances
            ->groupBy(fn ($att) => $att->attendance_date->format('M Y'))
            ->map(fn ($group) => [
                'month' => $group[0]->attendance_date->format('M Y'),
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'percentage' => $group->count() > 0
                    ? round(($group->where('status', 'present')->count() / $group->count()) * 100, 2)
                    : 0,
            ])
            ->reverse()
            ->values();

        return view('student.attendance', [
            'student' => $student,
            'attendances' => $attendances,
            'summary' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $percentage,
            ],
            'monthlyData' => $attendanceByMonth,
        ]);
    }

    public function myCalendar(Request $request): View
    {
        $student = $this->student();
        $user = Auth::user();

        $selectedYear = max(2020, min(2100, (int) $request->integer('year', now()->year)));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', now()->month)));

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $today = now()->startOfDay();

        $joinDate = $student->admission_date?->copy()->startOfDay()
            ?? $user?->created_at?->copy()->startOfDay()
            ?? (clone $monthStart);

        $attendances = $student->attendances()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

        $holidays = Holiday::query()
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get();

        $leaveRequests = LeaveRequest::query()
            ->where('requester_type', 'student')
            ->where('student_id', $student->id)
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get();

        $approvedLeaves = $leaveRequests->where('status', 'approved')->values();
        $pendingLeaves = $leaveRequests->where('status', 'pending')->values();

        $approvedLeavesBeforeMonth = LeaveRequest::query()
            ->where('requester_type', 'student')
            ->where('student_id', $student->id)
            ->where('status', 'approved')
            ->whereDate('end_date', '<', $monthStart->toDateString())
            ->get();

        $holidayByDate = [];
        foreach ($holidays as $holiday) {
            $start = Carbon::parse($holiday->start_date)->startOfDay();
            $end = Carbon::parse($holiday->end_date)->startOfDay();
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $dateKey = $cursor->toDateString();
                $holidayByDate[$dateKey] = [
                    'title' => $holiday->title,
                    'type' => $holiday->holiday_type ?: 'holiday',
                    'description' => $holiday->description,
                ];
            }
        }

        $leaveByDate = [];
        foreach ($approvedLeaves as $leave) {
            $start = Carbon::parse($leave->start_date)->startOfDay();
            $end = Carbon::parse($leave->end_date)->startOfDay();
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $dateKey = $cursor->toDateString();
                $leaveByDate[$dateKey] = [
                    'type' => $leave->leave_type,
                    'reason' => $leave->reason,
                ];
            }
        }

        $calendarRows = [];
        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $endCursor = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $summary = [
            'present' => 0,
            'absent' => 0,
            'holiday' => 0,
            'leave' => 0,
        ];

        while ($cursor->lte($endCursor)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $cursor->toDateString();
                $isCurrentMonth = $cursor->month === $monthStart->month;
                $isBeforeJoin = $cursor->lt($joinDate);

                $cellType = 'none';
                $cellLabel = null;
                $cellMeta = null;
                $inTime = null;
                $outTime = null;

                if ($isCurrentMonth && ! $isBeforeJoin) {
                    if (isset($holidayByDate[$dateKey])) {
                        $cellType = ($holidayByDate[$dateKey]['type'] ?? 'holiday') === 'weekoff' ? 'weekoff' : 'holiday';
                        $cellLabel = $holidayByDate[$dateKey]['title'];
                        $cellMeta = $cellType === 'weekoff'
                            ? 'WO'
                            : strtoupper(substr((string) $holidayByDate[$dateKey]['type'], 0, 3));
                        $summary['holiday']++;
                    } elseif (isset($leaveByDate[$dateKey])) {
                        $cellType = 'leave';
                        $cellLabel = ucfirst((string) $leaveByDate[$dateKey]['type']).' Leave';
                        $cellMeta = strtoupper(substr((string) $leaveByDate[$dateKey]['type'], 0, 2));
                        $summary['leave']++;
                    } elseif ($attendances->has($dateKey)) {
                        $attendance = $attendances->get($dateKey);
                        $attendanceStatus = (string) $attendance->status;
                        if ($attendanceStatus === 'leave') {
                            $cellType = 'leave';
                            $cellLabel = 'Leave';
                            $summary['leave']++;
                        } else {
                            $isPresent = in_array($attendanceStatus, ['present', 'late'], true);
                            $cellType = $isPresent ? 'present' : 'absent';
                            $cellLabel = ucfirst($attendanceStatus);
                            if ($isPresent) {
                                $summary['present']++;
                            } else {
                                $summary['absent']++;
                            }
                        }

                        $cellMeta = strtoupper(substr((string) $attendance->status, 0, 1));

                        $timings = $this->extractAttendanceTimings($attendance);
                        $inTime = $timings['in'];
                        $outTime = $timings['out'];
                    } elseif ($cursor->lte($today)) {
                        $cellType = 'absent';
                        $cellLabel = 'Absent';
                        $cellMeta = 'A';
                        $summary['absent']++;
                    }
                }

                $week[] = [
                    'date' => $cursor->copy(),
                    'isCurrentMonth' => $isCurrentMonth,
                    'isBeforeJoin' => $isBeforeJoin,
                    'type' => $cellType,
                    'label' => $cellLabel,
                    'meta' => $cellMeta,
                    'inTime' => $inTime,
                    'outTime' => $outTime,
                ];

                $cursor->addDay();
            }
            $calendarRows[] = $week;
        }

        $monthOptions = collect(range(1, 12))->map(fn (int $m) => [
            'value' => $m,
            'label' => Carbon::create(null, $m, 1)->format('F'),
        ]);

        $leaveTypes = collect(['casual', 'medical', 'earned', 'holiday'])
            ->merge($leaveRequests->pluck('leave_type')->map(fn ($type) => strtolower((string) $type)))
            ->unique()
            ->values();

        $leaveSummaryRows = $leaveTypes->map(function (string $type) use ($approvedLeavesBeforeMonth, $approvedLeaves, $pendingLeaves, $monthStart, $monthEnd) {
            $opening = (float) $approvedLeavesBeforeMonth
                ->where('leave_type', $type)
                ->sum(fn ($leave) => $this->leaveDaysWithinRange($leave, Carbon::parse($leave->start_date), Carbon::parse($leave->end_date)));

            $consume = (float) $approvedLeaves
                ->where('leave_type', $type)
                ->sum(fn ($leave) => $this->leaveDaysWithinRange($leave, $monthStart, $monthEnd));

            $pending = (float) $pendingLeaves
                ->where('leave_type', $type)
                ->sum(fn ($leave) => $this->leaveDaysWithinRange($leave, $monthStart, $monthEnd));

            $credit = 0.0;
            $lateDed = 0.0;
            $encash = 0.0;
            $closing = max(0, $opening + $credit - $consume - $lateDed - $encash);

            return [
                'leave' => strtoupper(substr($type, 0, 3)),
                'opening' => $opening,
                'credit' => $credit,
                'consume' => $consume,
                'late_ded' => $lateDed,
                'encash' => $encash,
                'pending' => $pending,
                'closing' => $closing,
            ];
        })->values();

        return view('student.mycalendar', [
            'student' => $student,
            'calendarRows' => $calendarRows,
            'summary' => $summary,
            'pendingLeaves' => $pendingLeaves,
            'leaveSummaryRows' => $leaveSummaryRows,
            'joinDate' => $joinDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthStart->format('F Y'),
            'monthOptions' => $monthOptions,
            'years' => range(now()->year - 3, now()->year + 2),
        ]);
    }

    public function mapHoliday(Request $request): RedirectResponse
    {
        $student = $this->student();
        /** @var User|null $user */
        $user = Auth::user();
        $license = LicenseConfig::current();
        $requiresApproval = $license?->approvalRequired('student_calendar_mappings') ?? true;

        $validated = $request->validate([
            'leave_type' => ['required', 'string', 'in:holiday,casual,medical,earned'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        LeaveRequest::create([
            'requester_type' => 'student',
            'student_id' => $student->id,
            'staff_id' => null,
            'leave_type' => strtolower((string) $validated['leave_type']),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? 'Mapped from student calendar.',
            'status' => $requiresApproval ? 'pending' : 'approved',
            'approved_by' => $requiresApproval ? null : ($user?->isSuperAdmin() ? $user?->id : null),
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        return redirect()
            ->route('student.mycalendar', [
                'month' => Carbon::parse($validated['start_date'])->month,
                'year' => Carbon::parse($validated['start_date'])->year,
            ])
            ->with('status', $requiresApproval
                ? 'Mapping submitted. It will appear on calendar after admin approval.'
                : 'Mapping approved automatically and added to your calendar.');
    }

    private function extractAttendanceTimings($attendance): array
    {
        if (! $attendance) {
            return ['in' => null, 'out' => null];
        }

        $payload = is_array($attendance->capture_payload) ? $attendance->capture_payload : [];
        $in = $payload['check_in'] ?? $payload['in_time'] ?? $payload['first_in'] ?? null;
        $out = $payload['check_out'] ?? $payload['out_time'] ?? $payload['last_out'] ?? null;

        if (! $in && $attendance->captured_at) {
            $in = Carbon::parse($attendance->captured_at)->format('H:i');
        }

        if ($in) {
            $in = Carbon::parse($in)->format('H:i');
        }

        if ($out) {
            $out = Carbon::parse($out)->format('H:i');
        }

        return [
            'in' => $in,
            'out' => $out,
        ];
    }

    private function leaveDaysWithinRange(LeaveRequest $leave, Carbon $rangeStart, Carbon $rangeEnd): int
    {
        $start = Carbon::parse($leave->start_date)->startOfDay();
        $end = Carbon::parse($leave->end_date)->startOfDay();

        if ($end->lt($rangeStart) || $start->gt($rangeEnd)) {
            return 0;
        }

        $effectiveStart = $start->greaterThan($rangeStart) ? $start : $rangeStart;
        $effectiveEnd = $end->lessThan($rangeEnd) ? $end : $rangeEnd;

        return $effectiveStart->diffInDays($effectiveEnd) + 1;
    }

    public function results(): View
    {
        $student = $this->student();
        $results = $student->results()
            ->with(['exam', 'subject'])
            ->latest('id')
            ->get();

        $overallAverage = $results->count() > 0
            ? round((float) $results->avg('marks_obtained'), 2)
            : 0;

        // Group by exam for chart
        $resultsByExam = $results
            ->groupBy('exam_id')
            ->map(function ($group) {
                $exam = $group[0]->exam;
                $average = round((float) $group->avg('marks_obtained'), 2);

                return [
                    'exam' => $exam->name ?? 'Exam',
                    'average' => $average,
                    'count' => $group->count(),
                ];
            })
            ->values();

        // Group by subject
        $resultsBySubject = $results
            ->groupBy('subject_id')
            ->map(function ($group) {
                $subject = $group[0]->subject;
                $average = round((float) $group->avg('marks_obtained'), 2);

                return [
                    'subject' => $subject->name ?? 'Subject',
                    'average' => $average,
                    'grade' => $this->getGradeFromMarks($average),
                ];
            })
            ->sortByDesc('average')
            ->values();

        return view('student.results', [
            'student' => $student,
            'results' => $results,
            'summary' => [
                'total' => $results->count(),
                'average' => $overallAverage,
            ],
            'examData' => $resultsByExam,
            'subjectData' => $resultsBySubject,
        ]);
    }

    public function downloadResultsPdf()
    {
        $student = $this->student();
        $results = $student->results()
            ->with(['exam', 'subject'])
            ->latest('id')
            ->get();

        if ($results->isEmpty()) {
            return redirect()->route('student.results')->with('status', 'No result data available to download yet.');
        }

        $subjectRows = $results
            ->groupBy('subject_id')
            ->map(function ($group) {
                $subject = $group[0]->subject;
                $averageMarks = round((float) $group->avg('marks_obtained'), 2);

                return [
                    'subject' => $subject->name ?? 'Subject',
                    'marks' => $averageMarks,
                    'max_marks' => 100,
                    'percentage' => $averageMarks,
                    'grade' => $this->getGradeFromMarks($averageMarks),
                ];
            })
            ->sortBy('subject')
            ->values();

        $totalSubjects = $subjectRows->count();
        $totalObtained = round((float) $subjectRows->sum('marks'), 2);
        $totalMaximum = $totalSubjects * 100;
        $overallPercentage = $totalMaximum > 0
            ? round(($totalObtained / $totalMaximum) * 100, 2)
            : 0;

        $branding = $this->receiptBranding();

        $pdf = Pdf::loadView('student.results-pdf', [
            'student' => $student,
            'subjectRows' => $subjectRows,
            'totalSubjects' => $totalSubjects,
            'totalObtained' => $totalObtained,
            'totalMaximum' => $totalMaximum,
            'overallPercentage' => $overallPercentage,
            'generatedAt' => now(),
            'schoolName' => $branding['schoolName'],
            'logoUrl' => $branding['logoUrl'],
            'schoolAddress' => $branding['schoolAddress'],
            'schoolPhone' => $branding['schoolPhone'],
            'schoolEmail' => $branding['schoolEmail'],
            'branchName' => $branding['branchName'],
            'branchAddress' => $branding['branchAddress'],
        ]);

        $studentSlug = Str::of((string) $student->name)->slug('-')->value() ?: 'student';
        $filename = 'result-summary-'.$studentSlug.'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    public function downloadBook(StudyMaterial $material): BinaryFileResponse
    {
        $student = $this->student();
        abort_if(
            $material->status !== 'active'
            || ($material->academic_class_id && $material->academic_class_id !== $student->academic_class_id),
            404
        );

        return $this->downloadPublicFile($material->file_path, $material->title);
    }

    public function downloadExamPaper(ExamPaper $paper): BinaryFileResponse
    {
        $student = $this->student();
        $assignedSet = $this->assignedSet($student->roll_no);
        $paper->load('exam');

        abort_if(
            $paper->status !== 'active'
            || $paper->set_code !== $assignedSet
            || ! $paper->exam
            || $paper->exam->academic_class_id !== $student->academic_class_id,
            404
        );

        return $this->downloadPublicFile($paper->file_path, $paper->title);
    }

    private function student(): Student
    {
        $user = Auth::user();

        abort_unless($user && $user->role === 'student' && $user->student_id, 403);

        return Student::query()
            ->with(['academicClass', 'section', 'results.exam', 'results.subject', 'attendances', 'fees'])
            ->findOrFail($user->student_id);
    }

    private function questionsForExam(Exam $exam, string $assignedSet): Collection
    {
        return ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->where('set_code', $assignedSet)
            ->where('status', 'active')
            ->with('subject')
            ->orderBy('subject_id')
            ->orderByRaw('COALESCE(question_order, 999999) asc')
            ->orderBy('id')
            ->get();
    }

    private function activeAttempt(Student $student, Exam $exam): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->whereIn('status', ['started', 'locked'])
            ->latest('id')
            ->first();
    }

    private function assignedSet(?string $rollNo, array $activeSets = ['A', 'B', 'C', 'D', 'E']): string
    {
        $sets = array_values(array_filter($activeSets)) ?: ['A', 'B', 'C', 'D', 'E'];
        $digits = preg_replace('/\D+/', '', (string) $rollNo);

        if ($digits !== '') {
            return $sets[((int) $digits - 1) % count($sets)];
        }

        return $sets[abs(crc32((string) $rollNo)) % count($sets)];
    }

    private function guardStudentExam(Student $student, Exam $exam): void
    {
        abort_if($student->academic_class_id !== $exam->academic_class_id, 403);
        abort_if($exam->status !== 'active', 404);
    }

    private function downloadPublicFile(?string $path, ?string $title = null): BinaryFileResponse
    {
        abort_if(empty($path) || ! Storage::disk('public')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = trim((string) $title) !== ''
            ? trim((string) $title).($extension ? '.'.$extension : '')
            : basename($path);

        return response()->download(Storage::disk('public')->path($path), $filename);
    }

    private function receiptBranding(): array
    {
        $organization = Organization::current();
        $activeBranchId = (int) session('active_branch_id', 0);
        $activeBranch = $organization?->branches()->where('id', $activeBranchId)->first()
            ?? $organization?->branches()->where('is_active', true)->orderBy('id')->first();

        return [
            'schoolName' => $organization?->name ?: config('app.name', 'SchoolERP'),
            'logoUrl' => $this->resolveOrganizationLogoDataUri($organization),
            'schoolAddress' => $organization?->address,
            'schoolPhone' => $organization?->phone,
            'schoolEmail' => $organization?->email,
            'branchName' => $activeBranch?->name,
            'branchAddress' => $activeBranch?->address,
        ];
    }

    private function resolveOrganizationLogoDataUri(?Organization $organization): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        if ($organization?->logo_path && Storage::disk('public')->exists($organization->logo_path)) {
            return $this->toDataUri(Storage::disk('public')->path($organization->logo_path));
        }

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = public_path('school-logo.'.$ext);
            if (is_file($path)) {
                return $this->toDataUri($path);
            }
        }

        return null;
    }

    private function toDataUri(string $path): ?string
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function generateReceiptNoFromPayment(Payment $payment): string
    {
        $schoolCode = Str::of((string) config('app.name', 'SCH'))
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->substr(0, 4)
            ->value();

        if ($schoolCode === '') {
            $schoolCode = 'SCHL';
        }

        $serial = str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);

        return $schoolCode.'-RCPT-'.($payment->payment_date?->format('Ym') ?: now()->format('Ym')).'-'.$serial;
    }

    private function getGradeFromMarks(float $marks): string
    {
        if ($marks >= 90) {
            return 'A+';
        }
        if ($marks >= 80) {
            return 'A';
        }
        if ($marks >= 70) {
            return 'B';
        }
        if ($marks >= 60) {
            return 'C';
        }
        if ($marks >= 50) {
            return 'D';
        }

        return 'F';
    }
}
