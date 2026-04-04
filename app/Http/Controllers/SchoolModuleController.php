<?php

namespace App\Http\Controllers;

use App\Exports\ModuleExport;
use App\Models\AcademicClass;
use App\Models\AdmissionLead;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\BiometricDevice;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LicenseConfig;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Result;
use App\Models\SchoolNotification;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolModuleController extends Controller
{
    public function index(Request $request, string $module): View|string
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        $paginated = $this->records($module, $request);
        $leavePendingCount = null;
        $feesSummary = null;
        $studentCollegeStats = null;

        if ($module === 'leaves') {
            $leavePendingCount = (clone $this->scopedQuery('leaves'))
                ->where('status', 'pending')
                ->count();
        }

        if ($module === 'fees') {
            $feeSummaryRecords = $this->filteredModuleQuery('fees', $request, false)->get();

            $totalAmount = (float) $feeSummaryRecords->sum(fn (Fee $fee) => (float) $fee->amount);
            $totalPaid = (float) $feeSummaryRecords->sum(function (Fee $fee): float {
                $paymentsTotal = (float) collect($fee->payments ?? [])->sum('amount');

                return $paymentsTotal > 0 ? $paymentsTotal : (float) $fee->paid_amount;
            });
            $totalDue = max(0, $totalAmount - $totalPaid);

            $feesSummary = [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'paid_count' => $feeSummaryRecords->filter(function (Fee $fee): bool {
                    $paid = (float) (collect($fee->payments ?? [])->sum('amount') ?: $fee->paid_amount);

                    return $paid >= (float) $fee->amount && (float) $fee->amount > 0;
                })->count(),
                'partial_count' => $feeSummaryRecords->filter(function (Fee $fee): bool {
                    $paid = (float) (collect($fee->payments ?? [])->sum('amount') ?: $fee->paid_amount);

                    return $paid > 0 && $paid < (float) $fee->amount;
                })->count(),
                'pending_count' => $feeSummaryRecords->filter(function (Fee $fee): bool {
                    $paid = (float) (collect($fee->payments ?? [])->sum('amount') ?: $fee->paid_amount);

                    return $paid <= 0;
                })->count(),
            ];
        }

        if ($module === 'students') {
            $studentStatRecords = $this->filteredModuleQuery('students', $request, false)
                ->select(['id', 'college_name', 'current_college_name'])
                ->get();

            $totalStudents = $studentStatRecords->count();
            $previousFilled = $studentStatRecords->filter(fn (Student $student): bool => trim((string) ($student->college_name ?? '')) !== '')->count();
            $currentFilled = $studentStatRecords->filter(fn (Student $student): bool => trim((string) ($student->current_college_name ?? '')) !== '')->count();
            $bothFilled = $studentStatRecords->filter(fn (Student $student): bool => trim((string) ($student->college_name ?? '')) !== '' && trim((string) ($student->current_college_name ?? '')) !== '')->count();

            $studentCollegeStats = [
                'total' => $totalStudents,
                'previous_filled' => $previousFilled,
                'current_filled' => $currentFilled,
                'both_filled' => $bothFilled,
                'previous_percentage' => $totalStudents > 0 ? round(($previousFilled / $totalStudents) * 100, 1) : 0,
                'current_percentage' => $totalStudents > 0 ? round(($currentFilled / $totalStudents) * 100, 1) : 0,
                'both_percentage' => $totalStudents > 0 ? round(($bothFilled / $totalStudents) * 100, 1) : 0,
            ];
        }

        if ($request->ajax()) {
            return $this->renderTable($module, $moduleConfig, $request);
        }

        $lookups = $this->lookups($module);
        $view = isset($moduleConfig['view']) && view()->exists($moduleConfig['view']) ? $moduleConfig['view'] : 'modules.page';
        $records = $paginated['data'];
        $pagination = $paginated['pagination'];

        return view($view, compact('module', 'moduleConfig', 'records', 'lookups', 'pagination', 'leavePendingCount', 'feesSummary', 'studentCollegeStats'))->with('moduleKey', $module);
    }

    public function show($id, string $module): JsonResponse
    {
        $recordId = $this->normalizeRecordId($id);
        $record = $this->scopedQuery($module)->findOrFail($recordId);

        return response()->json(['record' => $record->toArray()]);
    }

    public function store(Request $request, string $module): JsonResponse
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        abort_if(! empty($moduleConfig['readonly']), 403);

        if ($module === 'students') {
            $this->ensureStudentLimitNotExceeded();
        }

        $record = DB::transaction(function () use ($request, $module) {
            $validated = $request->validate($this->rules($module));

            return $this->persistModule($module, $request, $validated);
        });

        return response()->json([
            'message' => $moduleConfig['singular'].' created successfully.',
            'html' => $this->renderTable($module, $moduleConfig, $request),
            'record' => $record->toArray(),
        ]);
    }

    public function update(Request $request, $id, string $module): JsonResponse
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        abort_if(! empty($moduleConfig['readonly']), 403);
        $recordId = $this->normalizeRecordId($id);

        $record = DB::transaction(function () use ($request, $module, $recordId) {
            $current = $this->scopedQuery($module)->findOrFail($recordId);
            $validated = $request->validate($this->rules($module, $current));

            return $this->persistModule($module, $request, $validated, $current);
        });

        return response()->json([
            'message' => $moduleConfig['singular'].' updated successfully.',
            'html' => $this->renderTable($module, $moduleConfig, $request),
            'record' => $record->toArray(),
        ]);
    }

    public function destroy(Request $request, $id, string $module): JsonResponse
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        abort_if(! empty($moduleConfig['readonly']), 403);
        $recordId = $this->normalizeRecordId($id);

        $record = $this->scopedQuery($module)->findOrFail($recordId);
        if (Schema::hasColumn($record->getTable(), 'deleted_by')) {
            $record->deleted_by = $request->user()->id;
            $record->save();
        }
        $record->delete();

        $this->audit($request, $module, 'delete', $record, $record->toArray(), [], $moduleConfig['singular'].' deleted');

        return response()->json([
            'message' => $moduleConfig['singular'].' deleted successfully.',
            'html' => $this->renderTable($module, $moduleConfig, $request),
        ]);
    }

    public function quickLeaveStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        /** @var LeaveRequest $leave */
        $leave = $this->scopedQuery('leaves')->findOrFail($id);
        $oldValues = $leave->toArray();

        $leave->status = $validated['status'];
        $leave->approved_by = $validated['status'] === 'approved' ? $request->user()->id : null;
        $leave->updated_by = $request->user()->id;
        $leave->save();

        $this->audit(
            $request,
            'leaves',
            'update',
            $leave,
            $oldValues,
            $leave->fresh()->toArray(),
            'Leave request status updated from list view'
        );

        if ($request->ajax()) {
            $moduleConfig = SchoolModuleRegistry::get('leaves');
            $pendingCount = (clone $this->scopedQuery('leaves'))
                ->where('status', 'pending')
                ->count();

            return response()->json([
                'message' => 'Leave request marked as '.ucfirst($validated['status']).'.',
                'html' => $this->renderTable('leaves', $moduleConfig, $request),
                'pending_count' => $pendingCount,
            ]);
        }

        return back()->with('status', 'Leave request marked as '.ucfirst($validated['status']).'.');
    }

    public function exportPdf(string $module)
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        [$headings, $rows] = $this->exportData($module, $moduleConfig);

        return Pdf::loadView('modules.export-pdf', [
            'title' => $moduleConfig['title'].' Report',
            'headings' => $headings,
            'rows' => $rows,
        ])->download($module.'-report.pdf');
    }

    public function exportExcel(string $module)
    {
        $moduleConfig = SchoolModuleRegistry::get($module);
        [$headings, $rows] = $this->exportData($module, $moduleConfig);

        return Excel::download(new ModuleExport($headings, $rows), $module.'-report.xlsx');
    }

    public function downloadFeeReceipt(int $id)
    {
        /** @var Fee $fee */
        $fee = $this->scopedQuery('fees')
            ->with(['student.academicClass', 'student.section', 'payments'])
            ->findOrFail($id);

        $supportsImages = extension_loaded('gd');
        $logoUrl = $this->resolveLogoPath($supportsImages);
        $paymentHistory = $fee->payments
            ->sortByDesc(fn (Payment $payment) => sprintf('%s-%010d', (string) $payment->payment_date, $payment->id))
            ->values();
        $latestPayment = $paymentHistory->first();

        $totalPaidAmount = $paymentHistory->isNotEmpty()
            ? (float) $paymentHistory->sum('amount')
            : (float) $fee->paid_amount;
        $currentPaymentAmount = (float) ($latestPayment?->amount ?? $fee->paid_amount);
        $remainingDueAmount = max(0, (float) $fee->amount - $totalPaidAmount);

        $receiptNo = (string) ($latestPayment?->receipt_no ?: $fee->receipt_no ?: ($latestPayment ? $this->generateReceiptNoFromPayment($latestPayment) : 'RCPT-'.str_pad((string) $fee->id, 6, '0', STR_PAD_LEFT)));
        $paymentDate = $latestPayment?->payment_date ?: $fee->updated_at;

        $pdf = Pdf::loadView('fees.receipt-pdf', [
            'fee' => $fee,
            'paymentHistory' => $paymentHistory,
            'latestPayment' => $latestPayment,
            'currentPaymentAmount' => $currentPaymentAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'remainingDueAmount' => $remainingDueAmount,
            'receiptNo' => $receiptNo,
            'paymentDate' => $paymentDate,
            'generatedAt' => now(),
            'schoolName' => config('app.name', 'SchoolERP'),
            'logoUrl' => $logoUrl,
        ]);

        $safeReceiptNo = Str::of($receiptNo)->replaceMatches('/[^A-Za-z0-9_-]/', '')->value() ?: 'receipt';

        return $pdf->download('payment-receipt-'.$safeReceiptNo.'.pdf');
    }

    public function masterCalendar(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $defaultPerPage = $user->isTeacher() ? 25 : 50;
        $selectedYear = max(2020, min(2100, (int) $request->integer('year', now()->year)));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', now()->month)));
        $selectedClassId = (int) $request->integer('class_id', 0);
        $selectedSectionId = (int) $request->integer('section_id', 0);
        $perPage = max(10, min(100, (int) $request->integer('per_page', $defaultPerPage)));
        $rollNo = trim((string) $request->input('roll_no', ''));

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        // All dates in the selected month
        $monthDates = [];
        for ($cur = $monthStart->copy(); $cur->lte($monthEnd); $cur->addDay()) {
            $monthDates[] = $cur->copy();
        }

        $specialDates = $this->buildSpecialDatesMap($monthStart, $monthEnd);
        $today = now()->startOfDay();

        // Teacher-scoped classes/sections
        $teacherSectionIds = $user->isTeacher() ? $this->teacherSectionIds($user) : null;
        $teacherClassIds = $teacherSectionIds !== null
            ? Section::query()->whereIn('id', $teacherSectionIds ?: [0])->pluck('academic_class_id')->unique()->values()->all()
            : null;

        $classes = AcademicClass::query()
            ->when($teacherClassIds !== null, fn ($q) => $q->whereIn('id', $teacherClassIds ?: [0]))
            ->orderBy('name')
            ->get();

        $sections = Section::query()
            ->when($teacherSectionIds !== null, fn ($q) => $q->whereIn('id', $teacherSectionIds ?: [0]))
            ->when($selectedClassId > 0, fn ($q) => $q->where('academic_class_id', $selectedClassId))
            ->orderBy('name')
            ->get();

        // Students filtered by class/section (and teacher scope)
        $teacherStudentIds = $user->isTeacher() ? $this->teacherStudentIds($user) : null;

        $studentsPage = Student::query()
            ->with(['academicClass', 'section'])
            ->when($teacherStudentIds !== null, fn ($q) => $q->whereIn('id', $teacherStudentIds ?: [0]))
            ->when($selectedClassId > 0, fn ($q) => $q->where('academic_class_id', $selectedClassId))
            ->when($selectedSectionId > 0, fn ($q) => $q->where('section_id', $selectedSectionId))
            ->when($rollNo !== '', function ($q) use ($rollNo) {
                $q->where(function ($inner) use ($rollNo) {
                    $inner->where('roll_no', 'like', '%'.$rollNo.'%')
                        ->orWhere('admission_no', 'like', '%'.$rollNo.'%');
                });
            })
            ->orderBy('roll_no')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();

        if ($studentsPage->isEmpty() && $studentsPage->lastPage() > 0 && $studentsPage->currentPage() > $studentsPage->lastPage()) {
            $query = $request->query();
            $query['page'] = $studentsPage->lastPage();

            return redirect()->route('master.calendar', $query);
        }

        $students = $studentsPage->getCollection();

        $studentIds = $students->pluck('id')->all();

        // Attendances for all students this month (keyed student_id → date → status)
        $allAttendances = [];
        if ($studentIds !== []) {
            Attendance::query()
                ->where('attendance_for', 'student')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->get()
                ->each(function ($att) use (&$allAttendances) {
                    $allAttendances[$att->student_id][$att->attendance_date->toDateString()] = (string) $att->status;
                });
        }

        // Approved leaves for all students this month (keyed student_id → date → true)
        $allLeaves = [];
        if ($studentIds !== []) {
            LeaveRequest::query()
                ->where('requester_type', 'student')
                ->whereIn('student_id', $studentIds)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->whereDate('end_date', '>=', $monthStart->toDateString())
                ->get()
                ->each(function ($leave) use (&$allLeaves, $monthStart, $monthEnd) {
                    $lStart = Carbon::parse($leave->start_date)->startOfDay();
                    $lEnd = Carbon::parse($leave->end_date)->startOfDay();
                    for ($cur = $lStart->copy(); $cur->lte($lEnd); $cur->addDay()) {
                        if ($cur->between($monthStart, $monthEnd)) {
                            $allLeaves[$leave->student_id][$cur->toDateString()] = true;
                        }
                    }
                });
        }

        // Build matrix (one row per student, one cell per date)
        $matrix = $students->map(function ($student) use ($monthDates, $specialDates, $allAttendances, $allLeaves, $today, $monthStart) {
            $days = [];
            $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0];
            $joinDate = $this->studentActiveFrom($student, $monthStart);

            foreach ($monthDates as $date) {
                $dateKey = $date->toDateString();

                if ($date->lt($joinDate)) {
                    $days[$dateKey] = 'none';

                    continue;
                }

                if (isset($specialDates[$dateKey])) {
                    $status = ($specialDates[$dateKey]['type'] ?? 'holiday') === 'weekoff' ? 'weekoff' : 'holiday';
                    $summary['holiday']++;
                } elseif (isset($allLeaves[$student->id][$dateKey])) {
                    $status = 'leave';
                    $summary['leave']++;
                } elseif (isset($allAttendances[$student->id][$dateKey])) {
                    $s = $allAttendances[$student->id][$dateKey];
                    if ($s === 'leave') {
                        $status = 'leave';
                        $summary['leave']++;
                    } elseif (in_array($s, ['present', 'late'], true)) {
                        $status = 'present';
                        $summary['present']++;
                    } else {
                        $status = 'absent';
                        $summary['absent']++;
                    }
                } elseif ($date->lte($today)) {
                    $status = 'absent';
                    $summary['absent']++;
                } else {
                    $status = 'none';
                }

                $days[$dateKey] = $status;
            }

            return [
                'student' => $student,
                'days' => $days,
                'summary' => $summary,
            ];
        })->all();

        $monthOptions = collect(range(1, 12))->map(fn (int $m) => [
            'value' => $m,
            'label' => Carbon::create(null, $m, 1)->format('F'),
        ]);

        return view('master-calendar', [
            'monthDates' => $monthDates,
            'specialDates' => $specialDates,
            'matrix' => $matrix,
            'studentsPage' => $studentsPage,
            'classes' => $classes,
            'sections' => $sections,
            'selectedClassId' => $selectedClassId,
            'selectedSectionId' => $selectedSectionId,
            'perPage' => $perPage,
            'rollNo' => $rollNo,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthStart->format('F Y'),
            'monthOptions' => $monthOptions,
            'years' => range(now()->year - 3, now()->year + 2),
        ]);
    }

    public function exportMasterCalendar(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $selectedYear = max(2020, min(2100, (int) $request->integer('year', now()->year)));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', now()->month)));
        $selectedClassId = (int) $request->integer('class_id', 0);
        $selectedSectionId = (int) $request->integer('section_id', 0);
        $rollNo = trim((string) $request->input('roll_no', ''));

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $monthDates = [];
        for ($cur = $monthStart->copy(); $cur->lte($monthEnd); $cur->addDay()) {
            $monthDates[] = $cur->copy();
        }

        $specialDates = $this->buildSpecialDatesMap($monthStart, $monthEnd);
        $today = now()->startOfDay();

        $teacherSectionIds = $user->isTeacher() ? $this->teacherSectionIds($user) : null;
        $teacherStudentIds = $user->isTeacher() ? $this->teacherStudentIds($user) : null;

        $students = Student::query()
            ->with(['academicClass', 'section'])
            ->when($teacherStudentIds !== null, fn ($q) => $q->whereIn('id', $teacherStudentIds ?: [0]))
            ->when($selectedClassId > 0, fn ($q) => $q->where('academic_class_id', $selectedClassId))
            ->when($selectedSectionId > 0, fn ($q) => $q->where('section_id', $selectedSectionId))
            ->when($teacherSectionIds !== null && $selectedSectionId <= 0, fn ($q) => $q->whereIn('section_id', $teacherSectionIds ?: [0]))
            ->when($rollNo !== '', function ($q) use ($rollNo) {
                $q->where(function ($inner) use ($rollNo) {
                    $inner->where('roll_no', 'like', '%'.$rollNo.'%')
                        ->orWhere('admission_no', 'like', '%'.$rollNo.'%');
                });
            })
            ->orderBy('roll_no')
            ->orderBy('first_name')
            ->get();

        $studentIds = $students->pluck('id')->all();

        $allAttendances = [];
        if ($studentIds !== []) {
            Attendance::query()
                ->where('attendance_for', 'student')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->get()
                ->each(function ($att) use (&$allAttendances) {
                    $allAttendances[$att->student_id][$att->attendance_date->toDateString()] = (string) $att->status;
                });
        }

        $allLeaves = [];
        if ($studentIds !== []) {
            LeaveRequest::query()
                ->where('requester_type', 'student')
                ->whereIn('student_id', $studentIds)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $monthEnd->toDateString())
                ->whereDate('end_date', '>=', $monthStart->toDateString())
                ->get()
                ->each(function ($leave) use (&$allLeaves, $monthStart, $monthEnd) {
                    $lStart = Carbon::parse($leave->start_date)->startOfDay();
                    $lEnd = Carbon::parse($leave->end_date)->startOfDay();
                    for ($cur = $lStart->copy(); $cur->lte($lEnd); $cur->addDay()) {
                        if ($cur->between($monthStart, $monthEnd)) {
                            $allLeaves[$leave->student_id][$cur->toDateString()] = true;
                        }
                    }
                });
        }

        $filename = 'master-calendar-'.$monthStart->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($students, $monthDates, $specialDates, $allAttendances, $allLeaves, $today, $monthStart): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            $header = ['Student Name', 'Roll No', 'Admission No', 'Class', 'Section'];
            foreach ($monthDates as $date) {
                $header[] = $date->format('d-M');
            }
            $header = array_merge($header, ['Present', 'Absent', 'Leave', 'Holiday']);
            fputcsv($handle, $header);

            foreach ($students as $student) {
                $joinDate = $this->studentActiveFrom($student, $monthStart);
                $row = [
                    $student->full_name,
                    (string) ($student->roll_no ?? ''),
                    (string) ($student->admission_no ?? ''),
                    (string) optional($student->academicClass)->name,
                    (string) optional($student->section)->name,
                ];

                $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0];

                foreach ($monthDates as $date) {
                    $dateKey = $date->toDateString();

                    if ($date->lt($joinDate)) {
                        $row[] = '-';

                        continue;
                    }

                    if (isset($specialDates[$dateKey])) {
                        $status = ($specialDates[$dateKey]['type'] ?? 'holiday') === 'weekoff' ? 'weekoff' : 'holiday';
                        $summary['holiday']++;
                    } elseif (isset($allLeaves[$student->id][$dateKey])) {
                        $status = 'leave';
                        $summary['leave']++;
                    } elseif (isset($allAttendances[$student->id][$dateKey])) {
                        $s = $allAttendances[$student->id][$dateKey];
                        if ($s === 'leave') {
                            $status = 'leave';
                            $summary['leave']++;
                        } elseif (in_array($s, ['present', 'late'], true)) {
                            $status = 'present';
                            $summary['present']++;
                        } else {
                            $status = 'absent';
                            $summary['absent']++;
                        }
                    } elseif ($date->lte($today)) {
                        $status = 'absent';
                        $summary['absent']++;
                    } else {
                        $status = 'none';
                    }

                    $row[] = match ($status) {
                        'present' => 'P',
                        'absent' => 'A',
                        'leave' => 'L',
                        'holiday' => 'H',
                        'weekoff' => 'W',
                        default => '-',
                    };
                }

                $row[] = $summary['present'];
                $row[] = $summary['absent'];
                $row[] = $summary['leave'];
                $row[] = $summary['holiday'];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function myAttendance(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $selectedYear = max(2020, min(2100, (int) $request->integer('year', now()->year)));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', now()->month)));
        $statusFilter = strtolower(trim((string) $request->input('status', '')));
        $allowedStatuses = ['present', 'late', 'absent', 'leave'];
        if (! in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $staff = $user->staff;
        $staffId = (int) ($user->staff_id ?? 0);

        $attendances = collect();
        if ($staffId > 0) {
            $attendances = Attendance::query()
                ->where('attendance_for', 'staff')
                ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where(function ($query) use ($staffId) {
                    $query->where('staff_attendance_id', $staffId)
                        ->orWhere(function ($inner) use ($staffId) {
                            $inner->whereNull('staff_attendance_id')
                                ->where('staff_id', $staffId);
                        });
                })
                ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
                ->orderByDesc('attendance_date')
                ->get();
        }

        $rows = $attendances->map(function (Attendance $attendance) {
            $timings = $this->extractAttendanceTimings($attendance);

            return [
                'date' => $attendance->attendance_date,
                'status' => (string) $attendance->status,
                'method' => (string) ($attendance->attendance_method ?: 'manual'),
                'in_time' => $timings['in'],
                'out_time' => $timings['out'],
                'remarks' => (string) ($attendance->remarks ?: ''),
            ];
        });

        $total = $attendances->count();
        $present = $attendances->whereIn('status', ['present', 'late'])->count();
        $absent = $attendances->where('status', 'absent')->count();
        $leave = $attendances->where('status', 'leave')->count();
        $late = $attendances->where('status', 'late')->count();
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        $monthOptions = collect(range(1, 12))->map(fn (int $m) => [
            'value' => $m,
            'label' => Carbon::create(null, $m, 1)->format('F'),
        ]);

        return view('staff.my-attendance', [
            'staff' => $staff,
            'staffName' => $staff?->full_name ?: $user->name,
            'rows' => $rows,
            'summary' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'late' => $late,
                'percentage' => $percentage,
            ],
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'statusFilter' => $statusFilter,
            'monthLabel' => $monthStart->format('F Y'),
            'monthOptions' => $monthOptions,
            'years' => range(now()->year - 3, now()->year + 2),
            'statusOptions' => [
                '' => 'All Status',
                'present' => 'Present',
                'late' => 'Late',
                'absent' => 'Absent',
                'leave' => 'Leave',
            ],
            'hasStaffMapping' => $staffId > 0,
        ]);
    }

    public function storeMasterCalendarDayOff(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'entry_type' => ['required', Rule::in(['holiday', 'weekoff'])],
            'title' => ['nullable', 'string', 'max:150'],
            'weekday' => ['nullable', Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['required', Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $entryType = (string) $validated['entry_type'];
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        $userId = (int) $request->user()->id;
        $createdDates = [];

        if ($entryType === 'holiday') {
            Holiday::query()->create([
                'title' => trim((string) ($validated['title'] ?? 'Holiday')) ?: 'Holiday',
                'holiday_type' => 'holiday',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'description' => $validated['description'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $createdDates[] = $cursor->toDateString();
            }

            $message = 'Holiday added successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'entry_type' => 'holiday',
                    'title' => trim((string) ($validated['title'] ?? 'Holiday')) ?: 'Holiday',
                    'created_dates' => $createdDates,
                ]);
            }

            return back()->with('status', $message);
        }

        $weekdayValues = collect($validated['weekdays'] ?? [])
            ->map(fn ($day) => strtolower(trim((string) $day)))
            ->filter()
            ->unique()
            ->values();

        if ($weekdayValues->isEmpty() && ! empty($validated['weekday'])) {
            $weekdayValues = collect([(string) $validated['weekday']]);
        }

        if ($weekdayValues->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['weekdays' => 'Select at least one weekday for weekoff setup.']);
        }

        $effectiveEnd = $end->copy();
        $minimumYearEnd = $start->copy()->addYear()->subDay();
        if ($effectiveEnd->lt($minimumYearEnd)) {
            $effectiveEnd = $minimumYearEnd;
        }

        $weekdayMap = [
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
        ];

        $targetWeekdays = $weekdayValues
            ->map(fn (string $weekday) => $weekdayMap[$weekday] ?? null)
            ->filter(fn ($weekday) => $weekday !== null)
            ->values()
            ->all();
        $created = 0;
        $skipped = 0;

        for ($cursor = $start->copy(); $cursor->lte($effectiveEnd); $cursor->addDay()) {
            if (! in_array($cursor->dayOfWeek, $targetWeekdays, true)) {
                continue;
            }

            $date = $cursor->toDateString();
            $weekdayName = strtolower($cursor->englishDayOfWeek);

            $exists = Holiday::query()
                ->whereDate('start_date', $date)
                ->whereDate('end_date', $date)
                ->where('holiday_type', 'weekoff')
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            Holiday::query()->create([
                'title' => 'Week Off - '.ucfirst($weekdayName),
                'holiday_type' => 'weekoff',
                'start_date' => $date,
                'end_date' => $date,
                'description' => $validated['description'] ?? 'Configured from Master Calendar',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $createdDates[] = $date;
            $created++;
        }

        $message = "Weekoff setup completed. Added {$created} day(s), skipped {$skipped} existing day(s). Applied till ".$effectiveEnd->format('d M Y').'.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'entry_type' => 'weekoff',
                'title' => 'Week Off',
                'created_dates' => $createdDates,
                'effective_end' => $effectiveEnd->toDateString(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function studentCalendarIndex(Request $request): View
    {
        $selectedClassId = (int) $request->integer('class_id', 0);
        $selectedSectionId = (int) $request->integer('section_id', 0);
        $search = trim((string) $request->input('q', ''));
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = $this->scopedQuery('students')
            ->with(['academicClass:id,name', 'section:id,name'])
            ->select(['id', 'first_name', 'last_name', 'roll_no', 'admission_no', 'academic_class_id', 'section_id'])
            ->when($selectedClassId > 0, fn ($builder) => $builder->where('academic_class_id', $selectedClassId))
            ->when($selectedSectionId > 0, fn ($builder) => $builder->where('section_id', $selectedSectionId))
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('roll_no', 'like', '%'.$search.'%')
                        ->orWhere('admission_no', 'like', '%'.$search.'%');
                });
            });

        $students = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $lookups = $this->lookups('students');

        return view('students.calendar-index', [
            'students' => $students,
            'classes' => $lookups['academic_classes'] ?? [],
            'sections' => $lookups['sections'] ?? [],
            'selectedClassId' => $selectedClassId,
            'selectedSectionId' => $selectedSectionId,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function studentCalendarSections(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $classId = (int) $request->integer('class_id', 0);

        $sections = Section::query()
            ->select(['id', 'name', 'academic_class_id'])
            ->when($classId > 0, fn ($query) => $query->where('academic_class_id', $classId))
            ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $this->teacherSectionIds($user) ?: [0]))
            ->orderBy('name')
            ->get();

        return response()->json([
            'sections' => $sections->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'academic_class_id' => $section->academic_class_id,
            ])->values()->all(),
        ]);
    }

    public function importAttendanceExcel(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:academic_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'attendance_date' => ['required', 'date'],
            'attendance_file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $classId = (int) ($validated['class_id'] ?? 0);
        $sectionId = (int) ($validated['section_id'] ?? 0);
        $defaultDate = Carbon::parse($validated['attendance_date'])->toDateString();

        if ($sectionId > 0 && $classId <= 0) {
            $classId = (int) (Section::query()->where('id', $sectionId)->value('academic_class_id') ?? 0);
        }

        if ($sectionId > 0 && $classId > 0) {
            $sectionMatches = Section::query()
                ->where('id', $sectionId)
                ->where('academic_class_id', $classId)
                ->exists();

            if (! $sectionMatches) {
                return back()->withErrors([
                    'section_id' => 'Selected section does not belong to selected class.',
                ])->withInput();
            }
        }

        if ($user->isTeacher()) {
            $allowedSectionIds = $this->teacherSectionIds($user);
            $allowedClassIds = Section::query()
                ->whereIn('id', $allowedSectionIds ?: [0])
                ->pluck('academic_class_id')
                ->unique()
                ->values()
                ->all();

            if ($classId > 0 && ! in_array($classId, $allowedClassIds, true)) {
                abort(403, 'You are not allowed to import attendance for this class.');
            }

            if ($sectionId > 0 && ! in_array($sectionId, $allowedSectionIds, true)) {
                abort(403, 'You are not allowed to import attendance for this section.');
            }
        }

        $filePath = $request->file('attendance_file')->getRealPath();
        if (! $filePath) {
            return back()->withErrors([
                'attendance_file' => 'Unable to read uploaded file.',
            ])->withInput();
        }

        $sheetRows = IOFactory::load($filePath)->getActiveSheet()->toArray(null, true, true, false);
        if (count($sheetRows) < 2) {
            return back()->withErrors([
                'attendance_file' => 'Excel file must contain header row and at least one data row.',
            ])->withInput();
        }

        $headerRow = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), (array) ($sheetRows[0] ?? []));
        $headerIndex = [];
        foreach ($headerRow as $index => $header) {
            if ($header !== '' && ! array_key_exists($header, $headerIndex)) {
                $headerIndex[$header] = $index;
            }
        }

        $rollIndex = $headerIndex['roll_no']
            ?? $headerIndex['roll']
            ?? $headerIndex['roll_number']
            ?? $headerIndex['rollno']
            ?? null;

        $inIndex = $headerIndex['in_time']
            ?? $headerIndex['in']
            ?? $headerIndex['check_in']
            ?? $headerIndex['intime']
            ?? null;

        $outIndex = $headerIndex['out_time']
            ?? $headerIndex['out']
            ?? $headerIndex['check_out']
            ?? $headerIndex['outtime']
            ?? null;

        $dateIndex = $headerIndex['date']
            ?? $headerIndex['attendance_date']
            ?? null;

        $classIndex = $headerIndex['class']
            ?? $headerIndex['class_name']
            ?? $headerIndex['class_code']
            ?? null;

        $sectionIndex = $headerIndex['section']
            ?? $headerIndex['section_name']
            ?? $headerIndex['section_code']
            ?? null;

        if ($rollIndex === null || $inIndex === null || $outIndex === null) {
            return back()->withErrors([
                'attendance_file' => 'Required columns missing. Please include roll_no, in_time and out_time columns.',
            ])->withInput();
        }

        if ($classIndex === null || $sectionIndex === null) {
            return back()->withErrors([
                'attendance_file' => 'Required columns missing. Please include class and section columns in Excel.',
            ])->withInput();
        }

        $classRecords = AcademicClass::query()->select(['id', 'name', 'code'])->get();
        $sectionRecords = Section::query()->select(['id', 'name', 'code', 'academic_class_id'])->get();

        if ($user->isTeacher()) {
            $allowedSectionIds = $this->teacherSectionIds($user);
            $allowedClassIds = Section::query()
                ->whereIn('id', $allowedSectionIds ?: [0])
                ->pluck('academic_class_id')
                ->unique()
                ->values()
                ->all();

            $classRecords = $classRecords->whereIn('id', $allowedClassIds)->values();
            $sectionRecords = $sectionRecords->whereIn('id', $allowedSectionIds)->values();
        }

        $students = Student::query()
            ->select(['id', 'roll_no', 'section_id', 'academic_class_id'])
            ->when($classId > 0, fn ($query) => $query->where('academic_class_id', $classId))
            ->when($sectionId > 0, fn ($query) => $query->where('section_id', $sectionId))
            ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $this->teacherStudentIds($user) ?: [0]))
            ->get();

        $studentsByClassSectionRoll = [];
        foreach ($students as $studentRecord) {
            $normalizedRoll = strtolower(trim((string) $studentRecord->roll_no));
            if ($normalizedRoll === '') {
                continue;
            }

            $classSectionRollKey = $studentRecord->academic_class_id.'|'.((int) $studentRecord->section_id).'|'.$normalizedRoll;
            $studentsByClassSectionRoll[$classSectionRollKey] = $studentRecord;
        }

        if ($students->isEmpty()) {
            return back()->withErrors([
                'attendance_file' => 'No students found in selected class/section to import attendance.',
            ])->withInput();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $classUnresolved = 0;
        $sectionUnresolved = 0;
        $failedRows = [];

        foreach (array_slice($sheetRows, 1) as $row) {
            $rollNo = strtolower(trim((string) ($row[$rollIndex] ?? '')));
            $rawClass = $classIndex !== null ? trim((string) ($row[$classIndex] ?? '')) : '';
            $rawSection = $sectionIndex !== null ? trim((string) ($row[$sectionIndex] ?? '')) : '';
            $rawDate = $dateIndex !== null ? trim((string) ($row[$dateIndex] ?? '')) : '';
            $rawInTime = trim((string) ($row[$inIndex] ?? ''));
            $rawOutTime = trim((string) ($row[$outIndex] ?? ''));

            if ($rollNo === '') {
                $skipped++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => '',
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Missing roll_no',
                ];

                continue;
            }

            $rowClassId = $this->resolveImportClassId($row[$classIndex] ?? null, $classRecords);

            if ($rowClassId <= 0) {
                $classUnresolved++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => strtoupper($rollNo),
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Unable to resolve class',
                ];

                continue;
            }

            if ($classId > 0 && $rowClassId !== $classId) {
                $skipped++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => strtoupper($rollNo),
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Row class does not match selected class filter',
                ];

                continue;
            }

            if ($user->isTeacher()) {
                $allowedClassIds = $sectionRecords->pluck('academic_class_id')->unique()->values()->all();
                if (! in_array($rowClassId, $allowedClassIds, true)) {
                    $skipped++;
                    $failedRows[] = [
                        'class' => $rawClass,
                        'section' => $rawSection,
                        'roll_no' => strtoupper($rollNo),
                        'in_time' => $rawInTime,
                        'out_time' => $rawOutTime,
                        'date' => $rawDate,
                        'reason' => 'Teacher scope does not allow this class',
                    ];

                    continue;
                }
            }

            $rowSectionId = $this->resolveImportSectionId($row[$sectionIndex] ?? null, $rowClassId, $sectionRecords);

            if ($rowSectionId <= 0) {
                $sectionUnresolved++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => strtoupper($rollNo),
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Unable to resolve section',
                ];

                continue;
            }

            if ($sectionId > 0 && $rowSectionId !== $sectionId) {
                $skipped++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => strtoupper($rollNo),
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Row section does not match selected section filter',
                ];

                continue;
            }

            if ($rowSectionId > 0) {
                $sectionMatches = $sectionRecords->contains(fn (Section $sectionRecord) => (int) $sectionRecord->id === $rowSectionId && (int) $sectionRecord->academic_class_id === $rowClassId);
                if (! $sectionMatches) {
                    $skipped++;
                    $failedRows[] = [
                        'class' => $rawClass,
                        'section' => $rawSection,
                        'roll_no' => strtoupper($rollNo),
                        'in_time' => $rawInTime,
                        'out_time' => $rawOutTime,
                        'date' => $rawDate,
                        'reason' => 'Section does not belong to class',
                    ];

                    continue;
                }
            }

            /** @var Student|null $student */
            $student = $studentsByClassSectionRoll[$rowClassId.'|'.$rowSectionId.'|'.$rollNo] ?? null;

            if (! $student) {
                $notFound++;
                $failedRows[] = [
                    'class' => $rawClass,
                    'section' => $rawSection,
                    'roll_no' => strtoupper($rollNo),
                    'in_time' => $rawInTime,
                    'out_time' => $rawOutTime,
                    'date' => $rawDate,
                    'reason' => 'Student not found for class/section/roll_no',
                ];

                continue;
            }

            $inTime = $this->parseImportedTimeValue($row[$inIndex] ?? null);
            $outTime = $this->parseImportedTimeValue($row[$outIndex] ?? null);

            $rowDate = $defaultDate;
            if ($dateIndex !== null) {
                $parsedDate = $this->parseImportedDateValue($row[$dateIndex] ?? null);
                if ($parsedDate) {
                    $rowDate = $parsedDate;
                }
            }

            $attendance = Attendance::query()->firstOrNew([
                'attendance_for' => 'student',
                'student_id' => $student->id,
                'attendance_date' => $rowDate,
            ]);

            $isNew = ! $attendance->exists;
            $status = ($inTime || $outTime) ? 'present' : 'absent';
            $capturedAt = $inTime ? Carbon::parse($rowDate.' '.$inTime)->format('Y-m-d H:i:s') : null;

            $attendance->academic_class_id = $rowClassId;
            $attendance->section_id = $rowSectionId > 0 ? $rowSectionId : $student->section_id;
            $attendance->attendance_method = 'biometric_machine';
            $attendance->status = $status;
            $attendance->sync_status = 'synced';
            $attendance->marked_by_staff_id = (int) ($user->staff_id ?? 0) ?: null;
            $attendance->captured_at = $capturedAt;
            $attendance->capture_payload = [
                'in_time' => $inTime,
                'out_time' => $outTime,
                'source' => 'excel_import',
            ];
            $attendance->remarks = 'Imported from attendance Excel';
            $attendance->created_by = $attendance->created_by ?: (int) $user->id;
            $attendance->updated_by = (int) $user->id;
            $attendance->save();

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Attendance import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Roll not found: {$notFound}, Class unresolved: {$classUnresolved}, Section unresolved: {$sectionUnresolved}.";

        $reasonCounts = collect($failedRows)
            ->pluck('reason')
            ->filter()
            ->countBy()
            ->sortDesc();
        $topReasons = $reasonCounts
            ->take(3)
            ->map(fn ($count, $reason) => $reason.' ('.$count.')')
            ->values()
            ->all();

        $toastType = $failedRows === [] ? 'success' : 'warning';
        $toastMessage = $failedRows === []
            ? "Import success: {$created} created, {$updated} updated."
            : "Import completed with issues. Created: {$created}, Updated: {$updated}, Failed/Skipped: ".count($failedRows).'. Top reasons: '.implode('; ', $topReasons);

        if ($failedRows !== []) {
            $request->session()->put('attendance_import_failed_rows', $failedRows);
        } else {
            $request->session()->forget('attendance_import_failed_rows');
        }

        return back()
            ->with('status', $message)
            ->with('attendance_import_toast', [
                'type' => $toastType,
                'message' => $toastMessage,
            ]);
    }

    public function importStudentCollegeExcel(Request $request): RedirectResponse
    {
        $this->ensureStudentCollegeImportAccess($request);

        if ($request->boolean('confirm_import')) {
            /** @var User $user */
            $user = $request->user();
            $pendingUpdates = $request->session()->get('student_import_preview_updates', []);

            if (! is_array($pendingUpdates) || $pendingUpdates === []) {
                return back()->withErrors([
                    'student_update_file' => 'No preview data found. Please upload and preview file again.',
                ]);
            }

            $updated = 0;
            $missing = 0;

            foreach ($pendingUpdates as $item) {
                $studentId = (int) ($item['student_id'] ?? 0);
                $lineNo = (int) ($item['line'] ?? 0);
                if ($studentId <= 0) {
                    $missing++;

                    continue;
                }

                /** @var Student|null $student */
                $student = $this->scopedQuery('students')
                    ->select(['id', 'college_name', 'current_college_name'])
                    ->find($studentId);

                if (! $student) {
                    $missing++;

                    continue;
                }

                $dirty = false;
                $oldValues = [];
                $newValues = [];

                if (array_key_exists('college_name', $item) && $item['college_name'] !== null && $student->college_name !== $item['college_name']) {
                    $oldValues['college_name'] = (string) ($student->college_name ?? '');
                    $newValues['college_name'] = (string) $item['college_name'];
                    $student->college_name = (string) $item['college_name'];
                    $dirty = true;
                }

                if (array_key_exists('current_college_name', $item) && $item['current_college_name'] !== null && $student->current_college_name !== $item['current_college_name']) {
                    $oldValues['current_college_name'] = (string) ($student->current_college_name ?? '');
                    $newValues['current_college_name'] = (string) $item['current_college_name'];
                    $student->current_college_name = (string) $item['current_college_name'];
                    $dirty = true;
                }

                if (! $dirty) {
                    continue;
                }

                $student->updated_by = (int) $user->id;
                $student->save();
                $this->audit(
                    $request,
                    'students',
                    'import',
                    $student,
                    $oldValues,
                    $newValues,
                    'Student school/college updated via bulk import'.($lineNo > 0 ? ' (line '.$lineNo.')' : '')
                );
                $updated++;
            }

            $request->session()->forget('student_import_preview_updates');

            return back()
                ->with('status', "Student school/college import applied. Updated: {$updated}, Missing in scope: {$missing}.")
                ->with('student_import_toast', [
                    'type' => $missing > 0 ? 'warning' : 'success',
                    'message' => $missing > 0
                        ? "Import applied with warnings. Updated: {$updated}, Missing in scope: {$missing}."
                        : "Import success: {$updated} students updated.",
                ]);
        }

        $clearEmptyValues = $request->boolean('clear_empty_values');

        $request->validate([
            'student_update_file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $filePath = $request->file('student_update_file')->getRealPath();
        if (! $filePath) {
            return back()->withErrors([
                'student_update_file' => 'Unable to read uploaded file.',
            ])->withInput();
        }

        $sheetRows = IOFactory::load($filePath)->getActiveSheet()->toArray(null, true, true, false);
        if (count($sheetRows) < 2) {
            return back()->withErrors([
                'student_update_file' => 'Excel file must contain header row and at least one data row.',
            ])->withInput();
        }

        $headerRow = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), (array) ($sheetRows[0] ?? []));
        $headerIndex = [];
        foreach ($headerRow as $index => $header) {
            if ($header !== '' && ! array_key_exists($header, $headerIndex)) {
                $headerIndex[$header] = $index;
            }
        }

        $admissionIndex = $this->resolveImportHeaderIndex($headerIndex, [
            'admission_no',
            'admission_number',
            'admission',
            'admission_id',
            'admissionno',
            'admissionnumber',
        ]);

        $rollIndex = $this->resolveImportHeaderIndex($headerIndex, [
            'roll_no',
            'roll',
            'roll_number',
            'rollno',
            'rollnumber',
        ]);

        $previousCollegeIndex = $this->resolveImportHeaderIndex($headerIndex, [
            'previous_school_college_name',
            'previous_school_college',
            'previous_school_name',
            'previous_college_name',
            'previous_school',
            'previous_college',
            'prev_school_college_name',
            'prev_school_name',
            'prev_college_name',
            'previousschoolcollegename',
            'previousschoolname',
            'previouscollegename',
            'college_name',
            'school_college_name',
        ]);

        $currentCollegeIndex = $this->resolveImportHeaderIndex($headerIndex, [
            'current_school_college_name',
            'current_school_college',
            'current_school_name',
            'current_college_name',
            'current_school',
            'current_college',
            'curr_school_college_name',
            'curr_school_name',
            'curr_college_name',
            'currentschoolcollegename',
            'currentschoolname',
            'currentcollegename',
        ]);

        if ($admissionIndex === null && $rollIndex === null) {
            return back()->withErrors([
                'student_update_file' => 'Required columns missing. Please include admission_no or roll_no.',
            ])->withInput();
        }

        if ($previousCollegeIndex === null && $currentCollegeIndex === null) {
            return back()->withErrors([
                'student_update_file' => 'Required columns missing. Please include previous_school_college_name or current_school_college_name.',
            ])->withInput();
        }

        $studentScope = $this->scopedQuery('students')
            ->select(['id', 'admission_no', 'roll_no', 'college_name', 'current_college_name']);

        $previewUpdates = [];
        $previewRows = [];
        $toUpdate = 0;
        $unchanged = 0;
        $notFound = 0;
        $ambiguous = 0;
        $skipped = 0;
        $failedRows = [];

        foreach (array_slice($sheetRows, 1) as $rowNumber => $row) {
            $lineNo = $rowNumber + 2;
            $admissionNo = $admissionIndex !== null ? trim((string) ($row[$admissionIndex] ?? '')) : '';
            $rollNo = $rollIndex !== null ? trim((string) ($row[$rollIndex] ?? '')) : '';
            $previousSchool = $previousCollegeIndex !== null ? trim((string) ($row[$previousCollegeIndex] ?? '')) : '';
            $currentSchool = $currentCollegeIndex !== null ? trim((string) ($row[$currentCollegeIndex] ?? '')) : '';

            if ($admissionNo === '' && $rollNo === '') {
                $skipped++;
                $failedRows[] = [
                    'line' => $lineNo,
                    'admission_no' => '',
                    'roll_no' => '',
                    'previous_school_college_name' => $previousSchool,
                    'current_school_college_name' => $currentSchool,
                    'reason' => 'Missing admission_no/roll_no',
                ];

                continue;
            }

            $newPrevious = null;
            if ($previousCollegeIndex !== null) {
                $newPrevious = $previousSchool !== '' ? $previousSchool : ($clearEmptyValues ? '' : null);
            }

            $newCurrent = null;
            if ($currentCollegeIndex !== null) {
                $newCurrent = $currentSchool !== '' ? $currentSchool : ($clearEmptyValues ? '' : null);
            }

            if ($newPrevious === null && $newCurrent === null) {
                $skipped++;
                $failedRows[] = [
                    'line' => $lineNo,
                    'admission_no' => $admissionNo,
                    'roll_no' => $rollNo,
                    'previous_school_college_name' => '',
                    'current_school_college_name' => '',
                    'reason' => $clearEmptyValues
                        ? 'No importable school/college columns found in this row'
                        : 'No previous/current school value provided',
                ];

                continue;
            }

            $matches = (clone $studentScope)
                ->when($admissionNo !== '', fn ($query) => $query->where('admission_no', $admissionNo))
                ->when($rollNo !== '', fn ($query) => $query->where('roll_no', $rollNo))
                ->limit(2)
                ->get();

            if ($matches->isEmpty()) {
                $notFound++;
                $failedRows[] = [
                    'line' => $lineNo,
                    'admission_no' => $admissionNo,
                    'roll_no' => $rollNo,
                    'previous_school_college_name' => $previousSchool,
                    'current_school_college_name' => $currentSchool,
                    'reason' => 'Student not found in scope',
                ];

                continue;
            }

            if ($matches->count() > 1) {
                $ambiguous++;
                $failedRows[] = [
                    'line' => $lineNo,
                    'admission_no' => $admissionNo,
                    'roll_no' => $rollNo,
                    'previous_school_college_name' => $previousSchool,
                    'current_school_college_name' => $currentSchool,
                    'reason' => 'Multiple students matched. Provide admission_no for exact match',
                ];

                continue;
            }

            /** @var Student $student */
            $student = $matches->first();
            $dirty = false;

            if ($newPrevious !== null && $student->college_name !== $newPrevious) {
                $dirty = true;
            }

            if ($newCurrent !== null && $student->current_college_name !== $newCurrent) {
                $dirty = true;
            }

            if (! $dirty) {
                $unchanged++;

                continue;
            }

            $previewUpdates[] = [
                'student_id' => (int) $student->id,
                'line' => $lineNo,
                'admission_no' => (string) ($student->admission_no ?? ''),
                'roll_no' => (string) ($student->roll_no ?? ''),
                'college_name' => $newPrevious,
                'current_college_name' => $newCurrent,
            ];

            if (count($previewRows) < 30) {
                $previewRows[] = [
                    'line' => $lineNo,
                    'admission_no' => (string) ($student->admission_no ?? $admissionNo),
                    'roll_no' => (string) ($student->roll_no ?? $rollNo),
                    'old_previous_school' => (string) ($student->college_name ?? ''),
                    'new_previous_school' => (string) ($newPrevious ?? $student->college_name ?? ''),
                    'old_current_school' => (string) ($student->current_college_name ?? ''),
                    'new_current_school' => (string) ($newCurrent ?? $student->current_college_name ?? ''),
                ];
            }

            $toUpdate++;
        }

        $message = "Preview ready. To Update: {$toUpdate}, Unchanged: {$unchanged}, Skipped: {$skipped}, Not found: {$notFound}, Ambiguous: {$ambiguous}.";

        if ($failedRows !== []) {
            $request->session()->put('student_import_failed_rows', $failedRows);
        } else {
            $request->session()->forget('student_import_failed_rows');
        }

        if ($previewUpdates !== []) {
            $request->session()->put('student_import_preview_updates', $previewUpdates);
        } else {
            $request->session()->forget('student_import_preview_updates');
        }

        $toastType = ($failedRows === [] && $toUpdate > 0) ? 'success' : 'warning';
        $toastMessage = $toUpdate > 0
            ? "Preview complete: {$toUpdate} students will be updated after confirmation."
            : 'No updatable rows found in uploaded file.';

        return back()
            ->with('status', $message)
            ->with('student_import_preview', [
                'to_update' => $toUpdate,
                'unchanged' => $unchanged,
                'skipped' => $skipped,
                'not_found' => $notFound,
                'ambiguous' => $ambiguous,
                'clear_empty_values' => $clearEmptyValues,
                'rows' => $previewRows,
            ])
            ->with('student_import_toast', [
                'type' => $toastType,
                'message' => $toastMessage,
            ]);
    }

    public function downloadStudentCollegeImportErrors(Request $request): StreamedResponse|RedirectResponse
    {
        $failedRows = $request->session()->get('student_import_failed_rows', []);
        if (! is_array($failedRows) || $failedRows === []) {
            return back()->with('status', 'No failed import rows available to download.');
        }

        $filename = 'student-college-import-failed-rows.csv';

        return response()->streamDownload(function () use ($failedRows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['line', 'admission_no', 'roll_no', 'previous_school_college_name', 'current_school_college_name', 'reason']);

            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    (string) ($row['line'] ?? ''),
                    (string) ($row['admission_no'] ?? ''),
                    (string) ($row['roll_no'] ?? ''),
                    (string) ($row['previous_school_college_name'] ?? ''),
                    (string) ($row['current_school_college_name'] ?? ''),
                    (string) ($row['reason'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadAttendanceImportErrors(Request $request): StreamedResponse|RedirectResponse
    {
        $failedRows = $request->session()->get('attendance_import_failed_rows', []);
        if (! is_array($failedRows) || $failedRows === []) {
            return back()->with('status', 'No failed import rows available to download.');
        }

        $filename = 'attendance-import-failed-rows.csv';

        return response()->streamDownload(function () use ($failedRows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['class', 'section', 'roll_no', 'in_time', 'out_time', 'date', 'reason']);

            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    (string) ($row['class'] ?? ''),
                    (string) ($row['section'] ?? ''),
                    (string) ($row['roll_no'] ?? ''),
                    (string) ($row['in_time'] ?? ''),
                    (string) ($row['out_time'] ?? ''),
                    (string) ($row['date'] ?? ''),
                    (string) ($row['reason'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadAttendanceImportTemplate(): StreamedResponse
    {
        $filename = 'attendance-import-template.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['class', 'section', 'roll_no', 'in_time', 'out_time', 'date']);
            fputcsv($handle, ['Class X', 'Section A', '10A001', '08:45', '14:30', '26/03/2026']);
            fputcsv($handle, ['Class X', 'Section A', '10A002', '08:52', '14:25', '26/03/2026']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadStudentCollegeImportTemplate(): StreamedResponse
    {
        $filename = 'student-college-import-template.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['admission_no', 'roll_no', 'previous_school_college_name', 'current_school_college_name']);
            fputcsv($handle, ['ADM-CLASSX1001', '1001', 'XYZ Public School', 'Meerah Junior College']);
            fputcsv($handle, ['ADM-CLASSX1002', '1002', 'St. Thomas Academy', 'Meerah Senior School']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadStudentCollegeEditableExport(Request $request): StreamedResponse
    {
        $students = $this->scopedQuery('students')
            ->select(['admission_no', 'roll_no', 'college_name', 'current_college_name'])
            ->orderBy('admission_no')
            ->get();

        $filename = 'students-college-editable-export.csv';

        return response()->streamDownload(function () use ($students): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['admission_no', 'roll_no', 'previous_school_college_name', 'current_school_college_name']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    (string) ($student->admission_no ?? ''),
                    (string) ($student->roll_no ?? ''),
                    (string) ($student->college_name ?? ''),
                    (string) ($student->current_college_name ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function masterCalendarSections(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $classId = (int) $request->integer('class_id', 0);

        $sections = Section::query()
            ->select(['id', 'name', 'academic_class_id'])
            ->when($classId > 0, fn ($query) => $query->where('academic_class_id', $classId))
            ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $this->teacherSectionIds($user) ?: [0]))
            ->orderBy('name')
            ->get();

        return response()->json([
            'sections' => $sections->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'academic_class_id' => $section->academic_class_id,
            ])->values()->all(),
        ]);
    }

    public function studentCalendar(Request $request, int $id): View
    {
        /** @var Student $student */
        $student = $this->scopedQuery('students')->findOrFail($id);

        $selectedYear = max(2020, min(2100, (int) $request->integer('year', now()->year)));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', now()->month)));

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $today = now()->startOfDay();

        $joinDate = $this->studentActiveFrom($student, $monthStart);

        $attendances = Attendance::query()
            ->where('attendance_for', 'student')
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

        $specialDates = $this->buildSpecialDatesMap($monthStart, $monthEnd);

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

        $leaveByDate = [];
        foreach ($approvedLeaves as $leave) {
            $start = Carbon::parse($leave->start_date)->startOfDay();
            $end = Carbon::parse($leave->end_date)->startOfDay();
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $dateKey = $cursor->toDateString();
                $leaveByDate[$dateKey] = [
                    'type' => $leave->leave_type,
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
                    if (isset($specialDates[$dateKey])) {
                        $specialType = ($specialDates[$dateKey]['type'] ?? 'holiday') === 'weekoff' ? 'weekoff' : 'holiday';
                        $cellType = $specialType;
                        $cellLabel = $specialDates[$dateKey]['title'];
                        $cellMeta = $specialType === 'weekoff' ? 'WO' : strtoupper(substr((string) ($specialDates[$dateKey]['type'] ?? 'holiday'), 0, 3));
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

                        $cellMeta = strtoupper(substr($attendanceStatus, 0, 1));
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

        return view('students.calendar', [
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

    public function dayWiseCustomizationIndex(): View
    {
        $holidays = Holiday::query()
            ->orderBy('start_date', 'desc')
            ->get();

        $classes = AcademicClass::query()
            ->orderBy('name')
            ->get();

        return view('day-wise-customization', [
            'holidays' => $holidays,
            'classes' => $classes,
        ]);
    }

    public function saveDayWiseEntries(Request $request): RedirectResponse
    {
        $request->validate([
            'entries' => 'required|json',
        ]);

        $entries = json_decode($request->string('entries'), true) ?? [];

        if (empty($entries)) {
            return redirect()->back()->withErrors(['entries' => 'At least one entry is required']);
        }

        $created = 0;
        $errors = [];

        foreach ($entries as $index => $entry) {
            try {
                $this->validateDayWiseEntry($entry);

                $fromDate = Carbon::parse($entry['from_date'])->startOfDay();
                $toDate = $entry['to_date'] ? Carbon::parse($entry['to_date'])->startOfDay() : $fromDate->copy();

                if ($toDate->lt($fromDate)) {
                    $errors[] = 'Entry '.($index + 1).': To Date must be after From Date';

                    continue;
                }

                $classId = $entry['class_id'] ? (int) $entry['class_id'] : null;

                Holiday::updateOrCreate(
                    [
                        'entry_type' => $entry['type'],
                        'start_date' => $fromDate,
                        'end_date' => $toDate,
                        'class_id' => $classId,
                    ],
                    [
                        'title' => $entry['title'],
                        'description' => $entry['notes'] ?? '',
                        'created_by' => Auth::id(),
                    ]
                );

                $created++;
            } catch (\Exception $e) {
                $errors[] = 'Entry '.($index + 1).': '.$e->getMessage();
            }
        }

        if (! empty($errors)) {
            return redirect()->back()
                ->withErrors(['entries' => implode(' | ', $errors)])
                ->withInput();
        }

        return redirect()->route('day-wise-customization.index')
            ->with('success', "Successfully saved {$created} day-wise customization entries!");
    }

    public function deleteDayWiseEntry(int $id): RedirectResponse
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Entry deleted successfully!');
    }

    private function validateDayWiseEntry(array $entry): void
    {
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'nullable|date',
            'type' => ['required', Rule::in(['holiday', 'weekoff', 'event', 'exam', 'sports', 'fest'])],
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'class_id' => 'nullable|integer|exists:academic_classes,id',
        ];

        $validator = validator()->make($entry, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function studentActiveFrom(Student $student, Carbon $fallbackDate): Carbon
    {
        return $student->admission_date?->copy()->startOfDay()
            ?? $student->created_at?->copy()->startOfDay()
            ?? $fallbackDate->copy()->startOfDay();
    }

    private function buildSpecialDatesMap(Carbon $monthStart, Carbon $monthEnd): array
    {
        $specialDates = [];

        $holidays = Holiday::query()
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get();

        foreach ($holidays as $holiday) {
            $start = Carbon::parse($holiday->start_date)->startOfDay();
            $end = Carbon::parse($holiday->end_date)->startOfDay();

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                if ($cursor->lt($monthStart) || $cursor->gt($monthEnd)) {
                    continue;
                }

                $specialDates[$cursor->toDateString()] = [
                    'title' => (string) ($holiday->title ?: ucfirst((string) ($holiday->holiday_type ?: 'Holiday'))),
                    'type' => (string) ($holiday->holiday_type ?: 'holiday'),
                ];
            }
        }

        return $specialDates;
    }

    private function persistModule(string $module, Request $request, array $validated, ?Model $record = null): Model
    {
        $modelClass = SchoolModuleRegistry::get($module)['model'];
        $record ??= new $modelClass;

        $oldValues = $record->exists ? $record->toArray() : [];
        $previousPaidAmount = $record->exists && $module === 'fees' ? (float) $record->paid_amount : 0;
        $payload = $this->preparePayload($module, $request, $validated, $record);

        if ($module === 'admission-leads') {
            $stage = (string) ($payload['stage'] ?? $record->stage ?? 'new');
            $this->enforceAdmissionLeadWipLimit($stage, $record->exists ? (int) $record->getKey() : null);
        }

        $payload['updated_by'] = $request->user()->id;
        if (! $record->exists) {
            $payload['created_by'] = $request->user()->id;
        }

        $record->fill(Arr::except($payload, ['subject_ids', 'new_subject_names', 'installment_amount', 'installment_date']));
        $record->save();

        if ($module === 'classes') {
            $selectedSubjectIds = array_map('intval', $payload['subject_ids'] ?? []);
            $createdSubjectIds = $this->createSubjectsFromClassInput($record, (string) ($payload['new_subject_names'] ?? ''), (int) $request->user()->id);
            $record->subjects()->sync(array_values(array_unique(array_merge($selectedSubjectIds, $createdSubjectIds))));
        }

        if ($module === 'staff') {
            $this->syncStaffUser($record, $payload);
        }

        if ($module === 'students') {
            $this->syncStudentUser($record);
        }

        if ($module === 'fees') {
            $this->syncFeePayment($record, $payload, $previousPaidAmount, $request->user()->id);
        }

        if ($module === 'exams') {
            $this->syncExamScheduleNotification($record, $request->user()->id);
        }

        if ($module === 'results' && $record->wasRecentlyCreated) {
            $this->syncResultPublishedNotification($record, $request->user()->id);
        }

        $this->audit(
            $request,
            $module,
            $record->wasRecentlyCreated ? 'create' : 'update',
            $record,
            $oldValues,
            $record->fresh()->toArray(),
            SchoolModuleRegistry::get($module)['singular'].' saved'
        );

        return $record->fresh($this->eagerLoad($module));
    }

    private function enforceAdmissionLeadWipLimit(string $targetStage, ?int $excludeLeadId = null): void
    {
        $limit = (int) ((LicenseConfig::current()?->admissionLeadWipLimits()[$targetStage] ?? 0));
        if ($limit <= 0) {
            return;
        }

        $query = AdmissionLead::query()->where('stage', $targetStage);
        if ($excludeLeadId) {
            $query->whereKeyNot($excludeLeadId);
        }

        $currentCount = $query->count();
        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'stage' => 'WIP limit reached for '.$targetStage.' stage. Limit: '.$limit.', current: '.$currentCount.'.',
            ]);
        }
    }

    private function syncExamScheduleNotification(Model $record, int $userId): void
    {
        if (! $record instanceof Exam || ! $record->start_date) {
            return;
        }

        $className = AcademicClass::query()->whereKey($record->academic_class_id)->value('name') ?? 'Class';
        $examType = trim((string) ($record->exam_type ?? 'Exam'));
        $examDate = $record->start_date?->format('d M Y') ?? now()->format('d M Y');

        SchoolNotification::query()->updateOrCreate(
            [
                'source_type' => 'exam_schedule',
                'source_id' => $record->id,
            ],
            [
                'title' => 'Exam Reminder: '.$record->name,
                'message' => $record->name.' ('.$examType.') is scheduled on '.$examDate.' for '.$className.' (all sections).',
                'audience' => 'students',
                'academic_class_id' => $record->academic_class_id,
                'section_id' => null,
                'publish_date' => $record->start_date,
                'status' => 'published',
                'updated_by' => $userId,
                'created_by' => $userId,
            ]
        );
    }

    private function syncResultPublishedNotification(Model $record, int $userId): void
    {
        if (! $record instanceof Result) {
            return;
        }

        $exam = Exam::query()->with('academicClass')->find($record->exam_id);
        if (! $exam) {
            return;
        }

        $alreadyExists = SchoolNotification::query()
            ->where('source_type', 'result_published')
            ->where('source_id', $exam->id)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $className = $exam->academicClass?->name ?? 'all classes';

        SchoolNotification::query()->create([
            'title' => 'Result Published: '.$exam->name,
            'message' => 'Results for '.$exam->name.' have been published. Students and parents can now check performance.',
            'audience' => 'all',
            'academic_class_id' => null,
            'section_id' => null,
            'source_type' => 'result_published',
            'source_id' => $exam->id,
            'publish_date' => now()->toDateString(),
            'status' => 'published',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function preparePayload(string $module, Request $request, array $validated, Model $record): array
    {
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        // Auto-generate roll_no / admission_no for students BEFORE file upload
        // so photo and documents can use roll_no in their filenames.
        if ($module === 'students') {
            $classId = (int) ($validated['academic_class_id'] ?? $record->academic_class_id ?? 0);

            if (! $record->exists && $classId > 0) {
                $rollNo = $this->nextClassRollNo($classId);
                $validated['roll_no'] = (string) $rollNo;
                $validated['admission_no'] = $this->buildAdmissionNo($classId, $rollNo);
            }

            if ($record->exists && $classId > 0) {
                if (empty($validated['roll_no'])) {
                    $rollNo = $this->nextClassRollNo($classId, (int) $record->id);
                    $validated['roll_no'] = (string) $rollNo;
                }
                if (empty($validated['admission_no'])) {
                    $validated['admission_no'] = $this->buildAdmissionNo($classId, (int) $validated['roll_no']);
                }
            }
        }

        foreach (SchoolModuleRegistry::get($module)['file_fields'] ?? [] as $field) {
            if ($request->hasFile($field)) {
                if ($module === 'students') {
                    $rollNo = $validated['roll_no'] ?? $record->roll_no ?? null;

                    if ($field === 'photo' && $rollNo) {
                        $ext = $request->file($field)->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'photo-'.$rollNo.'.'.$ext;
                        $validated[$field] = $request->file($field)->storeAs($module, $filename, 'public');
                    } elseif ($field === 'aadhar_file' && $rollNo) {
                        $ext = $request->file($field)->getClientOriginalExtension() ?: 'pdf';
                        $filename = 'aadhar-'.$rollNo.'.'.$ext;
                        $validated[$field] = $request->file($field)->storeAs($module, $filename, 'public');
                    } elseif ($field === 'documents' && $rollNo) {
                        $files = is_array($request->file($field)) ? $request->file($field) : [$request->file($field)];
                        $validated[$field] = collect($files)->values()->map(function ($file, $index) use ($rollNo, $module) {
                            $ext = $file->getClientOriginalExtension() ?: 'pdf';
                            $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $origName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $origName);
                            $filename = 'doc-'.$rollNo.'-'.($index + 1).'-'.$origName.'.'.$ext;

                            return $file->storeAs($module, $filename, 'public');
                        })->all();
                    } else {
                        $validated[$field] = is_array($request->file($field))
                            ? collect($request->file($field))->map(fn ($file) => $file->store($module, 'public'))->all()
                            : $request->file($field)->store($module, 'public');
                    }
                } else {
                    $validated[$field] = is_array($request->file($field))
                        ? collect($request->file($field))->map(fn ($file) => $file->store($module, 'public'))->all()
                        : $request->file($field)->store($module, 'public');
                }
            } elseif ($record->exists) {
                $validated[$field] = $record->{$field};
            }
        }

        $validated['subject_ids'] = array_map('intval', $request->input('subject_ids', []));
        $validated['new_subject_names'] = trim((string) $request->input('new_subject_names', ''));
        $validated['permissions'] = $request->input('permissions', $validated['permissions'] ?? []);

        if ($module === 'leaves') {
            $license = LicenseConfig::current();
            $requiresApproval = $license?->approvalRequired('leave_requests') ?? true;

            $validated['student_id'] = ($validated['requester_type'] ?? null) === 'student' ? $validated['student_id'] : null;
            $validated['staff_id'] = ($validated['requester_type'] ?? null) === 'staff' ? $validated['staff_id'] : null;

            if ($requiresApproval) {
                $currentStatus = (string) ($record->status ?? 'pending');
                $requestedStatus = (string) ($validated['status'] ?? $currentStatus);

                if ($record->exists && $requestedStatus !== $currentStatus && in_array($requestedStatus, ['approved', 'rejected'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Leave approval must be completed from the approval action. Direct approve or reject from the form is disabled in Master Control.',
                    ]);
                }

                $validated['status'] = $record->exists ? $currentStatus : 'pending';
                $validated['approved_by'] = $validated['status'] === 'approved' ? ($record->approved_by ?? null) : null;
            } else {
                $validated['status'] = $record->exists
                    ? (string) ($validated['status'] ?? $record->status ?? 'approved')
                    : 'approved';
                $validated['approved_by'] = ($validated['status'] ?? null) === 'approved' ? $request->user()->id : null;
            }
        }

        if (in_array($module, ['attendance', 'results'], true) && $request->user()->isTeacher()) {
            $validated['staff_id'] = $request->user()->staff_id;
        }

        if ($module === 'attendance') {
            $attendanceFor = (string) ($validated['attendance_for'] ?? 'student');

            if ($attendanceFor === 'staff') {
                $validated['student_id'] = null;
                $validated['academic_class_id'] = null;
                $validated['section_id'] = null;
            } else {
                $validated['staff_attendance_id'] = null;
            }

            if (($validated['attendance_method'] ?? 'manual') !== 'manual' && empty($validated['captured_at'])) {
                $validated['captured_at'] = now();
            }

            $capturePayload = $validated['capture_payload'] ?? null;
            if (is_string($capturePayload) && trim($capturePayload) !== '') {
                $validated['capture_payload'] = json_decode($capturePayload, true);
            }

            $validated['marked_by_staff_id'] = $validated['marked_by_staff_id']
                ?? ($request->user()->isTeacher() ? $request->user()->staff_id : null);

            // Keep existing column in sync for legacy reports.
            $validated['staff_id'] = $validated['marked_by_staff_id'];
        }

        if ($module === 'fees') {
            $existingPaidAmount = (float) ($record->paid_amount ?? 0);
            $directPaidAmount = (float) ($validated['paid_amount'] ?? $existingPaidAmount);
            $installmentAmount = (float) $request->input('installment_amount', 0);
            $amount = (float) ($validated['amount'] ?? $record->amount ?? 0);
            $basePaidAmount = $record->exists ? $existingPaidAmount : 0.0;

            if ($installmentAmount < 0) {
                throw ValidationException::withMessages([
                    'installment_amount' => 'Installment amount cannot be negative.',
                ]);
            }

            if ($installmentAmount > 0) {
                $remainingBeforeInstallment = max(0, $amount - $basePaidAmount);
                if ($remainingBeforeInstallment <= 0) {
                    throw ValidationException::withMessages([
                        'installment_amount' => 'This fee is already fully paid.',
                    ]);
                }

                if ($installmentAmount > $remainingBeforeInstallment) {
                    throw ValidationException::withMessages([
                        'installment_amount' => 'Installment amount cannot exceed the remaining due amount.',
                    ]);
                }
            }

            $validated['paid_amount'] = $installmentAmount > 0
                ? $basePaidAmount + $installmentAmount
                : $directPaidAmount;

            $validated['status'] = $validated['paid_amount'] >= $amount
                ? 'paid'
                : ($validated['paid_amount'] > 0 ? 'partial' : $validated['status']);
        }

        if ($module === 'exams') {
            $allowedSets = ['A', 'B', 'C', 'D', 'E'];
            $inputSets = $request->input('question_sets', $validated['question_sets'] ?? $allowedSets);
            $normalizedSets = collect(is_array($inputSets) ? $inputSets : [])
                ->map(fn ($set) => strtoupper(trim((string) $set)))
                ->filter(fn ($set) => in_array($set, $allowedSets, true))
                ->values()
                ->all();

            $validated['question_sets'] = $normalizedSets !== [] ? $normalizedSets : $allowedSets;
        }

        return $validated;
    }

    private function nextClassRollNo(int $classId, ?int $ignoreStudentId = null): int
    {
        $maxRollNo = Student::query()
            ->where('academic_class_id', $classId)
            ->when($ignoreStudentId, fn ($query) => $query->whereKeyNot($ignoreStudentId))
            ->pluck('roll_no')
            ->map(fn ($roll) => (int) preg_replace('/\D+/', '', (string) $roll))
            ->filter(fn (int $roll) => $roll > 0)
            ->max();

        if (! $maxRollNo || $maxRollNo < 1000) {
            return 1001;
        }

        return $maxRollNo + 1;
    }

    private function buildAdmissionNo(int $classId, int $rollNo): string
    {
        $class = AcademicClass::query()->find($classId);
        $classCode = strtoupper((string) ($class?->code ?? $class?->name ?? 'GEN'));
        $classCode = preg_replace('/[^A-Z0-9]/', '', $classCode) ?: 'GEN';

        return 'ADM-'.$classCode.$rollNo;
    }

    private function createSubjectsFromClassInput(AcademicClass $class, string $newSubjectNames, int $userId): array
    {
        $names = collect(preg_split('/[\r\n,]+/', $newSubjectNames) ?: [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        return $names->map(function (string $name) use ($class, $userId) {
            $existing = Subject::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->where(function ($query) use ($class) {
                    $query->where('academic_class_id', $class->id)
                        ->orWhereNull('academic_class_id');
                })
                ->first();

            if ($existing) {
                return (int) $existing->id;
            }

            $baseCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($name, 0, 4)) ?: 'SUB');
            $code = $baseCode;
            $counter = 1;

            while (Subject::query()->where('code', $code)->exists()) {
                $counter++;
                $code = $baseCode.$counter;
            }

            $subject = Subject::query()->create([
                'academic_class_id' => $class->id,
                'name' => $name,
                'code' => $code,
                'type' => 'theory',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return (int) $subject->id;
        })->all();
    }

    private function syncStaffUser(Staff $staff, array $payload): void
    {
        if (! in_array($staff->role_type, ['admin', 'hr', 'teacher'], true)) {
            if ($staff->linkedUser) {
                $staff->linkedUser->update(['active' => false]);
            }

            return;
        }

        $permissions = $payload['permissions'] ?? [];
        if ($staff->role_type === 'admin' && $permissions === []) {
            $permissions = SchoolModuleRegistry::defaultPermissionsForRole('admin');
        }

        if ($staff->role_type === 'hr' && $permissions === []) {
            $permissions = SchoolModuleRegistry::defaultPermissionsForRole('hr');
        }

        if ($staff->role_type === 'teacher' && $permissions === []) {
            $permissions = SchoolModuleRegistry::defaultPermissionsForRole('teacher');
        }

        $user = User::withTrashed()->firstOrNew(['staff_id' => $staff->id]);
        if ($user->trashed()) {
            $user->restore();
        }

        if (! $user->exists || $user->role !== $staff->role_type) {
            $this->enforceLicensedStaffRoleLimit($staff->role_type, $user->exists ? (int) $user->id : null);
        }

        $user->fill([
            'name' => $staff->full_name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'photo' => $staff->photo,
            'role' => $staff->role_type,
            'permissions' => $permissions,
            'active' => $staff->status === 'active',
            'must_change_password' => $user->exists ? $user->must_change_password : true,
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('ChangeMe@123');
        }

        $user->save();
    }

    private function enforceLicensedStaffRoleLimit(string $role, ?int $ignoreUserId = null): void
    {
        $license = LicenseConfig::current();

        if (! $license) {
            return;
        }

        $limit = $license->limitForRole($role);

        if (! $limit) {
            return;
        }

        $currentCount = User::query()
            ->where('role', $role)
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->count();

        if ($currentCount >= $limit) {
            $label = ucfirst(str_replace('_', ' ', $role));

            throw ValidationException::withMessages([
                'role_type' => "{$label} user limit ({$limit}) reached for the {$license->planLabel()} plan. Upgrade the plan or increase the role limit in Master Control.",
            ]);
        }
    }

    private function syncStudentUser(Student $student): void
    {
        $user = User::withTrashed()->firstOrNew(['student_id' => $student->id]);
        if ($user->trashed()) {
            $user->restore();
        }

        $phone = $student->phone ?: $student->guardian_phone;
        $email = $student->email;

        if (! $email || User::query()->where('email', $email)->whereKeyNot($user->id ?? 0)->exists()) {
            $email = 'student'.$student->id.'@students.schoolsphere.local';
        }

        $user->fill([
            'name' => $student->full_name,
            'email' => $email,
            'phone' => $phone,
            'photo' => $student->photo,
            'role' => 'student',
            'permissions' => [],
            'student_id' => $student->id,
            'active' => $student->status === 'active',
            'must_change_password' => false,
        ]);

        if (! $user->exists || empty($user->getRawOriginal('password'))) {
            $user->password = Hash::make((string) $student->roll_no);
        }

        $user->save();
    }

    private function syncFeePayment(Fee $fee, array $payload, float $previousPaidAmount, int $userId): void
    {
        $currentPaidAmount = (float) $fee->paid_amount;
        $difference = $currentPaidAmount - $previousPaidAmount;

        if ($difference > 0) {
            $payment = Payment::create([
                'fee_id' => $fee->id,
                'student_id' => $fee->student_id,
                'amount' => $difference,
                'payment_date' => ! empty($payload['installment_date']) ? $payload['installment_date'] : now()->toDateString(),
                'payment_mode' => $payload['payment_mode'] ?? null,
                'receipt_no' => null,
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $payment->receipt_no = $this->generateReceiptNoFromPayment($payment);
            $payment->updated_by = $userId;
            $payment->save();

            if (empty($fee->receipt_no)) {
                $fee->receipt_no = $payment->receipt_no;
                $fee->updated_by = $userId;
                $fee->save();
            }
        }
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

        return $schoolCode.'-RCPT-'.$payment->payment_date?->format('Ym').'-'.$serial;
    }

    private function resolveLogoPath(bool $supportsImages): ?string
    {
        if (! $supportsImages) {
            return null;
        }

        // SVG is intentionally skipped because DomPDF cannot render SVG reliably.
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

    private function records(string $module, ?Request $request = null)
    {
        $perPage = (int) ($request?->input('per_page') ?? 25);
        $query = $this->filteredModuleQuery($module, $request, true);

        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'links' => $paginated->links(),
            ],
        ];
    }

    private function filteredModuleQuery(string $module, ?Request $request = null, bool $applySorting = true)
    {
        $searchField = $request?->input('search_field');
        $searchTerm = $request?->input('search');
        $sortBy = $request?->input('sort_by');
        $sortOrder = $request?->input('sort_order') === 'desc' ? 'desc' : 'asc';
        $classId = (int) ($request?->input('class_id') ?? 0);
        $feeMonth = (int) ($request?->input('fee_month') ?? 0);
        $collegeName = trim((string) ($request?->input('college_name') ?? ''));
        $currentCollegeName = trim((string) ($request?->input('current_college_name') ?? ''));

        $query = $this->scopedQuery($module);

        if ($searchField && $searchTerm) {
            $query->where($searchField, 'like', '%'.$searchTerm.'%');
        }

        if ($module === 'fees') {
            if ($classId > 0) {
                $query->where('academic_class_id', $classId);
            }

            if ($feeMonth >= 1 && $feeMonth <= 12) {
                $query->whereMonth('due_date', $feeMonth);
            }
        }

        if ($module === 'students' && $collegeName !== '') {
            $query->where('college_name', 'like', '%'.$collegeName.'%');
        }

        if ($module === 'students' && $currentCollegeName !== '') {
            $query->where('current_college_name', 'like', '%'.$currentCollegeName.'%');
        }

        if ($applySorting) {
            if ($sortBy) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }
        }

        return $query;
    }

    private function normalizeRecordId($id): int
    {
        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        abort(404);
    }

    private function ensureStudentLimitNotExceeded(): void
    {
        $license = LicenseConfig::current();
        $limit = (int) ($license?->resolvedStudentLimit() ?? 0);

        if ($limit <= 0) {
            return;
        }

        $currentCount = Student::query()->count();

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'student_limit' => "Student limit ({$limit}) reached. Upgrade license or increase limit to add more students.",
            ]);
        }
    }

    private function scopedQuery(string $module)
    {
        $modelClass = SchoolModuleRegistry::get($module)['model'];
        $query = $modelClass::query()->with($this->eagerLoad($module));
        /** @var User $user */
        $user = Auth::user();

        if ($user->isTeacher()) {
            $studentIds = $this->teacherStudentIds($user);
            $sectionIds = $this->teacherSectionIds($user);

            switch ($module) {
                case 'students':
                    $query->whereIn('id', $studentIds ?: [0]);
                    break;
                case 'attendance':
                    $query->where(function ($builder) use ($studentIds, $user) {
                        $builder->whereIn('student_id', $studentIds ?: [0])
                            ->orWhere('marked_by_staff_id', $user->staff_id)
                            ->orWhere('staff_attendance_id', $user->staff_id);
                    });
                    break;
                case 'results':
                case 'fees':
                    $query->whereIn('student_id', $studentIds ?: [0]);
                    break;
                case 'timetable':
                    $query->where(function ($builder) use ($user, $sectionIds) {
                        $builder->where('staff_id', $user->staff_id)
                            ->orWhereIn('section_id', $sectionIds ?: [0]);
                    });
                    break;
                case 'leaves':
                    $query->where('staff_id', $user->staff_id);
                    break;
                case 'staff':
                    $query->whereKey($user->staff_id);
                    break;
            }
        }

        return $query;
    }

    private function lookups(string $module): array
    {
        /** @var User $user */
        $user = Auth::user();
        $studentIds = $this->teacherStudentIds($user);
        $sectionIds = $this->teacherSectionIds($user);
        $classIds = Section::query()->whereIn('id', $sectionIds)->pluck('academic_class_id')->unique()->values()->all();

        return [
            'academic_classes' => AcademicClass::query()
                ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $classIds ?: [0]))
                ->pluck('name', 'id')
                ->all(),
            'sections' => Section::query()
                ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $sectionIds ?: [0]))
                ->get()
                ->mapWithKeys(fn (Section $section) => [$section->id => $section->name])
                ->all(),
            'sections_meta' => Section::query()
                ->select(['id', 'name', 'academic_class_id'])
                ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $sectionIds ?: [0]))
                ->orderBy('name')
                ->get()
                ->map(fn (Section $section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'academic_class_id' => $section->academic_class_id,
                ])
                ->values()
                ->all(),
            'subjects' => Subject::query()->pluck('name', 'id')->all(),
            'students' => Student::query()
                ->when($user->isTeacher(), fn ($query) => $query->whereIn('id', $studentIds ?: [0]))
                ->get()
                ->mapWithKeys(fn (Student $student) => [$student->id => $student->full_name])
                ->all(),
            'biometric_devices' => BiometricDevice::query()
                ->where('status', 'active')
                ->pluck('device_name', 'id')
                ->all(),
            'staff' => Staff::query()->get()->mapWithKeys(fn (Staff $staff) => [$staff->id => $staff->full_name])->all(),
            'teachers' => Staff::query()->where('role_type', 'teacher')->get()->mapWithKeys(fn (Staff $staff) => [$staff->id => $staff->full_name])->all(),
            'exams' => Exam::query()->pluck('name', 'id')->all(),
            'permissions' => SchoolModuleRegistry::lookupPermissions(),
            'permission_presets' => [
                'admin' => SchoolModuleRegistry::defaultPermissionsForRole('admin'),
                'hr' => SchoolModuleRegistry::defaultPermissionsForRole('hr'),
                'teacher' => SchoolModuleRegistry::defaultPermissionsForRole('teacher'),
                'staff' => [],
            ],
        ];
    }

    private function exportData(string $module, array $moduleConfig): array
    {
        $headings = collect($moduleConfig['table_columns'])->pluck('label')->all();
        $allRecords = $this->scopedQuery($module)->latest()->get();
        $rows = $allRecords->map(function ($record) use ($moduleConfig) {
            return collect($moduleConfig['table_columns'])->map(function (array $column) use ($record) {
                $value = data_get($record, $column['key']);

                return is_array($value) ? implode(', ', $value) : $value;
            })->all();
        })->all();

        return [$headings, $rows];
    }

    private function renderTable(string $module, array $moduleConfig, ?Request $request = null): string
    {
        $paginated = $this->records($module, $request);

        return view('modules.table', [
            'records' => $paginated['data'],
            'pagination' => $paginated['pagination'],
            'moduleConfig' => $moduleConfig,
            'moduleKey' => $module,
        ])->render();
    }

    private function eagerLoad(string $module): array
    {
        return SchoolModuleRegistry::get($module)['eager_load'] ?? [];
    }

    private function audit(Request $request, string $module, string $action, Model $record, array $oldValues, array $newValues, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $record::class,
            'auditable_id' => $record->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
    }

    private function teacherStudentIds(User $user): array
    {
        if (! $user->isTeacher() || ! $user->staff_id) {
            return [];
        }

        return Student::query()
            ->whereHas('section', fn ($query) => $query->where('class_teacher_id', $user->staff_id))
            ->pluck('id')
            ->all();
    }

    private function teacherSectionIds(User $user): array
    {
        if (! $user->isTeacher() || ! $user->staff_id) {
            return [];
        }

        return Section::query()->where('class_teacher_id', $user->staff_id)->pluck('id')->all();
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

    private function resolveImportClassId($value, $classRecords): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        if (ctype_digit($raw)) {
            $classId = (int) $raw;

            return $classRecords->contains(fn (AcademicClass $classRecord) => (int) $classRecord->id === $classId)
                ? $classId
                : 0;
        }

        $needle = strtolower($raw);
        $matched = $classRecords->first(function (AcademicClass $classRecord) use ($needle) {
            return strtolower((string) $classRecord->name) === $needle
                || strtolower((string) ($classRecord->code ?? '')) === $needle;
        });

        return (int) ($matched->id ?? 0);
    }

    private function resolveImportSectionId($value, int $classId, $sectionRecords): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        if (ctype_digit($raw)) {
            $sectionId = (int) $raw;

            return $sectionRecords->contains(function (Section $sectionRecord) use ($sectionId, $classId) {
                if ((int) $sectionRecord->id !== $sectionId) {
                    return false;
                }

                return $classId <= 0 || (int) $sectionRecord->academic_class_id === $classId;
            }) ? $sectionId : 0;
        }

        $needle = strtolower($raw);
        $matched = $sectionRecords->first(function (Section $sectionRecord) use ($needle, $classId) {
            if ($classId > 0 && (int) $sectionRecord->academic_class_id !== $classId) {
                return false;
            }

            return strtolower((string) $sectionRecord->name) === $needle
                || strtolower((string) ($sectionRecord->code ?? '')) === $needle;
        });

        return (int) ($matched->id ?? 0);
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?: '';

        return trim($header, '_');
    }

    private function resolveImportHeaderIndex(array $headerIndex, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $headerIndex)) {
                return (int) $headerIndex[$alias];
            }
        }

        return null;
    }

    private function ensureStudentCollegeImportAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isHr()),
            403,
            'Only Admin or HR users can preview or apply student school/college bulk imports.'
        );
    }

    private function parseImportedTimeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i');
            } catch (\Throwable $exception) {
                return null;
            }
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('H:i');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function parseImportedDateValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                return null;
            }
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable $exception) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
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

    private function rules(string $module, ?Model $record = null): array
    {
        $recordId = $record?->getKey();

        return match ($module) {
            'students' => [
                'admission_no' => ['nullable', 'string', 'max:50', Rule::unique('students', 'admission_no')->ignore($recordId)],
                'roll_no' => ['nullable', 'string', 'max:50', Rule::unique('students', 'roll_no')->where(fn ($query) => $query
                    ->where('academic_class_id', request('academic_class_id')))->ignore($recordId)],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'gender' => ['required', 'string'],
                'date_of_birth' => ['nullable', 'date'],
                'phone' => ['nullable', 'string', 'max:20'],
                'email' => ['nullable', 'email', Rule::unique('students', 'email')->ignore($recordId)],
                'guardian_name' => ['nullable', 'string', 'max:100'],
                'guardian_phone' => ['nullable', 'string', 'max:20'],
                'college_name' => ['nullable', 'string', 'max:150'],
                'current_college_name' => ['nullable', 'string', 'max:150'],
                'admission_date' => ['nullable', 'date'],
                'blood_group' => ['nullable', 'string', 'max:5'],
                'academic_class_id' => ['required', 'exists:academic_classes,id'],
                'section_id' => ['required', 'exists:sections,id'],
                'address' => ['nullable', 'string'],
                'aadhar_number' => ['nullable', 'string', 'max:50'],
                'status' => ['required', 'string'],
                'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
                'aadhar_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf'],
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            ],
            'admission-leads' => [
                'student_name' => ['required', 'string', 'max:120'],
                'guardian_name' => ['nullable', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:25'],
                'email' => ['nullable', 'email', 'max:150'],
                'academic_class_id' => ['nullable', 'exists:academic_classes,id'],
                'source' => ['required', Rule::in(['walk_in', 'website', 'meta_ads', 'google_ads', 'reference', 'campaign', 'other'])],
                'stage' => ['required', Rule::in(['new', 'contacted', 'counselling_scheduled', 'counselling_done', 'follow_up', 'converted', 'lost'])],
                'score' => ['nullable', 'integer', 'min:0', 'max:100'],
                'assigned_to_staff_id' => ['nullable', 'exists:staff,id'],
                'last_contacted_at' => ['nullable', 'date'],
                'next_follow_up_at' => ['nullable', 'date'],
                'remarks' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'staff' => [
                'employee_id' => ['required', 'string', 'max:50', Rule::unique('staff', 'employee_id')->ignore($recordId)],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', Rule::unique('staff', 'email')->ignore($recordId)],
                'phone' => ['nullable', 'string', 'max:20'],
                'designation' => ['required', 'string', 'max:100'],
                'role_type' => ['required', 'string'],
                'joining_date' => ['nullable', 'date'],
                'qualification' => ['nullable', 'string'],
                'permissions' => ['nullable', 'array'],
                'experience_years' => ['nullable', 'integer'],
                'leave_balance_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'salary' => ['nullable', 'numeric'],
                'address' => ['nullable', 'string'],
                'aadhar_number' => ['nullable', 'string', 'max:50'],
                'pan_number' => ['nullable', 'string', 'max:50'],
                'status' => ['required', 'string'],
                'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
                'aadhar_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf'],
                'pancard_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf'],
                'qualification_files' => ['nullable', 'array'],
                'qualification_files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            ],
            'classes' => [
                'name' => ['required', 'string', 'max:100'],
                'code' => ['required', 'string', 'max:50', Rule::unique('academic_classes', 'code')->ignore($recordId)],
                'capacity' => ['nullable', 'integer'],
                'description' => ['nullable', 'string'],
                'subject_ids' => ['nullable', 'array'],
                'subject_ids.*' => ['exists:subjects,id'],
                'new_subject_names' => ['nullable', 'string', 'max:1000'],
                'status' => ['required', 'string'],
            ],
            'sections' => [
                'academic_class_id' => ['required', 'exists:academic_classes,id'],
                'name' => ['required', 'string', 'max:100'],
                'code' => ['required', 'string', 'max:50', Rule::unique('sections', 'code')->ignore($recordId)],
                'room_no' => ['nullable', 'string', 'max:50'],
                'class_teacher_id' => ['nullable', 'exists:staff,id'],
            ],
            'subjects' => [
                'academic_class_id' => ['nullable', 'exists:academic_classes,id'],
                'name' => ['required', 'string', 'max:100'],
                'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($recordId)],
                'type' => ['required', 'string'],
                'staff_id' => ['nullable', 'exists:staff,id'],
                'max_marks' => ['nullable', 'integer'],
            ],
            'exams' => [
                'academic_class_id' => ['required', 'exists:academic_classes,id'],
                'name' => ['required', 'string', 'max:100'],
                'exam_type' => ['required', 'string'],
                'question_sets' => ['nullable', 'array'],
                'question_sets.*' => ['in:A,B,C,D,E'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
                'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
                'negative_mark_per_wrong' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'total_marks' => ['nullable', 'integer'],
                'status' => ['required', 'string'],
            ],
            'exam-questions' => [
                'exam_id' => ['required', 'exists:exams,id'],
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
                'status' => ['required', 'string'],
            ],
            'study-materials' => [
                'academic_class_id' => ['nullable', 'exists:academic_classes,id'],
                'subject_id' => ['nullable', 'exists:subjects,id'],
                'title' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
                'file_path' => [Rule::requiredIf(! $recordId), 'nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png,webp'],
                'status' => ['required', 'string'],
            ],
            'exam-papers' => [
                'exam_id' => ['required', 'exists:exams,id'],
                'set_code' => ['required', 'in:A,B,C,D,E'],
                'title' => ['required', 'string', 'max:150'],
                'instructions' => ['nullable', 'string'],
                'file_path' => [Rule::requiredIf(! $recordId), 'nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp'],
                'status' => ['required', 'string'],
            ],
            'results' => [
                'student_id' => ['required', 'exists:students,id'],
                'exam_id' => ['required', 'exists:exams,id'],
                'subject_id' => ['required', Rule::unique('results')->where(fn ($query) => $query
                    ->where('student_id', request('student_id'))
                    ->where('exam_id', request('exam_id'))
                    ->where('subject_id', request('subject_id')))->ignore($recordId)],
                'marks_obtained' => ['required', 'numeric'],
                'grade' => ['nullable', 'string', 'max:10'],
                'remarks' => ['nullable', 'string'],
            ],
            'biometric-devices' => [
                'device_name' => ['required', 'string', 'max:150'],
                'device_code' => ['required', 'string', 'max:80', Rule::unique('biometric_devices', 'device_code')->ignore($recordId)],
                'brand' => ['nullable', 'string', 'max:80'],
                'model_no' => ['nullable', 'string', 'max:80'],
                'ip_address' => ['nullable', 'ip'],
                'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'location' => ['nullable', 'string', 'max:150'],
                'device_type' => ['required', Rule::in(['fingerprint', 'face', 'card', 'multi'])],
                'communication' => ['required', Rule::in(['push_api', 'pull_sdk', 'adms'])],
                'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
                'notes' => ['nullable', 'string'],
            ],
            'biometric-enrollments' => [
                'biometric_device_id' => ['required', 'exists:biometric_devices,id'],
                'enrollment_for' => ['required', Rule::in(['student', 'staff'])],
                'student_id' => ['required_if:enrollment_for,student', 'nullable', 'exists:students,id'],
                'staff_id' => ['required_if:enrollment_for,staff', 'nullable', 'exists:staff,id'],
                'punch_id' => ['required', 'string', 'max:80',
                    Rule::unique('biometric_enrollments')->where(fn ($q) => $q->where('biometric_device_id', request('biometric_device_id'))
                    )->ignore($recordId),
                ],
                'finger_index' => ['nullable', 'string', 'max:10'],
                'enrolled_at' => ['nullable', 'date'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'notes' => ['nullable', 'string'],
            ],
            'attendance' => [
                'attendance_for' => ['required', Rule::in(['student', 'staff'])],
                'attendance_method' => ['required', Rule::in(['manual', 'biometric_machine', 'mobile_face', 'mobile_finger'])],
                'attendance_date' => ['required', 'date'],
                'academic_class_id' => ['required_if:attendance_for,student', 'nullable', 'exists:academic_classes,id'],
                'section_id' => ['required_if:attendance_for,student', 'nullable', 'exists:sections,id'],
                'student_id' => ['required_if:attendance_for,student', 'nullable', Rule::unique('attendances')->where(fn ($query) => $query
                    ->where('attendance_for', 'student')
                    ->where('student_id', request('student_id'))
                    ->where('attendance_date', request('attendance_date')))->ignore($recordId)],
                'staff_attendance_id' => ['required_if:attendance_for,staff', 'nullable', Rule::unique('attendances')->where(fn ($query) => $query
                    ->where('attendance_for', 'staff')
                    ->where('staff_attendance_id', request('staff_attendance_id'))
                    ->where('attendance_date', request('attendance_date')))->ignore($recordId)],
                'marked_by_staff_id' => ['nullable', 'exists:staff,id'],
                'status' => ['required', 'string'],
                'sync_status' => ['required', Rule::in(['synced', 'pending', 'failed'])],
                'captured_at' => ['nullable', 'date'],
                'biometric_device_id' => ['nullable', 'string', 'max:120'],
                'biometric_log_id' => ['nullable', 'string', 'max:120'],
                'capture_payload' => ['nullable', 'json'],
                'remarks' => ['nullable', 'string'],
            ],
            'fees' => [
                'student_id' => ['required', 'exists:students,id'],
                'academic_class_id' => ['nullable', 'exists:academic_classes,id'],
                'fee_type' => ['required', 'string'],
                'amount' => ['required', 'numeric'],
                'paid_amount' => ['nullable', 'numeric'],
                'installment_amount' => ['nullable', 'numeric', 'min:0'],
                'installment_date' => ['nullable', 'date'],
                'due_date' => ['nullable', 'date'],
                'payment_mode' => ['nullable', 'string', 'max:50'],
                'status' => ['required', 'string'],
                'remarks' => ['nullable', 'string'],
            ],
            'timetable' => [
                'academic_class_id' => ['required', 'exists:academic_classes,id'],
                'section_id' => ['required', 'exists:sections,id'],
                'subject_id' => ['required', 'exists:subjects,id'],
                'staff_id' => ['nullable', 'exists:staff,id'],
                'day_of_week' => ['required', 'string'],
                'start_time' => ['required'],
                'end_time' => ['required'],
                'room_no' => ['nullable', 'string', 'max:50'],
            ],
            'notifications' => [
                'title' => ['required', 'string', 'max:150'],
                'message' => ['required', 'string'],
                'audience' => ['required', 'string'],
                'publish_date' => ['required', 'date'],
                'status' => ['required', 'string'],
            ],
            'holidays' => [
                'title' => ['required', 'string', 'max:150'],
                'holiday_type' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
                'description' => ['nullable', 'string'],
            ],
            'leaves' => [
                'requester_type' => ['required', 'string'],
                'staff_id' => ['nullable', 'exists:staff,id'],
                'student_id' => ['nullable', 'exists:students,id'],
                'leave_type' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
                'reason' => ['required', 'string'],
                'status' => ['required', 'string'],
            ],
            'calendar' => [
                'title' => ['required', 'string', 'max:150'],
                'event_type' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
                'location' => ['nullable', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
            ],
            default => [],
        };
    }

    // ─── Fee Structure ────────────────────────────────────────────────────────────

    public function feeStructureIndex(Request $request): View
    {
        $classes = AcademicClass::query()->where('status', 'active')->orderBy('name')->get();
        $classId = (int) $request->integer('class_id', 0);
        $year = trim((string) $request->input('academic_year', ''));

        $query = FeeStructure::query()
            ->with('academicClass')
            ->when($classId > 0, fn ($q) => $q->where('academic_class_id', $classId))
            ->when($year !== '', fn ($q) => $q->where('academic_year', $year))
            ->orderBy('academic_class_id')
            ->orderBy('fee_head');

        $structures = $query->get();

        $years = FeeStructure::query()
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return view('fee-structures.index', compact('structures', 'classes', 'classId', 'year', 'years'));
    }

    public function feeStructureStore(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'academic_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'fee_head' => ['required', 'string', 'max:50'],
            'fee_label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'academic_year' => ['required', 'string', 'max:10'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['created_by'] = $user->id;
        FeeStructure::create($data);

        return back()->with('status', 'Fee structure entry added successfully.');
    }

    public function feeStructureUpdate(Request $request, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $structure = FeeStructure::findOrFail($id);

        $data = $request->validate([
            'academic_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'fee_head' => ['required', 'string', 'max:50'],
            'fee_label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'academic_year' => ['required', 'string', 'max:10'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['updated_by'] = $user->id;
        $structure->update($data);

        return back()->with('status', 'Fee structure updated successfully.');
    }

    public function feeStructureDestroy(int $id): RedirectResponse
    {
        FeeStructure::findOrFail($id)->delete();

        return back()->with('status', 'Fee structure entry deleted.');
    }

    public function feeStructureAutoGenerate(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'academic_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'academic_year' => ['required', 'string', 'max:10'],
        ]);

        $classId = (int) $data['academic_class_id'];
        $academicYear = $data['academic_year'];

        $structures = FeeStructure::query()
            ->where('academic_class_id', $classId)
            ->where('academic_year', $academicYear)
            ->where('status', 'active')
            ->get();

        if ($structures->isEmpty()) {
            return back()->with('error', 'No active fee structures found for this class and academic year.');
        }

        $students = Student::query()
            ->where('academic_class_id', $classId)
            ->where('status', 'active')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No active students found in this class.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            foreach ($structures as $structure) {
                $dueDate = $structure->due_month
                    ? Carbon::createFromDate(now()->year, $structure->due_month, 1)->endOfMonth()->toDateString()
                    : null;

                $exists = Fee::query()
                    ->where('student_id', $student->id)
                    ->where('fee_type', $structure->fee_head)
                    ->when($dueDate, fn ($q) => $q->whereDate('due_date', $dueDate))
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Fee::create([
                    'student_id' => $student->id,
                    'academic_class_id' => $classId,
                    'fee_type' => $structure->fee_head,
                    'amount' => $structure->amount,
                    'paid_amount' => 0,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'remarks' => $structure->fee_label.' | '.$academicYear,
                    'created_by' => $user->id,
                ]);

                $created++;
            }
        }

        return back()->with('status', "Fee generation complete. Created: {$created} records, Skipped (already exist): {$skipped}.");
    }

    // ─── Certificate Generator ────────────────────────────────────────────────────

    public function certificateIndex(Request $request): View
    {
        $classes = AcademicClass::query()->where('status', 'active')->orderBy('name')->get();
        $classId = (int) $request->integer('class_id', 0);
        $search = trim((string) $request->input('q', ''));
        $organization = Organization::current();

        $students = Student::query()
            ->with('academicClass:id,name', 'section:id,name')
            ->when($classId > 0, fn ($q) => $q->where('academic_class_id', $classId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($inner) => $inner
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%")
                );
            })
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('certificates.index', compact('students', 'classes', 'classId', 'search', 'organization'));
    }

    public function certificateAssetsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stamp' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if (! $request->hasFile('stamp') && ! $request->hasFile('signature')) {
            return back()->with('error', 'Please choose stamp or signature image to upload.');
        }

        $organization = Organization::current() ?? new Organization;

        if (! $organization->exists) {
            $organization->fill([
                'type' => 'school',
                'name' => config('app.name', 'SchoolSphere'),
                'short_name' => config('app.name', 'SchoolSphere'),
                'is_active' => true,
                'created_by' => $request->user()->id,
            ]);
        }

        if ($request->hasFile('stamp')) {
            $organization->stamp_path = $request->file('stamp')->store('organization-stamps', 'public');
        }

        if ($request->hasFile('signature')) {
            $organization->signature_path = $request->file('signature')->store('organization-signatures', 'public');
        }

        $organization->updated_by = $request->user()->id;
        $organization->save();

        return back()->with('status', 'Certificate stamp and signature updated successfully.');
    }

    public function certificateGenerate(Request $request, string $type, int $studentId): Response
    {
        $allowedTypes = ['tc', 'bonafide', 'character'];
        if (! in_array($type, $allowedTypes, true)) {
            abort(404, 'Invalid certificate type.');
        }

        /** @var Student $student */
        $student = Student::query()
            ->with('academicClass:id,name,code', 'section:id,name')
            ->findOrFail($studentId);

        $org = Organization::current();

        $stampPath = null;
        $signaturePath = null;

        if ($org?->stamp_path) {
            $candidatePath = public_path('storage/'.$org->stamp_path);
            if (is_file($candidatePath)) {
                $stampPath = $candidatePath;
            }
        }

        if ($org?->signature_path) {
            $candidatePath = public_path('storage/'.$org->signature_path);
            if (is_file($candidatePath)) {
                $signaturePath = $candidatePath;
            }
        }

        $data = [
            'student' => $student,
            'org' => $org,
            'stampPath' => $stampPath,
            'signaturePath' => $signaturePath,
            'generatedOn' => now()->format('d M Y'),
            'type' => $type,
        ];

        $pdf = Pdf::loadView("certificates.{$type}", $data)
            ->setPaper('a4', 'portrait');

        $filename = strtoupper($type).'_'.$student->admission_no.'_'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }
}
