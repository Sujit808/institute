@extends('layouts.app')

@push('styles')
<style>
    .step-line {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 1.5rem;
        overflow-x: auto;
    }
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 80px;
        flex: 1;
        position: relative;
        z-index: 1;
    }
    .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 16px;
        left: calc(50% + 16px);
        width: calc(100% - 32px);
        height: 2px;
        background: #dee2e6;
        z-index: 0;
    }
    .step-item.done::after  { background: #16a34a; }
    .step-dot {
        width: 32px; height: 32px; border-radius: 50%;
        border: 2px solid #dee2e6; background: #fff; color: #6c757d;
        font-weight: 700; font-size: 0.8rem;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 4px; position: relative; z-index: 1; transition: all .2s;
    }
    .step-item.active .step-dot { border-color: #2563eb; background: #2563eb; color: #fff; }
    .step-item.done   .step-dot { border-color: #16a34a; background: #16a34a; color: #fff; }
    .step-label { font-size: 0.7rem; color: #6c757d; text-align: center; white-space: nowrap; }
    .step-item.active .step-label { color: #2563eb; font-weight: 600; }
    .step-item.done   .step-label { color: #16a34a; }
    .set-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 50%;
        font-weight: 700; font-size: 0.85rem; color: #fff;
    }
    .set-A { background: #2563eb; }
    .set-B { background: #16a34a; }
    .set-C { background: #d97706; }
    .set-D { background: #7c3aed; }
    .set-E { background: #dc2626; }
    .assignment-row-A { background: rgba(37,99,235,0.05); }
    .assignment-row-B { background: rgba(22,163,74,0.05); }
    .assignment-row-C { background: rgba(217,119,6,0.05); }
    .assignment-row-D { background: rgba(124,58,237,0.05); }
    .assignment-row-E { background: rgba(220,38,38,0.05); }
    .file-upload-card {
        border: 2px dashed #dee2e6; border-radius: 12px;
        padding: 1rem 1.25rem; transition: border-color .2s;
    }
    select:disabled { background-color: #f8f9fa; color: #adb5bd; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Exam Management</span>
            <h1 class="h3 mb-1">Set-wise Paper Upload</h1>
            <p class="text-body-secondary mb-0">Select exam type &rarr; class &rarr; section &rarr; question sets &rarr; upload papers per set.</p>
        </div>
        <a href="{{ route('exam-builder.index') }}" class="btn btn-outline-secondary">Back to Builder</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible mb-4">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible mb-4">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Step Indicator --}}
    <div class="step-line">
        <div class="step-item active" data-step="1"><div class="step-dot">1</div><div class="step-label">Exam Type</div></div>
        <div class="step-item"        data-step="2"><div class="step-dot">2</div><div class="step-label">Class</div></div>
        <div class="step-item"        data-step="3"><div class="step-dot">3</div><div class="step-label">Section</div></div>
        <div class="step-item"        data-step="4"><div class="step-dot">4</div><div class="step-label">Select Exam</div></div>
        <div class="step-item"        data-step="5"><div class="step-dot">5</div><div class="step-label">Question Sets</div></div>
        <div class="step-item"        data-step="6"><div class="step-dot">6</div><div class="step-label">Upload Papers</div></div>
    </div>

    <div class="row g-4">

        {{-- Left: Step Form --}}
        <div class="col-xl-5">
            <div class="card app-card">
                <div class="card-body p-4">

                    {{-- Step 1 --}}
                    <div id="step1Block">
                        <div class="eyebrow mb-1">Step 1 of 6</div>
                        <label class="form-label fw-semibold">Exam Type</label>
                        <select id="examTypeSelect" class="form-select">
                            <option value="">— Select Exam Type —</option>
                            @foreach ($examTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">e.g. Unit Test, Mid Term, Final</div>
                    </div>

                    {{-- Step 2 --}}
                    <div id="step2Block" class="mt-3 d-none">
                        <div class="eyebrow mb-1">Step 2 of 6</div>
                        <label class="form-label fw-semibold">Class</label>
                        <select id="classSelect" class="form-select" disabled>
                            <option value="">— Select Class —</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Step 3 --}}
                    <div id="step3Block" class="mt-3 d-none">
                        <div class="eyebrow mb-1">Step 3 of 6</div>
                        <label class="form-label fw-semibold">Section</label>
                        <select id="sectionSelect" class="form-select" disabled>
                            <option value="">— All Sections —</option>
                        </select>
                        <div class="form-text">Optional — leave blank to include all sections.</div>
                    </div>

                    {{-- Step 4 --}}
                    <div id="step4Block" class="mt-3 d-none">
                        <div class="eyebrow mb-1">Step 4 of 6</div>
                        <label class="form-label fw-semibold">Exam</label>
                        <select id="examSelect" class="form-select" disabled>
                            <option value="">— Select Exam —</option>
                        </select>
                    </div>

                    {{-- Step 5 --}}
                    <div id="step5Block" class="mt-3 d-none">
                        <div class="eyebrow mb-1">Step 5 of 6</div>
                        <label class="form-label fw-semibold">Question Sets</label>
                        <div class="d-flex flex-wrap gap-2" id="setToggleGroup">
                            @foreach (['A','B','C','D','E'] as $s)
                                <label class="stack-item justify-content-start px-3 py-2">
                                    <input type="checkbox" class="set-checkbox" value="{{ $s }}">
                                    <span>Set {{ $s }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text mt-1">Check sets to upload papers for. File inputs appear below.</div>
                    </div>

                    {{-- Step 6 --}}
                    <div id="step6Block" class="mt-3 d-none">
                        <div class="eyebrow mb-1">Step 6 of 6</div>
                        <label class="form-label fw-semibold">Upload Papers per Set</label>
                        <form id="paperUploadForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="set_count" id="hiddenSetCount" value="">
                            <div id="fileInputsContainer" class="vstack gap-3 mb-4"></div>
                            <button type="submit" class="btn btn-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-cloud-upload me-1" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 1.783 5.518 4.184A4 4 0 0 1 16 8a4 4 0 0 1-4 4H5a5 5 0 1 1 .406-9.658"/>
                                    <path fill-rule="evenodd" d="M8.354 5.146a.5.5 0 0 0-.708 0l-2.5 2.5a.5.5 0 1 0 .708.708L7.5 6.707V14.5a.5.5 0 0 0 1 0V6.707l1.646 1.647a.5.5 0 0 0 .708-.708z"/>
                                </svg>
                                Upload Papers
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        {{-- Right: Preview --}}
        <div class="col-xl-7">
            <div class="card app-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <span class="eyebrow">Auto Assignment</span>
                            <h2 class="h5 mb-0">Student &rarr; Set Preview</h2>
                        </div>
                        <div id="setLegend" class="d-flex gap-2 flex-wrap"></div>
                    </div>
                    <div id="previewPlaceholder" class="text-body-secondary py-4 text-center">
                        Complete steps 1&ndash;5 to preview student set assignments.
                    </div>
                    <div id="previewTableWrap" class="d-none">
                        <div class="table-responsive" style="max-height:460px;overflow-y:auto;">
                            <table class="table table-sm mb-0">
                                <thead class="table-light sticky-top">
                                    <tr><th>#</th><th>Roll No</th><th>Student</th><th>Set</th></tr>
                                </thead>
                                <tbody id="previewTableBody"></tbody>
                            </table>
                        </div>
                        <div class="mt-2 small text-body-secondary" id="previewSummary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const ALL_SETS   = ['A','B','C','D','E'];
    const SET_COLORS = { A:'#2563eb', B:'#16a34a', C:'#d97706', D:'#7c3aed', E:'#dc2626' };

    const examTypeSelect      = document.getElementById('examTypeSelect');
    const classSelect         = document.getElementById('classSelect');
    const sectionSelect       = document.getElementById('sectionSelect');
    const examSelect          = document.getElementById('examSelect');
    const setToggleGroup      = document.getElementById('setToggleGroup');
    const fileInputsContainer = document.getElementById('fileInputsContainer');
    const hiddenSetCount      = document.getElementById('hiddenSetCount');
    const paperUploadForm     = document.getElementById('paperUploadForm');
    const previewPlaceholder  = document.getElementById('previewPlaceholder');
    const previewTableWrap    = document.getElementById('previewTableWrap');
    const previewTableBody    = document.getElementById('previewTableBody');
    const previewSummary      = document.getElementById('previewSummary');
    const setLegend           = document.getElementById('setLegend');

    let previewTimer  = null;
    let currentExamId = null;

    function showBlock(id) { document.getElementById(id).classList.remove('d-none'); }
    function hideBlock(id) { document.getElementById(id).classList.add('d-none'); }
    function setStep(n) {
        document.querySelectorAll('.step-item').forEach(el => {
            const s = parseInt(el.dataset.step);
            el.classList.remove('active','done');
            if (s < n)   el.classList.add('done');
            if (s === n) el.classList.add('active');
        });
    }
    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function getSelectedSets() {
        return ALL_SETS.filter(s => setToggleGroup.querySelector('.set-checkbox[value="'+s+'"]')?.checked);
    }

    // Step 1: Exam Type
    examTypeSelect.addEventListener('change', function () {
        if (!this.value) return;
        classSelect.disabled = false;
        classSelect.value    = '';
        resetFrom(3);
        showBlock('step2Block');
        setStep(2);
    });

    // Step 2: Class
    classSelect.addEventListener('change', function () {
        if (!this.value) return;
        resetFrom(4);
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled  = true;

        fetch('{{ route("exam-papers.class-sections") }}?class_id=' + this.value, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(sections => {
            sectionSelect.innerHTML = '<option value="">— All Sections —</option>'
                + sections.map(s => '<option value="'+s.id+'">'+esc(s.name)+'</option>').join('');
            sectionSelect.disabled  = false;
            showBlock('step3Block');
            setStep(3);
            loadExams();
        });
    });

    // Step 3: Section
    sectionSelect.addEventListener('change', function () {
        loadExams();
    });

    function loadExams() {
        const classId  = classSelect.value;
        const examType = examTypeSelect.value;
        if (!classId) return;

        examSelect.innerHTML = '<option value="">Loading...</option>';
        examSelect.disabled  = true;
        resetFrom(5);

        fetch('{{ route("exam-papers.class-exams") }}?class_id='+classId+'&exam_type='+encodeURIComponent(examType), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(exams => {
            if (!exams.length) {
                examSelect.innerHTML = '<option value="">No exams found for this type</option>';
            } else {
                examSelect.innerHTML = '<option value="">— Select Exam —</option>'
                    + exams.map(e => '<option value="'+e.id+'" data-sets="'+esc(JSON.stringify(e.question_sets||[]))+'">'+esc(e.name)+'</option>').join('');
            }
            examSelect.disabled = false;
            showBlock('step4Block');
            setStep(4);
        });
    }

    // Step 4: Exam
    examSelect.addEventListener('change', function () {
        if (!this.value) { resetFrom(5); setStep(4); return; }
        currentExamId = this.value;
        paperUploadForm.action = '/exam-builder/'+currentExamId+'/papers';

        // Pre-tick sets already on this exam
        let savedSets = [];
        try { savedSets = JSON.parse(this.options[this.selectedIndex].dataset.sets || '[]'); } catch(e){}
        setToggleGroup.querySelectorAll('.set-checkbox').forEach(cb => {
            cb.checked = savedSets.includes(cb.value);
        });

        showBlock('step5Block');
        setStep(5);
        renderFileInputs();
        schedulePreview();
    });

    // Step 5: Set Checkboxes
    setToggleGroup.querySelectorAll('.set-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            renderFileInputs();
            schedulePreview();
        });
    });

    function renderFileInputs() {
        const sets = getSelectedSets();
        fileInputsContainer.innerHTML = '';
        if (!sets.length) { hideBlock('step6Block'); return; }

        hiddenSetCount.value = sets.length;
        sets.forEach(setCode => {
            const div = document.createElement('div');
            div.className = 'file-upload-card';
            div.innerHTML =
                '<div class="d-flex align-items-center gap-2 mb-2">'
                + '<span class="set-badge set-'+setCode+'">'+setCode+'</span>'
                + '<span class="fw-semibold">Set '+setCode+' Question Paper</span>'
                + '<span class="text-body-secondary small ms-auto">PDF / DOC / IMG</span>'
                + '</div>'
                + '<input type="file" name="file_'+setCode+'" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png">';
            fileInputsContainer.appendChild(div);
        });
        showBlock('step6Block');
        setStep(6);
    }

    // Reset steps from given step number onward
    function resetFrom(fromStep) {
        if (fromStep <= 3) {
            sectionSelect.innerHTML = '<option value="">— All Sections —</option>';
            sectionSelect.disabled  = true;
            hideBlock('step3Block');
        }
        if (fromStep <= 4) {
            examSelect.innerHTML = '<option value="">—</option>';
            examSelect.disabled  = true;
            hideBlock('step4Block');
            currentExamId = null;
        }
        if (fromStep <= 5) {
            setToggleGroup.querySelectorAll('.set-checkbox').forEach(cb => cb.checked = false);
            hideBlock('step5Block');
        }
        if (fromStep <= 6) {
            fileInputsContainer.innerHTML = '';
            hideBlock('step6Block');
        }
        clearPreview();
    }

    // Preview
    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(loadPreview, 350);
    }

    function clearPreview() {
        clearTimeout(previewTimer);
        previewPlaceholder.textContent = 'Complete steps 1\u20135 to preview student set assignments.';
        previewPlaceholder.classList.remove('d-none');
        previewTableWrap.classList.add('d-none');
        setLegend.innerHTML = '';
    }

    function loadPreview() {
        const sets      = getSelectedSets();
        const sectionId = sectionSelect.value;
        if (!currentExamId || !sets.length) { clearPreview(); return; }

        previewPlaceholder.textContent = 'Loading...';
        previewPlaceholder.classList.remove('d-none');
        previewTableWrap.classList.add('d-none');

        const p = new URLSearchParams({ exam_id: currentExamId, set_count: sets.length, section_id: sectionId });
        fetch('{{ route("exam-papers.assignment-preview") }}?' + p, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(rows => {
            previewPlaceholder.classList.add('d-none');
            if (!rows.length) {
                previewPlaceholder.textContent = 'No students found.';
                previewPlaceholder.classList.remove('d-none');
                return;
            }
            setLegend.innerHTML = sets.map(s =>
                '<span class="badge" style="background:'+SET_COLORS[s]+';font-size:0.78rem;">Set '+s+'</span>'
            ).join('');
            previewTableBody.innerHTML = rows.map((row, idx) =>
                '<tr class="assignment-row-'+row.set+'">'
                + '<td class="text-body-secondary small">'+(idx+1)+'</td>'
                + '<td><code class="small">'+esc(row.roll_no||'—')+'</code></td>'
                + '<td>'+esc(row.name)+'</td>'
                + '<td><span class="set-badge set-'+row.set+'" style="width:26px;height:26px;font-size:0.72rem;">'+row.set+'</span></td>'
                + '</tr>'
            ).join('');
            const counts = {};
            sets.forEach(s => counts[s] = 0);
            rows.forEach(r => counts[r.set] = (counts[r.set]||0)+1);
            previewSummary.textContent = 'Total: '+rows.length+' students  ·  '+sets.map(s=>'Set '+s+': '+counts[s]).join(' · ');
            previewTableWrap.classList.remove('d-none');
        })
        .catch(() => {
            previewPlaceholder.textContent = 'Failed to load preview.';
            previewPlaceholder.classList.remove('d-none');
        });
    }

})();
</script>
@endpush