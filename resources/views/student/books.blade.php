@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Study Materials</span>
            <h1 class="h3 mb-1">Books & Downloads</h1>
            <p class="text-body-secondary mb-0">Download books and study materials assigned to your class.</p>
        </div>
    </div>

    @php
        $materialsBySubject = $materials->groupBy(function ($m) {
            return optional($m->subject)->name ?? 'General Materials';
        });
    @endphp

    @forelse ($materialsBySubject as $subject => $subjectMaterials)
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-folder"></i> {{ $subject }}
            </h5>
            <div class="row g-4">
                @foreach ($subjectMaterials as $material)
                    @php
                        $extension = strtolower((string) pathinfo((string) $material->file_path, PATHINFO_EXTENSION));
                        $cardBorderClass = $extension === 'pdf' ? 'border-danger' : (in_array($extension, ['doc', 'docx'], true) ? 'border-primary' : (in_array($extension, ['xls', 'xlsx'], true) ? 'border-success' : 'border-warning'));
                        $iconClass = $extension === 'pdf' ? 'bi-file-pdf text-danger' : (in_array($extension, ['doc', 'docx'], true) ? 'bi-file-word text-primary' : (in_array($extension, ['xls', 'xlsx'], true) ? 'bi-file-excel text-success' : 'bi-file-earmark text-warning'));
                    @endphp
                    <div class="col-xl-4 col-md-6">
                        <div class="card app-card h-100 transition-all border-start border-4 {{ $cardBorderClass }}">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="eyebrow mb-2">{{ optional($material->subject)->name ?? 'General Material' }}</span>
                                        <h2 class="h5 mb-2">{{ $material->title }}</h2>
                                    </div>
                                    <div class="fs-3">
                                        <i class="bi {{ $iconClass }}"></i>
                                    </div>
                                </div>
                                
                                <p class="text-body-secondary small flex-grow-1">{{ $material->description ?: 'Study material available for download.' }}</p>
                                
                                <div class="small text-body-secondary mb-3">
                                    <div class="mb-1">Class: <strong>{{ optional($material->academicClass)->name ?? 'All Classes' }}</strong></div>
                                    <div>File: <strong>{{ strtoupper(pathinfo($material->file_path, PATHINFO_EXTENSION)) }}</strong></div>
                                </div>
                                
                                <a class="btn btn-primary btn-sm w-100" href="{{ route('student.books.download', $material->id) }}">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card app-card">
            <div class="card-body p-4 text-center text-body-secondary">
                <i class="bi bi-book" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="mt-3 mb-0">No books or study materials available yet.</p>
            </div>
        </div>
    @endforelse
</div>

<style>
    .transition-all {
        transition: all 0.3s ease;
    }
    
    .transition-all:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection