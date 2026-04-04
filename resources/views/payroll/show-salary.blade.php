@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Salary Details</h2>
    <div class="mb-3">
        <strong>Staff:</strong> {{ $salary->employee->user->name ?? '-' }}<br>
        <strong>Designation:</strong> {{ $salary->employee->designation ?? '-' }}<br>
        <strong>Month:</strong> {{ $salary->salary_month }}<br>
        <strong>Base Salary:</strong> {{ $salary->gross_salary }}<br>
        <strong>Deductions:</strong> {{ $salary->deductions }}<br>
        <strong>Net Salary:</strong> {{ $salary->net_salary }}<br>
        <strong>Status:</strong> {{ ucfirst($salary->status) }}<br>
        <strong>Provident Fund (PF):</strong> {{ $salary->pf ?? 0 }}<br>
        <strong>ESI:</strong> {{ $salary->esi ?? 0 }}<br>
        <strong>TDS:</strong> {{ $salary->tds ?? 0 }}<br>
        <strong>Custom Components:</strong>
        @if(is_array($salary->custom_components))
            <ul>
            @foreach($salary->custom_components as $key => $val)
                <li>{{ $key }}: {{ $val }}</li>
            @endforeach
            </ul>
        @elseif($salary->custom_components)
            <pre>{{ json_encode($salary->custom_components, JSON_PRETTY_PRINT) }}</pre>
        @else
            <span>-</span>
        @endif
    </div>
    <div class="mb-3">
        @if($salary->payslip)
            <a href="{{ route('payroll.salary.payslip.download', $salary->id) }}" class="btn btn-success">Download Payslip</a>
        @else
            <form method="POST" action="{{ route('payroll.salary.payslip.generate', $salary->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Generate Payslip</button>
            </form>
        @endif
        @if($salary->payslip && $salary->employee->user && $salary->employee->user->email)
            <form method="POST" action="{{ route('payroll.salary.email-payslip', $salary->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-info">Email Payslip to Staff</button>
            </form>
        @endif
        <a href="{{ route('payroll.salary.index') }}" class="btn btn-secondary">Back</a>
    </div>
    @if(isset($revisions) && $revisions->count())
        <div class="mt-4">
            <h5>Salary Revision History</h5>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Date</th><th>User</th><th>Old Values</th><th>New Values</th></tr></thead>
                <tbody>
                    @foreach($revisions as $rev)
                        <tr>
                            <td>{{ $rev->created_at }}</td>
                            <td>{{ $rev->user->name ?? '-' }}</td>
                            <td><pre>{{ json_encode($rev->old_values, JSON_PRETTY_PRINT) }}</pre></td>
                            <td><pre>{{ json_encode($rev->new_values, JSON_PRETTY_PRINT) }}</pre></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
