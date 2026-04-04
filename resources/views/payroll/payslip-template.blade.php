@extends('layouts.blank')

@section('content')
<div style="max-width:600px;margin:auto;padding:24px;border:1px solid #ccc;font-family:sans-serif;">
    <h2 style="text-align:center;">Salary Payslip</h2>
    <hr>
    <table style="width:100%;margin-bottom:16px;">
        <tr><td><strong>Staff Name:</strong></td><td>{{ $salary->employee->user->name ?? '-' }}</td></tr>
        <tr><td><strong>Designation:</strong></td><td>{{ $salary->employee->designation ?? '-' }}</td></tr>
        <tr><td><strong>Month:</strong></td><td>{{ $salary->salary_month }}</td></tr>
    </table>
    <table style="width:100%;margin-bottom:16px;">
        <tr><td><strong>Base Salary:</strong></td><td>{{ $salary->gross_salary }}</td></tr>
        <tr><td><strong>Deductions:</strong></td><td>{{ $salary->deductions }}</td></tr>
        <tr><td><strong>Net Salary:</strong></td><td>{{ $salary->net_salary }}</td></tr>
        <tr><td><strong>Status:</strong></td><td>{{ ucfirst($salary->status) }}</td></tr>
        <tr><td><strong>Provident Fund (PF):</strong></td><td>{{ $salary->pf ?? 0 }}</td></tr>
        <tr><td><strong>ESI:</strong></td><td>{{ $salary->esi ?? 0 }}</td></tr>
        <tr><td><strong>TDS:</strong></td><td>{{ $salary->tds ?? 0 }}</td></tr>
    </table>
    <h5>Custom Components</h5>
    @if(is_array($salary->custom_components))
        <table style="width:100%;margin-bottom:16px;">
            <thead><tr><th>Component</th><th>Amount</th></tr></thead>
            <tbody>
            @foreach($salary->custom_components as $key => $val)
                <tr><td>{{ $key }}</td><td>{{ $val }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>-</p>
    @endif
    <hr>
    <div style="text-align:center;font-size:12px;color:#888;">Generated on {{ now()->format('d-m-Y H:i') }}</div>
</div>
@endsection
