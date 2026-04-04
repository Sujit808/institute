@extends('layouts.app')

@section('content')
<div class="container py-4">
	<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
		<div>
			<h2 class="mb-1">Staff Payroll & Salary Management</h2>
			<div class="text-muted">Manage, filter, and generate payslips for all staff in one place.</div>
		</div>
		<a href="{{ route('payroll.salary.create') }}" class="btn btn-primary">+ Add Salary Record</a>
	</div>

	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card shadow-sm border-0">
				<div class="card-body py-3">
					<div class="fw-semibold text-secondary">Total Staff</div>
					<div class="fs-4">{{ $employees->count() }}</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm border-0">
				<div class="card-body py-3">
					<div class="fw-semibold text-secondary">Total Records</div>
					<div class="fs-4">{{ $salaries->total() ?? $salaries->count() }}</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm border-0">
				<div class="card-body py-3">
					<div class="fw-semibold text-secondary">Paid</div>
					<div class="fs-4 text-success">{{ $salaries->where('status','paid')->count() }}</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm border-0">
				<div class="card-body py-3">
					<div class="fw-semibold text-secondary">Pending</div>
					<div class="fs-4 text-danger">{{ $salaries->where('status','pending')->count() }}</div>
				</div>
			</div>
		</div>
	</div>

	<form method="GET" class="row g-3 mb-3 align-items-end">
		<div class="col-md-3">
			<label class="form-label">Staff</label>
			<select name="employee_id" class="form-select">
				<option value="">All</option>
				@foreach($employees as $employee)
					<option value="{{ $employee->id }}" @if(request('employee_id') == $employee->id) selected @endif>{{ $employee->user->name ?? '-' }}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-2">
			<label class="form-label">Month</label>
			<input type="month" name="salary_month" class="form-control" value="{{ request('salary_month') }}">
		</div>
		<div class="col-md-2">
			<label class="form-label">Status</label>
			<select name="status" class="form-select">
				<option value="">All</option>
				<option value="pending" @if(request('status') == 'pending') selected @endif>Pending</option>
				<option value="paid" @if(request('status') == 'paid') selected @endif>Paid</option>
			</select>
		</div>
		<div class="col-md-3">
			<label class="form-label">Search Staff</label>
			<input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Enter name">
		</div>
		<div class="col-md-2">
			<button type="submit" class="btn btn-outline-primary w-100">Filter</button>
		</div>
	</form>
	<form method="POST" action="{{ route('payroll.salary.bulk') }}" class="card shadow-sm border-0">
		@csrf
		<div class="card-body">
			<div class="mb-3 d-flex flex-wrap gap-2">
				<button type="submit" name="action" value="mark_paid" class="btn btn-success btn-sm">Mark as Paid</button>
				<button type="submit" name="action" value="export" class="btn btn-outline-primary btn-sm">Export Selected</button>
				<button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected records?')">Delete Selected</button>
				<button type="submit" name="action" value="generate_payslips" class="btn btn-secondary btn-sm">Generate Payslips</button>
			</div>
			<div class="table-responsive">
				<table class="table table-hover align-middle">
					<thead class="table-light">
						<tr>
							<th style="width:32px"><input type="checkbox" id="select-all"></th>
							<th>Staff Name</th>
							<th>Designation</th>
							<th>Base Salary</th>
							<th>PF</th>
							<th>ESI</th>
							<th>TDS</th>
							<th>Custom Components</th>
							<th>Deductions</th>
							<th>Net Salary</th>
							<th>Month</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						@if($salaries->count())
							@foreach($salaries as $salary)
								<tr class="@if($salary->status=='paid') table-success @elseif($salary->status=='pending') table-warning @endif">
									<td><input type="checkbox" name="ids[]" value="{{ $salary->id }}"></td>
									<td>{{ $salary->employee->user->name ?? '-' }}</td>
									<td>{{ $salary->employee->designation ?? '-' }}</td>
									<td>{{ $salary->gross_salary ?? 0 }}</td>
									<td>{{ $salary->pf ?? 0 }}</td>
									<td>{{ $salary->esi ?? 0 }}</td>
									<td>{{ $salary->tds ?? 0 }}</td>
									<td>
										@if(is_array($salary->custom_components))
											<ul class="mb-0 ps-3">
												@foreach($salary->custom_components as $key => $val)
													<li><span class="text-muted">{{ $key }}</span>: <span class="fw-semibold">{{ $val }}</span></li>
												@endforeach
											</ul>
										@elseif($salary->custom_components)
											<span>{{ $salary->custom_components }}</span>
										@else
											-
										@endif
									</td>
									<td>{{ $salary->deductions }}</td>
									<td class="fw-bold">{{ $salary->net_salary }}</td>
									<td>{{ $salary->salary_month }}</td>
									<td>
										<span class="badge bg-@if($salary->status=='paid')success@elseif($salary->status=='pending')warning text-dark@else-secondary@endif">{{ ucfirst($salary->status) }}</span>
									</td>
									<td>
										<a href="{{ route('payroll.salary.show', $salary->id) }}" class="btn btn-sm btn-info">View</a>
										<a href="{{ route('payroll.salary.edit', $salary->id) }}" class="btn btn-sm btn-warning">Edit</a>
									</td>
								</tr>
							@endforeach
						@else
							<tr><td colspan="13" class="text-center text-muted">No salary records found.</td></tr>
						@endif
					</tbody>
				</table>
			</div>
		</div>
	</form>
	<script>
		document.getElementById('select-all').addEventListener('change', function() {
			const checked = this.checked;
			document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = checked);
		});
	</script>
</div>
@endsection
