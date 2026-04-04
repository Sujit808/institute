<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricEnrollment;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceIntegrationController extends Controller
{
    public function punch(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.attendance_integration.webhook_token', '');
        $requestToken = (string) $request->header('X-Attendance-Token', '');

        if ($configuredToken === '' || ! hash_equals($configuredToken, $requestToken)) {
            return response()->json([
                'message' => 'Unauthorized attendance integration request.',
            ], 401);
        }

        $validated = $request->validate([
            'attendance_for' => ['required', Rule::in(['student', 'staff'])],
            'attendance_method' => ['required', Rule::in(['manual', 'biometric_machine', 'mobile_face', 'mobile_finger'])],
            'external_user_code' => ['required', 'string', 'max:120'],
            'attendance_date' => ['nullable', 'date'],
            'captured_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['present', 'absent', 'late', 'leave'])],
            'remarks' => ['nullable', 'string'],
            'biometric_device_id' => ['nullable', 'string', 'max:120'],
            'biometric_log_id' => ['nullable', 'string', 'max:120'],
            'sync_status' => ['nullable', Rule::in(['synced', 'pending', 'failed'])],
            'capture_payload' => ['nullable', 'array'],
            'marked_by_employee_id' => ['nullable', 'string', 'max:120'],
        ]);

        $attendance = DB::transaction(function () use ($validated) {
            $attendanceFor = (string) $validated['attendance_for'];
            $attendanceDate = (string) ($validated['attendance_date'] ?? now()->toDateString());
            $capturedAt = $validated['captured_at'] ?? now();

            $studentId = null;
            $staffAttendanceId = null;
            $academicClassId = null;
            $sectionId = null;

            if ($attendanceFor === 'student') {
                // First try biometric enrollment lookup (punch_id + device = deterministic match)
                $enrollment = $this->findEnrollment('student', (string) $validated['external_user_code'], $validated['biometric_device_id'] ?? null);
                if ($enrollment) {
                    $student = $enrollment->student;
                } else {
                    $student = $this->resolveStudent((string) $validated['external_user_code']);
                }

                if (! $student) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Student not found for provided external_user_code.',
                    ], 422));
                }

                $studentId = $student->id;
                $academicClassId = $student->academic_class_id;
                $sectionId = $student->section_id;
            } else {
                // First try biometric enrollment lookup
                $enrollment = $this->findEnrollment('staff', (string) $validated['external_user_code'], $validated['biometric_device_id'] ?? null);
                if ($enrollment) {
                    $staff = $enrollment->staff;
                } else {
                    $staff = $this->resolveStaff((string) $validated['external_user_code']);
                }

                if (! $staff) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Staff not found for provided external_user_code.',
                    ], 422));
                }

                $staffAttendanceId = $staff->id;
            }

            $markedByStaffId = null;
            if (! empty($validated['marked_by_employee_id'])) {
                $markedByStaffId = Staff::query()
                    ->where('employee_id', $validated['marked_by_employee_id'])
                    ->orWhere('id', ctype_digit((string) $validated['marked_by_employee_id']) ? (int) $validated['marked_by_employee_id'] : 0)
                    ->value('id');
            }

            $attendance = Attendance::query()->firstOrNew([
                'attendance_for' => $attendanceFor,
                'attendance_date' => $attendanceDate,
                'student_id' => $studentId,
                'staff_attendance_id' => $staffAttendanceId,
            ]);

            $attendance->fill([
                'attendance_for' => $attendanceFor,
                'student_id' => $studentId,
                'staff_attendance_id' => $staffAttendanceId,
                'academic_class_id' => $academicClassId,
                'section_id' => $sectionId,
                'attendance_date' => $attendanceDate,
                'attendance_method' => $validated['attendance_method'],
                'status' => $validated['status'] ?? 'present',
                'sync_status' => $validated['sync_status'] ?? 'synced',
                'remarks' => $validated['remarks'] ?? null,
                'biometric_device_id' => $validated['biometric_device_id'] ?? null,
                'biometric_log_id' => $validated['biometric_log_id'] ?? null,
                'capture_payload' => $validated['capture_payload'] ?? null,
                'captured_at' => $capturedAt,
                'marked_by_staff_id' => $markedByStaffId,
                // Keep existing column synced for legacy attendance reports.
                'staff_id' => $markedByStaffId,
            ]);

            $attendance->save();

            return $attendance->fresh(['student', 'staffAttendance', 'markedBy']);
        });

        return response()->json([
            'message' => 'Attendance punch synced successfully.',
            'attendance' => [
                'id' => $attendance->id,
                'attendance_for' => $attendance->attendance_for,
                'attendance_method' => $attendance->attendance_method,
                'attendance_date' => optional($attendance->attendance_date)->toDateString(),
                'status' => $attendance->status,
                'sync_status' => $attendance->sync_status,
                'student' => $attendance->student?->full_name,
                'staff' => $attendance->staffAttendance?->full_name,
                'marked_by' => $attendance->markedBy?->full_name,
            ],
        ]);
    }

    private function findEnrollment(string $enrollmentFor, string $punchId, ?string $deviceCode): ?BiometricEnrollment
    {
        $query = BiometricEnrollment::query()
            ->with(['student', 'staff'])
            ->where('enrollment_for', $enrollmentFor)
            ->where('punch_id', $punchId)
            ->where('status', 'active');

        if ($deviceCode) {
            $query->whereHas('device', fn ($q) => $q->where('device_code', $deviceCode));
        }

        return $query->first();
    }

    private function resolveStudent(string $code): ?Student
    {
        return Student::query()
            ->where('admission_no', $code)
            ->orWhere('roll_no', $code)
            ->orWhere('id', ctype_digit($code) ? (int) $code : 0)
            ->first();
    }

    private function resolveStaff(string $code): ?Staff
    {
        return Staff::query()
            ->where('employee_id', $code)
            ->orWhere('email', $code)
            ->orWhere('id', ctype_digit($code) ? (int) $code : 0)
            ->first();
    }
}
