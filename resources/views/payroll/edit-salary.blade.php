@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Edit Staff Salary</h2>
    <form method="POST" action="{{ route('payroll.salary.update', $salary->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Staff</label>
            <select name="employee_id" class="form-select" required disabled>
                <option value="">Select Staff</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @if($salary->employee_id == $employee->id) selected @endif>{{ $employee->user->name ?? '-' }} ({{ $employee->designation }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Month</label>
            <input type="month" name="salary_month" class="form-control" value="{{ $salary->salary_month }}" required disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Base Salary</label>
            <input type="number" name="gross_salary" class="form-control" step="0.01" value="{{ $salary->gross_salary }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deductions</label>
            <input type="number" name="deductions" class="form-control" step="0.01" value="{{ $salary->deductions }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Net Salary</label>
            <input type="number" name="net_salary" class="form-control" step="0.01" value="{{ $salary->net_salary }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Provident Fund (PF)</label>
            <input type="number" name="pf" class="form-control" step="0.01" value="{{ $salary->pf ?? 0 }}">
        </div>
        <div class="mb-3">
            <label class="form-label">ESI</label>
            <input type="number" name="esi" class="form-control" step="0.01" value="{{ $salary->esi ?? 0 }}">
        </div>
        <div class="mb-3">
            <label class="form-label">TDS</label>
            <input type="number" name="tds" class="form-control" step="0.01" value="{{ $salary->tds ?? 0 }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Custom Components (JSON)</label>
            <textarea name="custom_components" class="form-control" rows="2" placeholder='{"HRA": 2000, "Bonus": 1000}'>{{ $salary->custom_components ? json_encode($salary->custom_components) : '' }}</textarea>
            <small class="text-muted">Enter as JSON: {"Component Name": amount, ...}</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="pending" @if($salary->status == 'pending') selected @endif>Pending</option>
                <option value="paid" @if($salary->status == 'paid') selected @endif>Paid</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Update Salary</button>
        <a href="{{ route('payroll.salary.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
