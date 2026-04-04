<div class="table-responsive">
    <table class="table align-middle app-table mb-0">
        <thead>
            <tr>
                @foreach ($moduleConfig['table_columns'] as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
                @if ($moduleKey === 'exams')
                    <th>Set Papers</th>
                @endif
                @unless (! empty($moduleConfig['readonly']))
                    <th class="text-end">Actions</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                @php
                    $recordId = (int) data_get($record, 'id', 0);
                @endphp
                <tr>
                    @foreach ($moduleConfig['table_columns'] as $column)
                        @php
                            $value = data_get($record, $column['key']);
                            $feeAmount = $moduleKey === 'fees' ? (float) data_get($record, 'amount', 0) : 0;
                            $actualPaidAmount = $moduleKey === 'fees'
                                ? (float) (collect($record->payments ?? [])->sum('amount') ?: data_get($record, 'paid_amount', 0))
                                : 0;
                            $remainingDueAmount = $moduleKey === 'fees' ? max(0, $feeAmount - $actualPaidAmount) : 0;
                            $paymentProgress = $moduleKey === 'fees' && $feeAmount > 0
                                ? min(100, round(($actualPaidAmount / $feeAmount) * 100))
                                : 0;
                            $feePaymentsPayload = $moduleKey === 'fees'
                                ? collect($record->payments ?? [])
                                    ->sortByDesc(fn ($payment) => sprintf('%s-%010d', (string) $payment->payment_date, $payment->id))
                                    ->values()
                                    ->map(function ($payment) {
                                        return [
                                            'date' => optional($payment->payment_date)->format('d M Y') ?: 'N/A',
                                            'receipt_no' => $payment->receipt_no ?: 'N/A',
                                            'mode' => strtoupper((string) ($payment->payment_mode ?: 'N/A')),
                                            'remarks' => $payment->remarks ?: '-',
                                            'amount' => number_format((float) $payment->amount, 2, '.', ''),
                                        ];
                                    })
                                    ->all()
                                : [];
                        @endphp
                        <td>
                            @if ($moduleKey === 'fees' && $column['key'] === 'amount')
                                <div class="fee-amount-pill">Total Rs {{ number_format($feeAmount, 2) }}</div>
                                <div class="small text-body-secondary mt-1 text-capitalize">{{ str_replace('_', ' ', (string) data_get($record, 'fee_type')) }}</div>
                            @elseif ($moduleKey === 'fees' && $column['key'] === 'paid_amount')
                                <div class="fee-paid-pill">Paid Rs {{ number_format($actualPaidAmount, 2) }}</div>
                                <div class="progress mt-2" style="height: 8px; min-width: 130px;">
                                    <div class="progress-bar {{ $paymentProgress >= 100 ? 'bg-success' : ($paymentProgress > 0 ? 'bg-warning' : 'bg-secondary') }}" data-progress-width="{{ $paymentProgress }}"></div>
                                </div>
                                <div class="small text-body-secondary mt-1">{{ $paymentProgress }}% settled</div>
                            @elseif ($moduleKey === 'fees' && $column['key'] === 'due_date')
                                <div>{{ optional(data_get($record, 'due_date'))->format('d M Y') ?: '-' }}</div>
                                <div class="mt-2"><span class="fee-due-pill">Due Rs {{ number_format($remainingDueAmount, 2) }}</span></div>
                            @elseif (($column['type'] ?? null) === 'badge')
                                @php
                                    $rawValue = strtolower((string) $value);
                                    $badgeClass = 'text-bg-light border text-capitalize';

                                    if ($moduleKey === 'leaves' && $column['key'] === 'status') {
                                        if ($rawValue === 'rejected' || $rawValue === 'reject') {
                                            $badgeClass = 'text-danger bg-danger-subtle border border-danger-subtle text-capitalize';
                                        } elseif ($rawValue === 'approved' || $rawValue === 'approve') {
                                            $badgeClass = 'text-success bg-success-subtle border border-success-subtle text-capitalize';
                                        } elseif ($rawValue === 'pending') {
                                            $badgeClass = 'text-primary bg-primary-subtle border border-primary-subtle text-capitalize';
                                        }
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', (string) $value) }}</span>
                            @elseif (is_array($value))
                                {{ implode(', ', $value) }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                    @if ($moduleKey === 'exams')
                        @php($papersBySet = collect($record->papers ?? [])->keyBy('set_code'))
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $setCode)
                                    @php($paper = $papersBySet->get($setCode))
                                    @php($extension = $paper ? strtolower(pathinfo((string) $paper->file_path, PATHINFO_EXTENSION)) : null)
                                    <div class="border rounded-3 px-2 py-1 small" style="min-width: 68px;">
                                        <div class="fw-semibold mb-1">{{ $setCode }}</div>
                                        @if ($paper)
                                            @if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                                                <a href="{{ asset('storage/'.$paper->file_path) }}" target="_blank" rel="noopener noreferrer" title="{{ $paper->title }}">
                                                    <img src="{{ asset('storage/'.$paper->file_path) }}" alt="{{ $paper->title }}" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                                </a>
                                            @else
                                                <a href="{{ asset('storage/'.$paper->file_path) }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" title="{{ $paper->title }}">
                                                    <span class="badge text-bg-light border">{{ strtoupper($extension ?: 'FILE') }}</span>
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-body-secondary">-</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    @endif
                    @unless (! empty($moduleConfig['readonly']))
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if ($moduleKey === 'exams' && $recordId > 0)
                                    <a href="{{ route('exam-builder.show', $recordId) }}" class="btn btn-outline-secondary">Builder</a>
                                    <a href="{{ route('exam-attempts.review.index', ['exam_id' => $recordId]) }}" class="btn btn-outline-warning">Attempts</a>
                                @endif
                                @if ($moduleKey === 'fees' && $recordId > 0 && $actualPaidAmount > 0)
                                    <a href="{{ route('fees.receipt.download', $recordId) }}" class="btn btn-outline-success">Receipt</a>
                                @endif
                                @if ($moduleKey === 'fees' && $recordId > 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#feeDetailsModal"
                                        data-fee-student="{{ data_get($record, 'student.full_name', 'Student') }}"
                                        data-fee-type="{{ ucfirst((string) data_get($record, 'fee_type', 'fee')) }}"
                                        data-fee-total="{{ number_format($feeAmount, 2, '.', '') }}"
                                        data-fee-paid="{{ number_format($actualPaidAmount, 2, '.', '') }}"
                                        data-fee-due="{{ number_format($remainingDueAmount, 2, '.', '') }}"
                                        data-fee-payments='@json($feePaymentsPayload)'>
                                        Details
                                    </button>
                                @endif
                                @if ($moduleKey === 'students' && $recordId > 0)
                                    <a href="{{ route('students.calendar', $recordId) }}" class="btn btn-outline-info">Calendar</a>
                                @endif
                                @if ($moduleKey === 'leaves' && $recordId > 0)
                                    @php($leaveStatus = strtolower((string) data_get($record, 'status')))
                                    @if ($leaveStatus === 'pending')
                                        <button type="button" class="btn btn-outline-success" data-leave-quick-action data-id="{{ $recordId }}" data-status="approved">Approve</button>
                                        <button type="button" class="btn btn-outline-danger" data-leave-quick-action data-id="{{ $recordId }}" data-status="rejected">Reject</button>
                                    @endif
                                @endif
                                @if ($recordId > 0)
                                    <button type="button" class="btn btn-outline-primary" data-edit-record data-module="{{ $moduleKey }}" data-id="{{ $recordId }}">Edit</button>
                                    <button type="button" class="btn btn-outline-danger" data-delete-record data-module="{{ $moduleKey }}" data-id="{{ $recordId }}">Delete</button>
                                @else
                                    <button type="button" class="btn btn-outline-secondary" disabled>Invalid ID</button>
                                @endif
                            </div>
                        </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($moduleConfig['table_columns']) + ($moduleKey === 'exams' ? 1 : 0) + (empty($moduleConfig['readonly']) ? 1 : 0) }}" class="text-center py-5 text-body-secondary">
                        No {{ strtolower($moduleConfig['title']) }} found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (!empty($pagination))
    <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top small text-body-secondary">
        <div>
            Showing <strong>{{ $pagination['from'] ?? 0 }}</strong> to <strong>{{ $pagination['to'] ?? 0 }}</strong> of <strong>{{ $pagination['total'] ?? 0 }}</strong> results
        </div>
        @if ($pagination['last_page'] > 1)
            <nav aria-label="Table pagination">
                <ul class="pagination mb-0 pagination-sm">
                    @if ($pagination['current_page'] > 1)
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="1" data-pagination-link>First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="{{ $pagination['current_page'] - 1 }}" data-pagination-link>Previous</a>
                        </li>
                    @endif

                    @for ($page = max(1, $pagination['current_page'] - 2); $page <= min($pagination['last_page'], $pagination['current_page'] + 2); $page++)
                        <li class="page-item {{ $page === $pagination['current_page'] ? 'active' : '' }}">
                            <a class="page-link" href="#" data-page="{{ $page }}" data-pagination-link>{{ $page }}</a>
                        </li>
                    @endfor

                    @if ($pagination['current_page'] < $pagination['last_page'])
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="{{ $pagination['current_page'] + 1 }}" data-pagination-link>Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="{{ $pagination['last_page'] }}" data-pagination-link>Last</a>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
@endif

