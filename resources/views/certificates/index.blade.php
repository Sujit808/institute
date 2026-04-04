@extends('layouts.app')

@section('content')
<style>
    .cert-shell { background:#f8fbff; border:1px solid #dbe7f3; border-radius:14px; padding:22px; }
    .cert-assets { background:linear-gradient(135deg,#eff6ff,#f8fafc); border:1px solid #cfe0f1; border-radius:12px; padding:16px; margin-bottom:14px; }
    .cert-filter { background:#fff; border:1px solid #dce7f3; border-radius:12px; padding:14px; }
    .cert-table-wrap { background:#fff; border:1px solid #dce7f3; border-radius:12px; overflow:hidden; margin-top:14px; }
    .cert-head th { background:linear-gradient(180deg,#f4f8fc,#ecf2f8); color:#334155; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #dce7f3; }
    .cert-actions .btn { font-size:.78rem; }
    .student-dot { display:inline-flex; align-items:center; gap:8px; font-weight:600; color:#1e293b; }
    .student-dot::before { content:""; width:8px; height:8px; border-radius:50%; background:#1d67c1; box-shadow:0 0 0 3px rgba(29,103,193,.12); }
    .asset-preview { height:88px; display:flex; align-items:center; justify-content:center; border:1px dashed #bfd2e6; border-radius:10px; background:#fff; overflow:hidden; }
    .asset-preview img { max-width:100%; max-height:100%; object-fit:contain; }
    .pagination .page-link { border-radius:8px; font-weight:600; font-size:.85rem; }
    .pagination .page-item.active .page-link { background:linear-gradient(180deg,#1f72d6,#1b60b3); border-color:#1d67c1; }
</style>

<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
    <div>
        <span class="eyebrow">Student Operations</span>
        <h1 class="h3 mb-1">Certificate Generator</h1>
        <p class="text-body-secondary mb-0">Generate Transfer Certificate, Bonafide, or Character Certificate as PDF.</p>
    </div>
</div>

<div class="cert-shell">
    <form method="POST" action="{{ route('certificates.assets.update') }}" enctype="multipart/form-data" class="cert-assets">
        @csrf
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
            <div>
                <h6 class="mb-1 fw-bold">Certificate Stamp & Signature</h6>
                <p class="text-body-secondary mb-0" style="font-size:.9rem;">Yahaan upload ki gayi images sabhi certificates me show hongi aur PDF download me bhi aayengi.</p>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-upload me-1"></i>Save Assets
            </button>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Office Stamp</label>
                <input type="file" name="stamp" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,image/*">
                <small class="text-body-secondary">PNG recommended with transparent background.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Principal Signature</label>
                <input type="file" name="signature" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,image/*">
                <small class="text-body-secondary">Prefer dark signature on white or transparent background.</small>
            </div>
            <div class="col-md-2">
                <div class="asset-preview">
                    @if(!empty($organization?->stamp_path))
                        <img src="{{ asset('storage/'.$organization->stamp_path) }}" alt="Stamp preview">
                    @else
                        <span class="text-body-secondary small">No stamp</span>
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <div class="asset-preview">
                    @if(!empty($organization?->signature_path))
                        <img src="{{ asset('storage/'.$organization->signature_path) }}" alt="Signature preview">
                    @else
                        <span class="text-body-secondary small">No signature</span>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <form method="GET" action="{{ route('certificates.index') }}" class="cert-filter mb-0">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Class</label>
                <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}" @selected($classId === $cls->id)>{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Search Student</label>
                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name or Admission No">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </div>
    </form>

    <div class="cert-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="cert-head">
                    <tr>
                        <th>Student</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th class="text-center">Certificates</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td><span class="student-dot">{{ $student->full_name }}</span></td>
                            <td class="text-secondary">{{ $student->admission_no ?: '-' }}</td>
                            <td>{{ $student->academicClass?->name ?? '-' }}</td>
                            <td>{{ $student->section?->name ?? '-' }}</td>
                            <td class="text-center cert-actions">
                                <a href="{{ route('certificates.generate', ['type' => 'tc', 'studentId' => $student->id]) }}"
                                   class="btn btn-sm btn-outline-danger me-1" target="_blank" title="Transfer Certificate">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>TC
                                </a>
                                <a href="{{ route('certificates.generate', ['type' => 'bonafide', 'studentId' => $student->id]) }}"
                                   class="btn btn-sm btn-outline-success me-1" target="_blank" title="Bonafide Certificate">
                                    <i class="bi bi-file-earmark-check me-1"></i>Bonafide
                                </a>
                                <a href="{{ route('certificates.generate', ['type' => 'character', 'studentId' => $student->id]) }}"
                                   class="btn btn-sm btn-outline-primary" target="_blank" title="Character Certificate">
                                    <i class="bi bi-file-earmark-person me-1"></i>Character
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                <i class="bi bi-file-earmark-x fs-2 d-block mb-2 opacity-50"></i>
                                No students found. Use filter to search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-secondary">
                    Showing <strong>{{ $students->firstItem() }}</strong> to <strong>{{ $students->lastItem() }}</strong> of <strong>{{ $students->total() }}</strong> students
                </small>
                {{ $students->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
