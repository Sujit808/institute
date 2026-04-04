@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <span class="eyebrow">Identity Cards</span>
                <h1 class="h2 mb-1">Student and Staff iCards</h1>
                <p class="text-body-secondary mb-0">Generate printable PDF identity cards using DomPDF.</p>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
            <div class="card app-card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                        <div>
                            <span class="eyebrow">Premium Signature</span>
                            <h2 class="h5 mb-1">Upload Digital Signature</h2>
                            <p class="text-body-secondary mb-0 small">This signature will be used on Premium V2 iCards.</p>
                        </div>

                        <form action="{{ route('icards.signature.upload') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-2">
                            @csrf
                            <input type="file" name="signature" accept=".png,.jpg,.jpeg,.webp" class="form-control form-control-sm @error('signature') is-invalid @enderror" required>
                            <button type="submit" class="btn btn-sm btn-primary">Upload Signature</button>
                        </form>
                    </div>

                    @if (! empty($signaturePreviewUrl))
                        <div class="mt-3 p-2 border rounded-3" style="max-width: 220px; background: #f8fbfd;">
                            <div class="small text-body-secondary mb-1">Current Signature</div>
                            <img src="{{ $signaturePreviewUrl }}" alt="Current signature" style="max-width: 200px; max-height: 70px; object-fit: contain;">
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Bulk iCard Download --}}
        @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isHr())
            <div class="card app-card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <span class="eyebrow">Bulk Export</span>
                    <h2 class="h5 mb-1">Bulk iCard Download</h2>
                    <p class="text-body-secondary small mb-3">Download all iCards for a class/section as a single ZIP file (max 200 records).</p>
                    <form action="{{ route('icards.bulk.download') }}" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label small mb-1">Type</label>
                                <select name="type" class="form-select form-select-sm" id="bulk-type" required>
                                    <option value="students">Students</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3" id="bulk-class-wrap">
                                <label class="form-label small mb-1">Class <span class="text-muted">(optional)</span></label>
                                <select name="class_id" class="form-select form-select-sm" id="bulk-class">
                                    <option value="">All Classes</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3" id="bulk-section-wrap">
                                <label class="form-label small mb-1">Section <span class="text-muted">(optional)</span></label>
                                <select name="section_id" class="form-select form-select-sm" id="bulk-section">
                                    <option value="">All Sections</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}" data-class="{{ $section->academic_class_id }}">
                                            {{ optional($section->academicClass)->name }} – {{ $section->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label small mb-1">Template</label>
                                <select name="template" class="form-select form-select-sm">
                                    <option value="standard">Standard</option>
                                    <option value="branded">Branded</option>
                                    <option value="premium">Premium V2</option>
                                </select>
                            </div>
                            <div class="col-sm-12 col-md-2">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-file-earmark-zip"></i> Download ZIP
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="eyebrow">Students</span>
                        <h2 class="h5 mb-3">Student iCards</h2>
                        <div class="stack-list">
                            @foreach ($students as $student)
                                <div class="stack-item">
                                    <div>
                                        <div class="fw-semibold">{{ $student->full_name }}</div>
                                        <div class="small text-body-secondary">{{ optional($student->academicClass)->name }} / {{ optional($student->section)->name }}</div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-primary" href="{{ route('icards.generate', ['type' => 'student', 'id' => $student->id, 'template' => 'standard']) }}">Standard</a>
                                        <a class="btn btn-outline-success" href="{{ route('icards.generate', ['type' => 'student', 'id' => $student->id, 'template' => 'branded']) }}">Branded</a>
                                        <a class="btn btn-outline-dark" href="{{ route('icards.generate', ['type' => 'student', 'id' => $student->id, 'template' => 'premium']) }}">Premium V2</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card app-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="eyebrow">Staff</span>
                        <h2 class="h5 mb-3">Staff iCards</h2>
                        <div class="stack-list">
                            @foreach ($staffMembers as $staff)
                                <div class="stack-item">
                                    <div>
                                        <div class="fw-semibold">{{ $staff->full_name }}</div>
                                        <div class="small text-body-secondary">{{ $staff->designation }}</div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-primary" href="{{ route('icards.generate', ['type' => 'staff', 'id' => $staff->id, 'template' => 'standard']) }}">Standard</a>
                                        <a class="btn btn-outline-success" href="{{ route('icards.generate', ['type' => 'staff', 'id' => $staff->id, 'template' => 'branded']) }}">Branded</a>
                                        <a class="btn btn-outline-dark" href="{{ route('icards.generate', ['type' => 'staff', 'id' => $staff->id, 'template' => 'premium']) }}">Premium V2</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const typeEl    = document.getElementById('bulk-type');
        const classEl   = document.getElementById('bulk-class');
        const sectionEl = document.getElementById('bulk-section');
        const classWrap   = document.getElementById('bulk-class-wrap');
        const sectionWrap = document.getElementById('bulk-section-wrap');

        // Store all original section options (excluding the placeholder)
        const allSectionOptions = Array.from(sectionEl.querySelectorAll('option[data-class]'))
            .map(o => ({ value: o.value, text: o.textContent, cls: o.dataset.class }));

        typeEl.addEventListener('change', function () {
            const isStaff = this.value === 'staff';
            classWrap.style.display   = isStaff ? 'none' : '';
            sectionWrap.style.display = isStaff ? 'none' : '';
        });

        classEl.addEventListener('change', function () {
            const selectedClass = this.value;
            // Reset section dropdown
            sectionEl.innerHTML = '<option value="">All Sections</option>';
            allSectionOptions
                .filter(o => !selectedClass || o.cls === selectedClass)
                .forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.value;
                    opt.dataset.class = o.cls;
                    opt.textContent = o.text;
                    sectionEl.appendChild(opt);
                });
        });
    })();
    </script>
@endsection
