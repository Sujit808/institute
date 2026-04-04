{{-- Shared form partial for both Add and Edit modals --}}
@php $feeHeads = \App\Models\FeeStructure::$feeHeads; $months = \App\Models\FeeStructure::$months; @endphp

<div class="mb-3">
    <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
    <select name="academic_class_id" class="form-select" required>
        <option value="">Select Class</option>
        @foreach($classes as $cls)
            <option value="{{ $cls->id }}" @selected(old('academic_class_id', $fs?->academic_class_id) == $cls->id)>{{ $cls->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Fee Head <span class="text-danger">*</span></label>
    <select name="fee_head" class="form-select" required id="feeHeadSelect_{{ $fs?->id ?? 'new' }}">
        <option value="">Select Fee Head</option>
        @foreach($feeHeads as $key => $label)
            <option value="{{ $key }}" @selected(old('fee_head', $fs?->fee_head) === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Display Label <span class="text-danger">*</span></label>
    <input type="text" name="fee_label" class="form-control" required
        value="{{ old('fee_label', $fs?->fee_label) }}"
        placeholder="e.g. Monthly Tuition Fee">
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
        <input type="number" name="amount" class="form-control" required min="0" step="0.01"
            value="{{ old('amount', $fs?->amount) }}" placeholder="0.00">
    </div>
    <div class="col-6">
        <label class="form-label fw-semibold">Due Month</label>
        <select name="due_month" class="form-select">
            <option value="">One-time / No due month</option>
            @foreach($months as $num => $name)
                <option value="{{ $num }}" @selected(old('due_month', $fs?->due_month) == $num)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
        <input type="text" name="academic_year" class="form-control" required
            value="{{ old('academic_year', $fs?->academic_year ?? date('Y').'-'.substr(date('Y')+1,2)) }}"
            placeholder="e.g. 2025-26" maxlength="10">
    </div>
    <div class="col-6">
        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <option value="active"   @selected(old('status', $fs?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $fs?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Notes</label>
    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes...">{{ old('notes', $fs?->notes) }}</textarea>
</div>
