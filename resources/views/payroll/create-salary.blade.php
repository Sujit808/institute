@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Add Staff Salary</h2>
    <form method="POST" action="{{ route('payroll.salary.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Staff</label>
            <select name="employee_id" class="form-select" required>
                <option value="">Select Staff</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->user->name ?? '-' }} ({{ $employee->designation }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Month</label>
            <input type="month" name="salary_month" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Base Salary</label>
            <input type="number" name="gross_salary" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deductions</label>
            <input type="number" name="deductions" class="form-control" step="0.01" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Net Salary</label>
            <input type="number" name="net_salary" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Provident Fund (PF)</label>
            <input type="number" name="pf" class="form-control" step="0.01" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">ESI</label>
            <input type="number" name="esi" class="form-control" step="0.01" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">TDS</label>
            <input type="number" name="tds" class="form-control" step="0.01" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Custom Components (JSON)</label>
            <textarea name="custom_components" class="form-control" rows="2" placeholder='{"HRA": 2000, "Bonus": 1000}'></textarea>
            <small class="text-muted">Enter as JSON: {"Component Name": amount, ...}</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Save Salary</button>
        <a href="{{ route('payroll.salary.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
