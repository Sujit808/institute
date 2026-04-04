@extends('layouts.app')

@section('content')
@php
    $paymentPercentage = max(0, min(100, (int) $summary['percentage']));
    $pendingPercentage = max(0, 100 - $paymentPercentage);
    $dueTextClass = $summary['due'] > 0 ? 'text-warning' : 'text-success';
    $paymentStateClass = $summary['percentage'] >= 100 ? 'text-success' : 'text-warning';
@endphp
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Fee Management</span>
            <h1 class="h3 mb-1">Fee Status</h1>
            <p class="text-body-secondary mb-0">View your fee payment status, receipts, and payment history.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Total Fee</span>
                    <h4 class="mb-2">₹{{ number_format($summary['total'], 2) }}</h4>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar js-fee-progress bg-primary" data-progress="{{ $paymentPercentage }}"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Amount Paid</span>
                    <h4 class="mb-0" style="color: #28a745;">₹{{ number_format($summary['paid'], 2) }}</h4>
                    <small class="text-body-secondary">{{ $summary['percentage'] }}% paid</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card">
                <div class="card-body p-4">
                    <span class="text-body-secondary small">Amount Due</span>
                    <h4 class="mb-0 {{ $dueTextClass }}">₹{{ number_format($summary['due'], 2) }}</h4>
                    <small class="text-body-secondary">{{ $pendingPercentage }}% pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card app-card text-center">
                <div class="card-body p-4">
                    <div class="fs-1 mb-2 {{ $paymentStateClass }}">
                        {{ $summary['percentage'] }}%
                    </div>
                    <small class="text-body-secondary">
                        @if($summary['percentage'] >= 100)
                            <span class="text-success">Fully Paid</span>
                        @else
                            <span class="text-warning">Pending</span>
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Details Table -->
    <div class="card app-card">
        <div class="card-header border-0 bg-transparent">
            <h5 class="mb-0">Fee Breakdown</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fee Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fees as $fee)
                        @php
                            $feePaidActual = (float) ($fee->payments->sum('amount') ?: $fee->paid_amount);
                            $canDownloadReceipt = $feePaidActual > 0;
                            $feeBalance = max(0, (float) $fee->amount - $feePaidActual);
                            $feeStatus = $feeBalance <= 0
                                ? 'paid'
                                : ($feePaidActual > 0 ? 'partial' : 'pending');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-500">{{ $fee->fee_type ?? 'Fee' }}</div>
                                @if($fee->receipt_no)
                                    <small class="text-body-secondary">Receipt: {{ $fee->receipt_no }}</small>
                                @endif
                            </td>
                            <td class="text-end">₹{{ number_format($fee->amount, 2) }}</td>
                            <td class="text-end fw-500" style="color: #28a745;">₹{{ number_format($feePaidActual, 2) }}</td>
                            <td class="text-end">₹{{ number_format($feeBalance, 2) }}</td>
                            <td>
                                @if($feeStatus === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($feeStatus === 'partial')
                                    <span class="badge bg-warning">Partial</span>
                                @else
                                    <span class="badge bg-danger">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($fee->due_date)
                                    <small>{{ $fee->due_date->format('d M Y') }}</small>
                                @else
                                    <small class="text-body-secondary">-</small>
                                @endif
                            </td>
                            <td>
                                @if($canDownloadReceipt)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('student.fees.receipt', $fee->id) }}">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF Receipt
                                    </a>
                                @else
                                    <span class="text-body-secondary small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-4">
                                No fee records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment History -->
    @if($fees->count() > 0)
        <div class="card app-card mt-4">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0">Payment Method</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($fees as $fee)
                        @if($fee->payment_mode)
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <small class="text-body-secondary">{{ $fee->fee_type ?? 'Fee' }}</small>
                                    <div class="fw-500 mt-1">
                                        <i class="bi bi-credit-card"></i>
                                        {{ ucfirst(str_replace('_', ' ', $fee->payment_mode)) }}
                                    </div>
                                    @if($feePaidActual > 0)
                                        <small class="text-success">₹{{ number_format($feePaidActual, 2) }} received</small>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="card app-card mt-4">
        <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Payment Receipt History</h5>
            <span class="small text-body-secondary">Each payment can be downloaded separately</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Fee Type</th>
                        <th class="text-end">Amount</th>
                        <th>Mode</th>
                        <th>Receipt No</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</td>
                            <td>
                                <div class="fw-500">{{ $payment->fee?->fee_type ?? 'Fee Payment' }}</div>
                                @if($payment->remarks)
                                    <small class="text-body-secondary">{{ $payment->remarks }}</small>
                                @endif
                            </td>
                            <td class="text-end fw-500">₹{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode ?? 'cash')) }}</td>
                            <td>{{ $payment->receipt_no ?: 'Auto-generated' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('student.payments.receipt', $payment->id) }}">
                                    <i class="bi bi-file-earmark-pdf"></i> Download
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">No payment history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-fee-progress').forEach(function (element) {
            element.style.width = element.dataset.progress + '%';
        });
    });
</script>
@endsection
