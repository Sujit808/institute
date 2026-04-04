@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Profile Header -->
    <div class="card app-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start gap-4">
                <div class="text-center">
                    <img src="{{ $student->photo ? asset('storage/'.$student->photo) : 'https://placehold.co/160x180/eaf2ff/1167b1?text=Student' }}" alt="Student Photo" class="rounded-4 border" style="width: 160px; height: 180px; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <span class="eyebrow">Student Profile</span>
                    <h1 class="h3 mb-4">{{ $student->full_name }}</h1>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Roll No</div><div class="metric-value">{{ $student->roll_no }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Admission No</div><div class="metric-value">{{ $student->admission_no }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Email</div><div class="metric-value fs-6">{{ $student->email ?: 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Mobile</div><div class="metric-value fs-6">{{ $student->phone ?: $student->guardian_phone ?: 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Class</div><div class="metric-value fs-6">{{ optional($student->academicClass)->name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Section</div><div class="metric-value fs-6">{{ optional($student->section)->name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Blood Group</div><div class="metric-value fs-6">{{ $student->blood_group ?: 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card h-100"><div class="metric-label">Gender</div><div class="metric-value fs-6">{{ $student->gender ? ucfirst($student->gender) : 'N/A' }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Personal Details -->
        <div class="col-lg-6">
            <div class="card app-card h-100">
                <div class="card-header border-0 bg-transparent">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-body-secondary small">Date of Birth</span>
                        <div class="fw-500">{{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-body-secondary small">Admission Date</span>
                        <div class="fw-500">{{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-body-secondary small">Aadhar Number</span>
                        <div class="fw-500">{{ $student->aadhar_number ? substr_replace($student->aadhar_number, 'XXXX XXXX', 0, 8) : 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="text-body-secondary small">Address</span>
                        <div class="fw-500">{{ $student->address ?: 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Details -->
        <div class="col-lg-6">
            <div class="card app-card h-100">
                <div class="card-header border-0 bg-transparent">
                    <h5 class="mb-0">Guardian Information</h5>
                </div>
                <div class="card-body">
                    @php
                        $statusBadgeClass = ($student->status ?? 'active') === 'active' ? 'bg-success' : 'bg-danger';
                    @endphp
                    <div class="mb-3">
                        <span class="text-body-secondary small">Guardian Name</span>
                        <div class="fw-500">{{ $student->guardian_name ?: 'N/A' }}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-body-secondary small">Guardian Phone</span>
                        <div class="fw-500">{{ $student->guardian_phone ?: 'N/A' }}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-body-secondary small">Status</span>
                        <div>
                            <span class="badge {{ $statusBadgeClass }}">
                                {{ ucfirst($student->status ?? 'Active') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mt-2">
        <div class="col-md-3">
            <a href="{{ route('student.attendance') }}" class="text-decoration-none">
                <div class="card app-card text-center py-3">
                    <div class="card-body">
                        <i class="bi bi-calendar-check" style="font-size: 28px; color: #1167b1;"></i>
                        <h6 class="mt-2 mb-0">Attendance</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('student.fees') }}" class="text-decoration-none">
                <div class="card app-card text-center py-3">
                    <div class="card-body">
                        <i class="bi bi-receipt" style="font-size: 28px; color: #ff9500;"></i>
                        <h6 class="mt-2 mb-0">Fees</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('student.results') }}" class="text-decoration-none">
                <div class="card app-card text-center py-3">
                    <div class="card-body">
                        <i class="bi bi-graph-up" style="font-size: 28px; color: #28a745;"></i>
                        <h6 class="mt-2 mb-0">Results</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('student.books') }}" class="text-decoration-none">
                <div class="card app-card text-center py-3">
                    <div class="card-body">
                        <i class="bi bi-book" style="font-size: 28px; color: #6f42c1;"></i>
                        <h6 class="mt-2 mb-0">Study Materials</h6>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection