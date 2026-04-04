@extends('layouts.app')

@section('content')
<style>
    .fs-shell { background:#f8fbff; border:1px solid #dbe7f3; border-radius:14px; padding:22px; }
    .fs-card  { background:#fff; border:1px solid #dce7f3; border-radius:12px; }
    .fs-head  { background:linear-gradient(180deg,#f4f8fc,#ecf2f8); color:#334155; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .badge-active   { background:#d1fae5; color:#065f46; font-size:.75rem; }
    .badge-inactive { background:#fee2e2; color:#991b1b; font-size:.75rem; }
    .amount-chip { font-weight:700; color:#1d67c1; font-size:.95rem; }
    .autogen-panel { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:12px; padding:18px; }
</style>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
    <div>
        <span class="eyebrow">Finance</span>
        <h1 class="h3 mb-1">Fee Structure</h1>
        <p class="text-body-secondary mb-0">Define fee heads per class and auto-generate fee records for all students.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeStructureModal">
        <i class="bi bi-plus-circle me-1"></i>Add Fee Head
    </button>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="fs-shell">

    {{-- Filter --}}
    <form method="GET" action="{{ route('fee-structures.index') }}" class="fs-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}" @selected($classId === $cls->id)>{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:.8rem">Academic Year</label>
                <select name="academic_year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </div>
    </form>

    {{-- Auto-generate panel --}}
    <div class="autogen-panel mb-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Auto-generate Fee Records</h6>
        <p class="text-secondary mb-3" style="font-size:.87rem">Ek class aur academic year choose karo — us class ke sabhi active students ke liye fee records automatically create ho jayenge (jo already exist hon wo skip honge).</p>
        <form method="POST" action="{{ route('fee-structures.auto-generate') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem">Class <span class="text-danger">*</span></label>
                <select name="academic_class_id" class="form-select form-select-sm" required>
                    <option value="">Select Class</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:.8rem">Academic Year <span class="text-danger">*</span></label>
                <input type="text" name="academic_year" class="form-control form-control-sm" placeholder="e.g. 2025-26" required value="{{ $year ?: date('Y').'-'.substr(date('Y')+1,2) }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Fee records generate karne hai? Existing records skip honge.')">
                    <i class="bi bi-lightning me-1"></i>Generate Fee Records
                </button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="fs-card overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="fs-head">
                    <tr>
                        <th>Class</th>
                        <th>Fee Head</th>
                        <th>Label</th>
                        <th>Amount</th>
                        <th>Due Month</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($structures as $s)
                        <tr>
                            <td class="fw-semibold">{{ $s->academicClass?->name ?? '-' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $s->fee_head }}</span></td>
                            <td>{{ $s->fee_label }}</td>
                            <td class="amount-chip">₹{{ number_format($s->amount, 2) }}</td>
                            <td>{{ $s->due_month ? \App\Models\FeeStructure::$months[$s->due_month] : 'One-time' }}</td>
                            <td>{{ $s->academic_year }}</td>
                            <td>
                                <span class="badge badge-{{ $s->status }} rounded-pill">{{ ucfirst($s->status) }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal{{ $s->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('fee-structures.destroy', $s->id) }}" class="d-inline"
                                    onsubmit="return confirm('Delete this fee structure entry?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        {{-- Edit Modal --}}
                        <div class="modal fade" id="editModal{{ $s->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('fee-structures.update', $s->id) }}" class="modal-content">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Fee Head</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        @include('fee-structures._form', ['fs' => $s, 'classes' => $classes])
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-clipboard-x fs-2 d-block mb-2 opacity-50"></i>
                                No fee structure entries found. Add one to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addFeeStructureModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('fee-structures.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Fee Head</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('fee-structures._form', ['fs' => null, 'classes' => $classes])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Fee Head</button>
            </div>
        </form>
    </div>
</div>
@endsection
