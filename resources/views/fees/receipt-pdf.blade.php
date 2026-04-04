<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.4;
        }
        .receipt {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 18px;
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }
        .watermark {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        .watermark-name {
            position: absolute;
            top: 44%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 64px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #111827;
            opacity: 0.05;
            white-space: nowrap;
            text-transform: uppercase;
        }
        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 220px;
            object-fit: contain;
            opacity: 0.07;
        }
        .content {
            position: relative;
            z-index: 1;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .school-name {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        .subtitle {
            margin: 4px 0 0 0;
            color: #4b5563;
        }
        .sub-meta {
            margin-top: 6px;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .grid td {
            padding: 6px 0;
            vertical-align: top;
        }
        .label {
            color: #6b7280;
            width: 160px;
        }
        .amount-box {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px;
            margin: 8px 0 14px;
        }
        .details-title {
            font-size: 12px;
            font-weight: 700;
            margin: 12px 0 8px;
            color: #111827;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .details-table th,
        .details-table td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 11px;
            text-align: left;
        }
        .details-table th {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }
        .details-table td.amount {
            text-align: right;
            white-space: nowrap;
        }
        .amount-line {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
        }
        .amount-line.total {
            border-top: 1px dashed #9ca3af;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: 700;
        }
        .footer {
            margin-top: 18px;
            color: #6b7280;
            font-size: 11px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="watermark" aria-hidden="true">
            @if (! empty($logoUrl))
                <img class="watermark-logo" src="{{ $logoUrl }}" alt="School Logo">
            @endif
            <div class="watermark-name">{{ $schoolName }}</div>
        </div>

        <div class="content">
            <div class="header">
                <h1 class="school-name">{{ $schoolName }}</h1>
                <p class="subtitle">Payment Receipt</p>
                <div class="sub-meta">
                    @if (! empty($branchName))
                        <div><strong>Branch:</strong> {{ $branchName }}</div>
                    @endif
                    @if (! empty($schoolAddress) || ! empty($branchAddress))
                        <div>{{ $branchAddress ?: $schoolAddress }}</div>
                    @endif
                    @if (! empty($schoolPhone) || ! empty($schoolEmail))
                        <div>
                            @if (! empty($schoolPhone))
                                Phone: {{ $schoolPhone }}
                            @endif
                            @if (! empty($schoolPhone) && ! empty($schoolEmail))
                                | 
                            @endif
                            @if (! empty($schoolEmail))
                                Email: {{ $schoolEmail }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <table class="grid">
                <tr>
                    <td class="label">Receipt No</td>
                    <td><strong>{{ $receiptNo }}</strong></td>
                    <td class="label">Receipt Date</td>
                    <td><strong>{{ optional($paymentDate)->format('d M Y') }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Student Name</td>
                    <td>{{ $fee->student?->full_name ?? 'N/A' }}</td>
                    <td class="label">Admission No</td>
                    <td>{{ $fee->student?->admission_no ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Class</td>
                    <td>{{ $fee->student?->academicClass?->name ?? 'N/A' }}</td>
                    <td class="label">Section</td>
                    <td>{{ $fee->student?->section?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Fee Type</td>
                    <td>{{ ucfirst((string) $fee->fee_type) }}</td>
                    <td class="label">Payment Mode</td>
                    <td>{{ strtoupper((string) ($latestPayment?->payment_mode ?: $fee->payment_mode ?: 'N/A')) }}</td>
                </tr>
            </table>

            <div class="amount-box">
                <div class="amount-line">
                    <span>Total Fee Amount</span>
                    <strong>Rs {{ number_format((float) $fee->amount, 2) }}</strong>
                </div>
                <div class="amount-line">
                    <span>Current Payment</span>
                    <strong>Rs {{ number_format((float) $currentPaymentAmount, 2) }}</strong>
                </div>
                <div class="amount-line">
                    <span>Total Paid</span>
                    <strong>Rs {{ number_format((float) $totalPaidAmount, 2) }}</strong>
                </div>
                <div class="amount-line total">
                    <span>Remaining Due</span>
                    <strong>Rs {{ number_format((float) $remainingDueAmount, 2) }}</strong>
                </div>
            </div>

            @if (($paymentHistory ?? collect())->isNotEmpty())
                <div class="details-title">Payment Details</div>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Receipt No</th>
                            <th>Mode</th>
                            <th>Remarks</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paymentHistory as $index => $payment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ optional($payment->payment_date)->format('d M Y') ?: 'N/A' }}</td>
                                <td>{{ $payment->receipt_no ?: 'N/A' }}</td>
                                <td>{{ strtoupper((string) ($payment->payment_mode ?: 'N/A')) }}</td>
                                <td>{{ $payment->remarks ?: '-' }}</td>
                                <td class="amount">Rs {{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (!empty($latestPayment?->remarks) || !empty($fee->remarks))
                <table class="grid">
                    <tr>
                        <td class="label">Remarks</td>
                        <td>{{ $latestPayment?->remarks ?: $fee->remarks }}</td>
                    </tr>
                </table>
            @endif

            <div class="footer">
                Generated on {{ $generatedAt->format('d M Y, h:i A') }} | This is a system-generated receipt.
            </div>
        </div>
    </div>
</body>
</html>
