<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\Exam;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Result;
use App\Models\SchoolNotification;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }

        $studentIds = $this->teacherStudentIds($user);

        $studentQuery = Student::query();
        $attendanceQuery = Attendance::query();
        $resultQuery = Result::query();
        $paymentQuery = Payment::query();
        $leaveQuery = LeaveRequest::query();

        if ($user->isTeacher()) {
            $studentQuery->whereIn('id', $studentIds ?: [0]);
            $attendanceQuery->whereIn('student_id', $studentIds ?: [0]);
            $resultQuery->whereIn('student_id', $studentIds ?: [0]);
            $paymentQuery->whereIn('student_id', $studentIds ?: [0]);
            $leaveQuery->where('staff_id', $user->staff_id);
        }

        $stats = [
            ['label' => 'Total Students', 'value' => $studentQuery->count()],
            ['label' => 'Total Staff', 'value' => $user->isTeacher() ? 1 : Staff::count()],
            ['label' => 'Fees Collected', 'value' => number_format((float) $paymentQuery->sum('amount'), 2)],
            ['label' => 'Leaves Pending', 'value' => $leaveQuery->where('status', 'pending')->count()],
        ];

        $attendanceLabels = collect(range(6, 0))->map(fn (int $offset) => now()->subDays($offset)->format('d M'));
        $attendancePresent = collect(range(6, 0))->map(fn (int $offset) => (clone $attendanceQuery)
            ->whereDate('attendance_date', now()->subDays($offset)->toDateString())
            ->where('status', 'present')
            ->count());
        $attendanceAbsent = collect(range(6, 0))->map(fn (int $offset) => (clone $attendanceQuery)
            ->whereDate('attendance_date', now()->subDays($offset)->toDateString())
            ->where('status', 'absent')
            ->count());

        $feesLabels = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->format('M Y'));
        $feesValues = collect(range(5, 0))->map(fn (int $offset) => (float) (clone $paymentQuery)
            ->whereYear('payment_date', now()->subMonths($offset)->year)
            ->whereMonth('payment_date', now()->subMonths($offset)->month)
            ->sum('amount'));

        $examIds = Exam::query()->latest()->limit(5)->pluck('id');
        $resultLabels = Exam::query()->whereIn('id', $examIds)->pluck('name');
        $resultValues = $examIds->map(fn (int $examId) => round((float) (clone $resultQuery)
            ->where('exam_id', $examId)
            ->avg('marks_obtained'), 2));

        $chartData = [
            'attendance' => [
                'labels' => $attendanceLabels,
                'present' => $attendancePresent,
                'absent' => $attendanceAbsent,
            ],
            'fees' => [
                'labels' => $feesLabels,
                'values' => $feesValues,
            ],
            'results' => [
                'labels' => $resultLabels,
                'values' => $resultValues,
            ],
        ];

        $upcomingItems = collect()
            ->merge(Holiday::query()->whereDate('start_date', '>=', now()->toDateString())->limit(5)->get()->map(fn (Holiday $holiday) => [
                'title' => $holiday->title,
                'date' => $holiday->start_date?->format('d M Y'),
                'type' => 'Holiday',
            ]))
            ->merge(CalendarEvent::query()->where('start_date', '>=', now())->limit(5)->get()->map(fn (CalendarEvent $event) => [
                'title' => $event->title,
                'date' => $event->start_date?->format('d M Y h:i A'),
                'type' => 'Event',
            ]))
            ->sortBy('date')
            ->take(6)
            ->values();

        $notifications = SchoolNotification::query()
            ->where('status', 'published')
            ->whereDate('publish_date', '<=', now()->toDateString())
            ->latest('publish_date')
            ->limit(5)
            ->get();

        $quickLinks = array_slice(SchoolModuleRegistry::navigation($user), 0, 8);

        if ($user->canAccessModule('icards') && ! collect($quickLinks)->contains(fn (array $link): bool => ($link['key'] ?? null) === 'icards')) {
            $quickLinks[] = ['key' => 'icards', 'title' => 'iCards', 'route' => route('icards.index')];
        }

        if ($user->isSuperAdmin() && ! collect($quickLinks)->contains(fn (array $link): bool => ($link['key'] ?? null) === 'license-settings')) {
            $quickLinks[] = ['key' => 'license-settings', 'title' => 'Master Control', 'route' => route('license-settings.edit')];
        }

        return view('dashboard', compact('stats', 'chartData', 'upcomingItems', 'notifications', 'quickLinks'));
    }

    public function accessMatrix(): View
    {
        return view('settings.access-matrix', [
            'matrix' => SchoolModuleRegistry::roleMatrix(),
            'presets' => [
                'admin' => SchoolModuleRegistry::defaultPermissionsForRole('admin'),
                'hr' => SchoolModuleRegistry::defaultPermissionsForRole('hr'),
                'teacher' => SchoolModuleRegistry::defaultPermissionsForRole('teacher'),
            ],
            'specialRoutes' => [
                ['label' => 'Master Calendar', 'route' => 'master.calendar', 'permission' => 'attendance'],
                ['label' => 'Student Calendar', 'route' => 'students.calendar.index', 'permission' => 'students'],
                ['label' => 'My Attendance', 'route' => 'my.attendance', 'permission' => 'attendance'],
                ['label' => 'iCards', 'route' => 'icards.index', 'permission' => 'icards'],
                ['label' => 'Quotations', 'route' => 'quotations.create', 'permission' => 'quotations'],
                ['label' => 'Master Control', 'route' => 'license-settings.edit', 'permission' => 'super_admin only'],
            ],
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
}
