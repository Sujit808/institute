<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AdmissionLead;
use App\Models\AuditLog;
use App\Models\LicenseConfig;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdmissionLeadController extends Controller
{
    /**
     * @return array<string, string>
     */
    public static function stages(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'counselling_scheduled' => 'Counselling Scheduled',
            'counselling_done' => 'Counselling Done',
            'follow_up' => 'Follow Up',
            'converted' => 'Converted',
            'lost' => 'Lost',
        ];
    }

    public function kanban(Request $request): View
    {
        $stages = self::stages();

        $leads = AdmissionLead::query()
            ->with(['academicClass', 'assignedToStaff', 'convertedStudent'])
            ->whereIn('stage', array_keys($stages))
            ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_follow_up_at')
            ->latest('id')
            ->get();

        $totalLeads = $leads->count();
        $convertedLeads = $leads->where('stage', 'converted')->count();
        $activeLeads = $leads->where('status', 'active')->count();
        $today = Carbon::now()->startOfDay();
        $dueToday = $leads->filter(fn (AdmissionLead $lead): bool => $lead->next_follow_up_at && $lead->next_follow_up_at->copy()->startOfDay()->eq($today))->count();
        $overdue = $leads->filter(fn (AdmissionLead $lead): bool => $lead->next_follow_up_at && $lead->next_follow_up_at->copy()->startOfDay()->lt($today) && ! in_array($lead->stage, ['converted', 'lost'], true))->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;

        $grouped = [];

        foreach (array_keys($stages) as $stage) {
            $grouped[$stage] = [];
        }

        foreach ($leads as $lead) {
            $grouped[$lead->stage][] = $lead;
        }

        return view('admission-leads.kanban', [
            'stages' => $stages,
            'groupedLeads' => $grouped,
            'today' => $today,
            'stageWipLimits' => LicenseConfig::current()?->admissionLeadWipLimits() ?? LicenseConfig::defaultAdmissionLeadWipLimits(),
            'kpi' => [
                'total' => $totalLeads,
                'active' => $activeLeads,
                'converted' => $convertedLeads,
                'conversion_rate' => $conversionRate,
                'due_today' => $dueToday,
                'overdue' => $overdue,
            ],
            'classLookups' => AcademicClass::query()->orderBy('name')->pluck('name', 'id')->all(),
            'sectionLookups' => Section::query()->select(['id', 'name', 'academic_class_id'])->orderBy('name')->get()->map(fn (Section $section): array => [
                'id' => $section->id,
                'name' => $section->name,
                'academic_class_id' => $section->academic_class_id,
            ])->values()->all(),
        ]);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'in:'.implode(',', array_keys(self::stages()))],
            'next_follow_up_at' => ['nullable', 'date'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
            'remarks' => ['nullable', 'string'],
        ]);

        $lead = AdmissionLead::query()->findOrFail($id);
        $previousStage = (string) $lead->stage;
        $this->enforceStageWipLimit((string) $validated['stage'], $lead->id);

        $lead->stage = $validated['stage'];
        if (array_key_exists('next_follow_up_at', $validated)) {
            $lead->next_follow_up_at = $validated['next_follow_up_at'];
        }
        if (array_key_exists('score', $validated)) {
            $lead->score = $validated['score'];
        }
        if (array_key_exists('status', $validated)) {
            $lead->status = $validated['status'];
        }
        if (array_key_exists('remarks', $validated)) {
            $lead->remarks = $validated['remarks'];
        }
        if (in_array($lead->stage, ['contacted', 'counselling_done', 'follow_up', 'converted'], true)) {
            $lead->last_contacted_at = Carbon::now();
        }
        if (! array_key_exists('score', $validated)) {
            $lead->score = $lead->calculateScore();
        }
        $lead->save();

        $this->audit($request, 'update', $lead, 'Admission lead stage updated', [
            'from_stage' => $previousStage,
            'to_stage' => $lead->stage,
            'score' => $lead->score,
            'status' => $lead->status,
        ]);

        return response()->json([
            'message' => 'Lead stage updated successfully.',
            'lead' => [
                'id' => $lead->id,
                'stage' => $lead->stage,
                'score' => $lead->score,
                'next_follow_up_at' => optional($lead->next_follow_up_at)?->toDateTimeString(),
                'status' => $lead->status,
            ],
        ]);
    }

    public function convertToStudent(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'academic_class_id' => ['required_without:existing_student_id', 'nullable', 'exists:academic_classes,id'],
            'section_id' => ['required_without:existing_student_id', 'nullable', 'exists:sections,id'],
            'existing_student_id' => ['nullable', 'exists:students,id'],
            'gender' => ['required', 'in:male,female,other'],
            'admission_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,inactive,alumni'],
            'conversion_reason' => ['nullable', 'string', 'max:1000'],
            'force_convert' => ['nullable', 'boolean'],
        ]);

        $lead = AdmissionLead::query()->with('convertedStudent')->findOrFail($id);
        if ($lead->converted_student_id && $lead->convertedStudent) {
            return response()->json([
                'message' => 'Lead already converted.',
                'student_id' => $lead->converted_student_id,
            ], 422);
        }

        if (! empty($validated['existing_student_id'])) {
            $student = Student::query()->findOrFail((int) $validated['existing_student_id']);

            $lead->update([
                'stage' => 'converted',
                'status' => 'active',
                'converted_student_id' => $student->id,
                'converted_at' => now(),
                'conversion_reason' => $validated['conversion_reason'] ?? null,
                'last_contacted_at' => now(),
                'updated_by' => $request->user()->id,
            ]);
            // Auto-score after conversion
            $lead->score = $lead->calculateScore();
            $lead->save();

            $this->audit($request, 'convert-link', $lead->fresh(), 'Admission lead linked to existing student', [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no,
                'conversion_reason' => $validated['conversion_reason'] ?? null,
            ]);

            return response()->json([
                'message' => 'Lead linked to existing student successfully.',
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $student->roll_no,
                ],
            ]);
        }

        $section = Section::query()->findOrFail((int) $validated['section_id']);
        if ((int) $section->academic_class_id !== (int) $validated['academic_class_id']) {
            throw ValidationException::withMessages([
                'section_id' => 'Selected section does not belong to selected class.',
            ]);
        }

        $license = LicenseConfig::current();
        $strictDuplicateCheck = $license?->admissionDuplicateStrict() ?? true;

        $duplicates = $this->findDuplicateStudents($lead);
        if ($strictDuplicateCheck && ! ($validated['force_convert'] ?? false) && $duplicates !== []) {
            return response()->json([
                'message' => 'Potential duplicate students found. Review before converting.',
                'duplicates' => $duplicates,
                'strict_mode' => true,
                'suggested_student_id' => $duplicates[0]['id'] ?? null,
            ], 422);
        }

        [$firstName, $lastName] = $this->splitStudentName((string) $lead->student_name);

        $student = DB::transaction(function () use ($lead, $validated, $firstName, $lastName): Student {
            $student = Student::create([
                'academic_class_id' => (int) $validated['academic_class_id'],
                'section_id' => (int) $validated['section_id'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $validated['gender'],
                'phone' => $lead->phone,
                'email' => $lead->email,
                'guardian_name' => $lead->guardian_name,
                'guardian_phone' => $lead->phone,
                'admission_date' => $validated['admission_date'] ?? now()->toDateString(),
                'status' => $validated['status'] ?? 'active',
                'created_by' => request()->user()->id,
                'updated_by' => request()->user()->id,
            ]);

            $this->syncStudentUserFromLead($student);

            $lead->update([
                'stage' => 'converted',
                'status' => 'active',
                'converted_student_id' => $student->id,
                'converted_at' => now(),
                'conversion_reason' => $validated['conversion_reason'] ?? null,
                'last_contacted_at' => now(),
                'updated_by' => request()->user()->id,
            ]);
            // Auto-score after conversion
            $lead->score = $lead->calculateScore();
            $lead->save();

            return $student;
        });

        $this->audit($request, 'convert', $lead->fresh(), 'Admission lead converted to student', [
            'student_id' => $student->id,
            'admission_no' => $student->admission_no,
            'roll_no' => $student->roll_no,
            'conversion_reason' => $validated['conversion_reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lead converted successfully.',
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findDuplicateStudents(AdmissionLead $lead): array
    {
        $phone = trim((string) ($lead->phone ?? ''));
        $email = strtolower(trim((string) ($lead->email ?? '')));
        [$firstName, $lastName] = $this->splitStudentName((string) $lead->student_name);

        $matches = Student::query()
            ->where(function ($query) use ($phone, $email, $firstName, $lastName): void {
                if ($phone !== '') {
                    $query->orWhere('phone', $phone)
                        ->orWhere('guardian_phone', $phone);
                }

                if ($email !== '') {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($firstName !== '' && $lastName !== '') {
                    $query->orWhere(function ($nested) use ($firstName, $lastName): void {
                        $nested->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                            ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)]);
                    });
                }
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return $matches->map(function (Student $student) use ($phone, $email, $firstName, $lastName): array {
            $confidence = 0;
            $reasons = [];

            if ($phone !== '' && ($student->phone === $phone || $student->guardian_phone === $phone)) {
                $confidence += 45;
                $reasons[] = 'Phone match';
            }

            if ($email !== '' && strtolower((string) $student->email) === $email) {
                $confidence += 35;
                $reasons[] = 'Email match';
            }

            if (strtolower((string) $student->first_name) === strtolower($firstName) && strtolower((string) $student->last_name) === strtolower($lastName)) {
                $confidence += 25;
                $reasons[] = 'Name match';
            }

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no,
                'phone' => $student->phone,
                'email' => $student->email,
                'confidence' => min(100, $confidence),
                'confidence_label' => $confidence >= 70 ? 'high' : ($confidence >= 45 ? 'medium' : 'low'),
                'reasons' => $reasons,
            ];
        })->sortByDesc('confidence')->values()->all();
    }

    private function enforceStageWipLimit(string $targetStage, int $excludeLeadId): void
    {
        $limit = (int) ((LicenseConfig::current()?->admissionLeadWipLimits()[$targetStage] ?? 0));
        if ($limit <= 0) {
            return;
        }

        $currentCount = AdmissionLead::query()
            ->where('stage', $targetStage)
            ->whereKeyNot($excludeLeadId)
            ->count();

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'stage' => 'WIP limit reached for '.$targetStage.' stage. Limit: '.$limit.', current: '.$currentCount.'.',
            ]);
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitStudentName(string $fullName): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $fullName) ?: '');
        if ($name === '') {
            return ['Student', 'Lead'];
        }

        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return [$parts[0], 'Lead'];
        }

        $last = array_pop($parts);

        return [implode(' ', $parts), (string) $last];
    }

    private function syncStudentUserFromLead(Student $student): void
    {
        $user = User::withTrashed()->firstOrNew(['student_id' => $student->id]);
        if ($user->trashed()) {
            $user->restore();
        }

        $email = $student->email;
        if (! $email || User::query()->where('email', $email)->whereKeyNot($user->id ?? 0)->exists()) {
            $email = 'student'.$student->id.'@students.schoolsphere.local';
        }

        $user->fill([
            'name' => $student->full_name,
            'email' => $email,
            'phone' => $student->phone ?: $student->guardian_phone,
            'photo' => $student->photo,
            'role' => 'student',
            'permissions' => [],
            'student_id' => $student->id,
            'active' => $student->status === 'active',
            'must_change_password' => false,
        ]);

        if (! $user->exists || empty($user->getRawOriginal('password'))) {
            $seed = trim((string) ($student->roll_no ?: 'student'.$student->id));
            $user->password = Hash::make(Str::limit($seed, 32, ''));
        }

        $user->save();
    }

    private function audit(Request $request, string $action, AdmissionLead $lead, string $description, array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'module' => 'admission-leads',
            'action' => $action,
            'description' => $description,
            'auditable_type' => AdmissionLead::class,
            'auditable_id' => $lead->id,
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
    }
}
