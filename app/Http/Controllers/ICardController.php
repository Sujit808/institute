<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ICardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->canAccessModule('icards'), 403);
        $supportsImages = extension_loaded('gd');
        $organization = Organization::current();
        $signaturePreviewUrl = $this->resolveSignaturePath($supportsImages, $organization);

        $students = Student::with(['academicClass', 'section'])
            ->when($user->isTeacher(), fn ($query) => $query->whereHas('section', fn ($section) => $section->where('class_teacher_id', $user->staff_id)))
            ->limit(20)
            ->get();

        $staffMembers = Staff::query()
            ->when($user->isTeacher(), fn ($query) => $query->whereKey($user->staff_id))
            ->limit(20)
            ->get();

        $classes = AcademicClass::orderBy('name')->get();
        $sections = Section::with('academicClass')->orderBy('academic_class_id')->orderBy('name')->get();

        return view('icards.index', compact('students', 'staffMembers', 'signaturePreviewUrl', 'classes', 'sections'));
    }

    public function uploadSignature(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin() || $request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'signature' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $organization = Organization::current() ?? new Organization;
        $file = $validated['signature'];
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = 'signature.'.($extension === 'jpeg' ? 'jpg' : $extension);

        $path = $file->storeAs('', $filename, 'public');

        $organization->updated_by = $request->user()->id;
        if (! $organization->exists) {
            $organization->created_by = $request->user()->id;
            $organization->name = $organization->name ?: (string) config('app.name', 'School');
            $organization->type = $organization->type ?: 'school';
            $organization->is_active = true;
        }

        // Safe dynamic assignment in case signature_path column exists.
        $organization->setAttribute('signature_path', $path);
        $organization->save();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'module' => 'icards',
            'action' => 'upload',
            'description' => 'Digital signature uploaded',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Digital signature uploaded successfully.');
    }

    public function generate(string $type, int $id, ?string $template = 'standard'): Response
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->canAccessModule('icards'), 403);
        $supportsImages = extension_loaded('gd');
        $organization = Organization::current();
        $schoolName = trim((string) ($organization?->name ?: config('app.name')));
        $schoolAddress = trim((string) ($organization?->address ?: 'School Address'));
        $logoUrl = $this->resolveLogoPath($supportsImages, $organization);
        $signatureUrl = $this->resolveSignaturePath($supportsImages, $organization);
        $sealUrl = $this->resolveSealPath($supportsImages, $organization);

        if ($type === 'student') {
            $record = Student::with(['academicClass', 'section'])
                ->when($user->isTeacher(), fn ($query) => $query->whereHas('section', fn ($section) => $section->where('class_teacher_id', $user->staff_id)))
                ->findOrFail($id);

            $issueDate = $record->admission_date?->format('d-m-Y') ?? now()->format('d-m-Y');
            $expiryDate = $record->admission_date?->copy()->addYear()->format('d-m-Y') ?? now()->addYear()->format('d-m-Y');

            $data = [
                'title' => 'Student iCard',
                'name' => $record->full_name,
                'metaLabel' => 'Class / Section',
                'metaValue' => trim(optional($record->academicClass)->name.' '.optional($record->section)->name),
                'photoUrl' => $this->resolvePhotoPath($record->photo, $supportsImages),
                'id' => $record->admission_no,
                'year' => date('Y'),
                'phone' => $record->phone ?: $record->guardian_phone,
                'email' => $record->email,
                'rollNo' => $record->roll_no,
                'admissionNo' => $record->admission_no,
                'bloodGroup' => $record->blood_group ?: 'N/A',
                'issueDate' => $issueDate,
                'expiryDate' => $expiryDate,
                'roleLine' => 'Student',
                'logoUrl' => $logoUrl,
                'schoolName' => $schoolName,
                'schoolAddress' => $schoolAddress,
                'signatureUrl' => $signatureUrl,
                'sealUrl' => $sealUrl,
            ];
        } else {
            $record = Staff::query()
                ->when($user->isTeacher(), fn ($query) => $query->whereKey($user->staff_id))
                ->findOrFail($id);

            $issueDate = $record->joining_date?->format('d-m-Y') ?? now()->format('d-m-Y');
            $expiryDate = $record->joining_date?->copy()->addYear()->format('d-m-Y') ?? now()->addYear()->format('d-m-Y');

            $data = [
                'title' => 'Staff iCard',
                'name' => $record->full_name,
                'metaLabel' => 'Role / Designation',
                'metaValue' => trim($record->role_type.' / '.$record->designation),
                'photoUrl' => $this->resolvePhotoPath($record->photo, $supportsImages),
                'id' => $record->employee_id,
                'year' => date('Y'),
                'phone' => $record->phone,
                'email' => $record->email,
                'rollNo' => $record->employee_id,
                'admissionNo' => $record->employee_id,
                'bloodGroup' => 'N/A',
                'issueDate' => $issueDate,
                'expiryDate' => $expiryDate,
                'roleLine' => $record->designation ?: 'Staff Member',
                'logoUrl' => $logoUrl,
                'schoolName' => $schoolName,
                'schoolAddress' => $schoolAddress,
                'signatureUrl' => $signatureUrl,
                'sealUrl' => $sealUrl,
            ];
        }

        $viewName = match ($template) {
            'branded' => 'icards.pdf-branded',
            'premium' => 'icards.pdf-premium',
            default => 'icards.pdf',
        };

        $firstName = strtolower(explode(' ', trim($data['name']))[0]);
        $firstName = preg_replace('/[^a-z0-9]/', '', $firstName) ?: 'student';
        $rollSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($data['rollNo'] ?? $id)));
        $filename = $firstName.'-'.$rollSlug.'-icard.pdf';

        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'icards',
            'action' => 'generate',
            'description' => 'iCard generated: '.$data['name'].' ('.$type.', '.$template.')',
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return Pdf::loadView($viewName, $data)->download($filename);
    }

    public function bulkDownload(Request $request): mixed
    {
        abort_unless(
            $request->user()?->isSuperAdmin() || $request->user()?->isAdmin() || $request->user()?->isHr(),
            403
        );

        $request->validate([
            'type' => ['required', 'in:students,staff'],
            'template' => ['required', 'in:standard,branded,premium'],
            'class_id' => ['nullable', 'integer', 'min:1'],
            'section_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $type = (string) $request->input('type');
        $template = (string) $request->input('template', 'standard');
        $classId = $request->integer('class_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;

        /** @var User $user */
        $user = $request->user();
        $supportsImages = extension_loaded('gd');
        $organization = Organization::current();
        $schoolName = trim((string) ($organization?->name ?: config('app.name')));
        $schoolAddress = trim((string) ($organization?->address ?: 'School Address'));
        $logoUrl = $this->resolveLogoPath($supportsImages, $organization);
        $signatureUrl = $this->resolveSignaturePath($supportsImages, $organization);
        $sealUrl = $this->resolveSealPath($supportsImages, $organization);

        $viewName = match ($template) {
            'branded' => 'icards.pdf-branded',
            'premium' => 'icards.pdf-premium',
            default => 'icards.pdf',
        };

        if ($type === 'students') {
            $records = Student::with(['academicClass', 'section'])
                ->when($classId, fn ($q) => $q->where('academic_class_id', $classId))
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->limit(200)
                ->get();
        } else {
            $records = Staff::query()->limit(200)->get();
        }

        if ($records->isEmpty()) {
            return back()->with('error', 'No records found for the selected filters.');
        }

        if (! class_exists('\ZipArchive')) {
            return back()->with('error', 'ZIP support (ZipArchive) is not enabled on this server.');
        }

        $zipName = $type.'-icards-'.date('Ymd-His').'.zip';
        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$zipName;

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP archive.');
        }

        foreach ($records as $record) {
            if ($type === 'students') {
                $issueDate = $record->admission_date?->format('d-m-Y') ?? now()->format('d-m-Y');
                $expiryDate = $record->admission_date?->copy()->addYear()->format('d-m-Y') ?? now()->addYear()->format('d-m-Y');
                $data = [
                    'title' => 'Student iCard',
                    'name' => $record->full_name,
                    'metaLabel' => 'Class / Section',
                    'metaValue' => trim(optional($record->academicClass)->name.' '.optional($record->section)->name),
                    'photoUrl' => $this->resolvePhotoPath($record->photo, $supportsImages),
                    'id' => $record->admission_no,
                    'year' => date('Y'),
                    'phone' => $record->phone ?: $record->guardian_phone,
                    'email' => $record->email,
                    'rollNo' => $record->roll_no,
                    'admissionNo' => $record->admission_no,
                    'bloodGroup' => $record->blood_group ?: 'N/A',
                    'issueDate' => $issueDate,
                    'expiryDate' => $expiryDate,
                    'roleLine' => 'Student',
                    'logoUrl' => $logoUrl,
                    'schoolName' => $schoolName,
                    'schoolAddress' => $schoolAddress,
                    'signatureUrl' => $signatureUrl,
                    'sealUrl' => $sealUrl,
                ];
                $slug = preg_replace('/[^a-z0-9]/', '', strtolower(explode(' ', trim($record->full_name))[0])) ?: 'student';
                $fileEntry = $slug.'-'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $record->roll_no)).'-icard.pdf';
            } else {
                $issueDate = $record->joining_date?->format('d-m-Y') ?? now()->format('d-m-Y');
                $expiryDate = $record->joining_date?->copy()->addYear()->format('d-m-Y') ?? now()->addYear()->format('d-m-Y');
                $data = [
                    'title' => 'Staff iCard',
                    'name' => $record->full_name,
                    'metaLabel' => 'Role / Designation',
                    'metaValue' => trim($record->role_type.' / '.$record->designation),
                    'photoUrl' => $this->resolvePhotoPath($record->photo, $supportsImages),
                    'id' => $record->employee_id,
                    'year' => date('Y'),
                    'phone' => $record->phone,
                    'email' => $record->email,
                    'rollNo' => $record->employee_id,
                    'admissionNo' => $record->employee_id,
                    'bloodGroup' => 'N/A',
                    'issueDate' => $issueDate,
                    'expiryDate' => $expiryDate,
                    'roleLine' => $record->designation ?: 'Staff Member',
                    'logoUrl' => $logoUrl,
                    'schoolName' => $schoolName,
                    'schoolAddress' => $schoolAddress,
                    'signatureUrl' => $signatureUrl,
                    'sealUrl' => $sealUrl,
                ];
                $slug = preg_replace('/[^a-z0-9]/', '', strtolower(explode(' ', trim($record->full_name))[0])) ?: 'staff';
                $fileEntry = $slug.'-'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $record->employee_id)).'-icard.pdf';
            }

            $pdfContent = Pdf::loadView($viewName, $data)->output();
            $zip->addFromString($fileEntry, $pdfContent);
        }

        $zip->close();

        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'icards',
            'action' => 'bulk-download',
            'description' => 'Bulk iCard download: '.$records->count().' '.$type.' ('.$template.')',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function resolvePhotoPath(?string $photo, bool $supportsImages): ?string
    {
        if (! $supportsImages || empty($photo)) {
            return null;
        }

        $publicStoragePath = public_path('storage/'.$photo);
        if (is_file($publicStoragePath)) {
            return $this->toDataUri($publicStoragePath);
        }

        $localStoragePath = storage_path('app/public/'.$photo);
        if (is_file($localStoragePath)) {
            return $this->toDataUri($localStoragePath);
        }

        return null;
    }

    private function resolveLogoPath(bool $supportsImages, ?Organization $organization = null): ?string
    {
        if (! $supportsImages) {
            return null;
        }

        if (! empty($organization?->logo_path)) {
            $publicStoragePath = public_path('storage/'.$organization->logo_path);
            if (is_file($publicStoragePath)) {
                return $this->toDataUri($publicStoragePath);
            }

            $localStoragePath = storage_path('app/public/'.$organization->logo_path);
            if (is_file($localStoragePath)) {
                return $this->toDataUri($localStoragePath);
            }
        }

        // SVG is intentionally skipped because DomPDF cannot render SVG images.
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = public_path('school-logo.'.$ext);
            if (is_file($path)) {
                return $this->toDataUri($path);
            }
        }

        return null;
    }

    private function resolveSignaturePath(bool $supportsImages, ?Organization $organization = null): ?string
    {
        if (! $supportsImages) {
            return null;
        }

        $storedPath = data_get($organization, 'signature_path');
        if (is_string($storedPath) && $storedPath !== '') {
            $publicStoragePath = public_path('storage/'.$storedPath);
            if (is_file($publicStoragePath)) {
                return $this->toDataUri($publicStoragePath);
            }

            $localStoragePath = storage_path('app/public/'.$storedPath);
            if (is_file($localStoragePath)) {
                return $this->toDataUri($localStoragePath);
            }
        }

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            foreach (['principal-signature', 'authorized-signature', 'signature'] as $name) {
                $path = public_path($name.'.'.$ext);
                if (is_file($path)) {
                    return $this->toDataUri($path);
                }

                $publicStoragePath = public_path('storage/'.$name.'.'.$ext);
                if (is_file($publicStoragePath)) {
                    return $this->toDataUri($publicStoragePath);
                }

                $localStoragePath = storage_path('app/public/'.$name.'.'.$ext);
                if (is_file($localStoragePath)) {
                    return $this->toDataUri($localStoragePath);
                }
            }
        }

        return null;
    }

    private function resolveSealPath(bool $supportsImages, ?Organization $organization = null): ?string
    {
        if (! $supportsImages) {
            return null;
        }

        $storedPath = data_get($organization, 'seal_path');
        if (is_string($storedPath) && $storedPath !== '') {
            $publicStoragePath = public_path('storage/'.$storedPath);
            if (is_file($publicStoragePath)) {
                return $this->toDataUri($publicStoragePath);
            }

            $localStoragePath = storage_path('app/public/'.$storedPath);
            if (is_file($localStoragePath)) {
                return $this->toDataUri($localStoragePath);
            }
        }

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            foreach (['school-seal', 'institute-seal', 'seal', 'stamp'] as $name) {
                $path = public_path($name.'.'.$ext);
                if (is_file($path)) {
                    return $this->toDataUri($path);
                }
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
}
