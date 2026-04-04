<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Quotation' }}</title>
    <style>
        @page {
            margin: 16mm;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.45;
            background: #ffffff;
        }

        .document {
            position: relative;
            border: 1px solid #dbe3ea;
            border-radius: 14px;
            padding: 24px;
            overflow: hidden;
            background: #ffffff;
        }

        .watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .watermark-logo {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 220px;
            object-fit: contain;
            opacity: 0.05;
        }

        .watermark-text {
            position: absolute;
            top: 46%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 56px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            white-space: nowrap;
            color: #0f172a;
            opacity: 0.04;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .top-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .top-bar td {
            vertical-align: top;
        }

        .brand-logo {
            width: 68px;
            height: 68px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid #dbe3ea;
            background: #ffffff;
            padding: 6px;
        }

        .brand-mark {
            width: 68px;
            height: 68px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0f766e, #1d4ed8);
            color: #ffffff;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            line-height: 68px;
        }

        .brand-name {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .brand-subtitle {
            margin: 4px 0 0;
            color: #475569;
            font-size: 12px;
        }

        .document-badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-size: 11px;
            border: 1px solid #bfdbfe;
        }

        .meta-table,
        .info-table,
        .line-items,
        .summary-table,
        .terms-table,
        .bank-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table {
            margin-bottom: 16px;
        }

        .meta-card,
        .info-card,
        .terms-card,
        .summary-card,
        .bank-card {
            border: 1px solid #dbe3ea;
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .kv-row {
            margin-bottom: 6px;
        }

        .kv-label {
            color: #64748b;
            display: inline-block;
            min-width: 108px;
        }

        .spacer-row td {
            height: 12px;
        }

        .line-items {
            margin-top: 8px;
            border: 1px solid #dbe3ea;
            border-radius: 12px;
            overflow: hidden;
        }

        .line-items th {
            background: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 11px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .line-items td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .line-items tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .summary-wrap {
            margin-top: 16px;
        }

        .summary-table td {
            vertical-align: top;
        }

        .summary-box td {
            padding: 8px 0;
        }

        .summary-box .label {
            color: #64748b;
        }

        .summary-box .total-row {
            border-top: 1px dashed #94a3b8;
            font-weight: 700;
        }

        .note-box {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #dbe3ea;
            padding-top: 12px;
            color: #64748b;
            font-size: 11px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: bottom;
        }

        .signature-line {
            width: 180px;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            color: #475569;
        }
    </style>
</head>
<body>
@php
    $org = $organization ?? ($organizationContext['organization'] ?? null);
    $orgName = $org?->name ?: ($company['name'] ?? config('app.name', 'MEERAHR'));
    $orgType = ucfirst((string) ($org?->type ?: ($company['type'] ?? 'Institute')));
    $orgShort = strtoupper(substr((string) ($org?->short_name ?: $orgName), 0, 2));
    $logoUrl = $logoUrl ?? (!empty($org?->logo_path) ? asset('storage/' . $org->logo_path) : ($company['logo_url'] ?? null));
    $companyPhone = $company['phone'] ?? ($org?->phone ?? 'N/A');
    $companyEmail = $company['email'] ?? ($org?->email ?? 'N/A');
    $companyAddress = $company['address'] ?? ($org?->address ?? 'Address not provided');
    $documentType = $documentType ?? 'Quotation';
    $quotationNo = $quotationNo ?? 'QT-202603-001';
    $quotationDate = $quotationDate ?? now();
    $validUntil = $validUntil ?? now()->addDays(15);
    $currency = $currency ?? 'PKR';
    $items = collect($items ?? [
        ['description' => 'Institute Management System Setup', 'details' => 'Core deployment and initial configuration', 'qty' => 1, 'unit_price' => 45000],
        ['description' => 'Branch and User Mapping', 'details' => 'Multi-branch setup with role assignment', 'qty' => 1, 'unit_price' => 15000],
        ['description' => 'Training and Go-Live Support', 'details' => 'Admin onboarding and handover', 'qty' => 1, 'unit_price' => 12000],
    ]);
    $subtotal = (float) ($subtotal ?? $items->sum(function ($item) {
        return ((float) ($item['qty'] ?? 0)) * ((float) ($item['unit_price'] ?? 0));
    }));
    $discountRate = (float) ($discountRate ?? 0);
    $discount = (float) ($discount ?? 0);
    $taxRate = (float) ($taxRate ?? 0);
    $tax = (float) ($tax ?? 0);
    $grandTotal = (float) ($grandTotal ?? max(0, $subtotal - $discount + $tax));
    $generatedAt = ($generatedAt instanceof \Carbon\CarbonInterface ? $generatedAt : now())->timezone(config('app.timezone'));
    $client = $client ?? [];
    $terms = $terms ?? [
        '50% advance before project start.',
        '30% payable after setup completion.',
        '20% payable after training and handover.',
        'Quotation validity is subject to the mentioned deadline.',
    ];
    $bankDetails = $bankDetails ?? [
        'Account Name' => 'Your Company Name',
        'Bank Name' => 'Your Bank',
        'Account No' => '0000000000',
        'IBAN' => 'PK00BANK0000000000000000',
    ];
@endphp

    <div class="document">
        <div class="watermark" aria-hidden="true">
            @if (! empty($logoUrl))
                <img class="watermark-logo" src="{{ $logoUrl }}" alt="{{ $orgName }} logo">
            @endif
            <div class="watermark-text">{{ $orgName }}</div>
        </div>

        <div class="content">
            <table class="top-bar">
                <tr>
                    <td>
                        <table>
                            <tr>
                                <td style="width: 84px; vertical-align: top;">
                                    @if (! empty($logoUrl))
                                        <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="brand-logo">
                                    @else
                                        <div class="brand-mark">{{ $orgShort }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align: top; padding-left: 12px;">
                                    <h1 class="brand-name">{{ $orgName }}</h1>
                                    <p class="brand-subtitle">{{ $orgType }} Management Solution</p>
                                    <p class="brand-subtitle">{{ $companyAddress }}</p>
                                    <p class="brand-subtitle">Phone: {{ $companyPhone }} | Email: {{ $companyEmail }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 180px; text-align: right;">
                        <div class="document-badge">{{ $documentType }}</div>
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td style="width: 52%; padding-right: 8px;">
                        <div class="info-card">
                            <div class="section-title">Bill To</div>
                            <div class="kv-row"><span class="kv-label">Client Name:</span> <strong>{{ $client['name'] ?? 'Client Name' }}</strong></div>
                            <div class="kv-row"><span class="kv-label">Institute:</span> {{ $client['institute_name'] ?? 'Institute Name' }}</div>
                            <div class="kv-row"><span class="kv-label">Contact Person:</span> {{ $client['contact_person'] ?? 'Contact Person' }}</div>
                            <div class="kv-row"><span class="kv-label">Phone:</span> {{ $client['phone'] ?? 'N/A' }}</div>
                            <div class="kv-row"><span class="kv-label">Email:</span> {{ $client['email'] ?? 'N/A' }}</div>
                            <div class="kv-row"><span class="kv-label">Address:</span> {{ $client['address'] ?? 'Client Address' }}</div>
                        </div>
                    </td>
                    <td style="width: 48%; padding-left: 8px;">
                        <div class="meta-card">
                            <div class="section-title">Document Details</div>
                            <div class="kv-row"><span class="kv-label">Quotation No:</span> <strong>{{ $quotationNo }}</strong></div>
                            <div class="kv-row"><span class="kv-label">Date:</span> {{ $quotationDate instanceof \Carbon\CarbonInterface ? $quotationDate->format('d M Y') : $quotationDate }}</div>
                            <div class="kv-row"><span class="kv-label">Valid Until:</span> {{ $validUntil instanceof \Carbon\CarbonInterface ? $validUntil->format('d M Y') : $validUntil }}</div>
                            <div class="kv-row"><span class="kv-label">Generated At:</span> {{ $generatedAt instanceof \Carbon\CarbonInterface ? $generatedAt->format('d M Y, h:i:s A') : $generatedAt }} ({{ config('app.timezone') }})</div>
                            <div class="kv-row"><span class="kv-label">Currency:</span> {{ $currency }}</div>
                            <div class="kv-row"><span class="kv-label">Prepared By:</span> {{ $preparedBy ?? 'Sales Team' }}</div>
                            <div class="kv-row"><span class="kv-label">Subject:</span> {{ $subject ?? 'Institute Management Software Proposal' }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="info-card">
                <div class="section-title">Project Scope</div>
                <div>{{ $introText ?? 'This quotation covers software setup, organization branding, branch configuration, user mapping, training, and go-live support for the client institute.' }}</div>
            </div>

            <table class="line-items">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Description</th>
                        <th style="width: 80px;">Qty</th>
                        <th style="width: 120px;" class="text-right">Unit Price</th>
                        <th style="width: 130px;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        @php
                            $qty = (float) ($item['qty'] ?? 0);
                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                            $amount = (float) ($item['amount'] ?? ($qty * $unitPrice));
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item['description'] ?? 'Service Item' }}</strong>
                                @if (! empty($item['details']))
                                    <div style="color: #64748b; margin-top: 4px;">{{ $item['details'] }}</div>
                                @endif
                            </td>
                            <td>{{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }}</td>
                            <td class="text-right">{{ $currency }} {{ number_format($unitPrice, 2) }}</td>
                            <td class="text-right"><strong>{{ $currency }} {{ number_format($amount, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary-wrap">
                <table class="summary-table">
                    <tr>
                        <td style="width: 56%; padding-right: 8px;">
                            <div class="terms-card">
                                <div class="section-title">Terms and Conditions</div>
                                <table class="terms-table">
                                    @foreach ($terms as $term)
                                        <tr>
                                            <td style="width: 16px; vertical-align: top;">-</td>
                                            <td>{{ $term }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                            <div class="bank-card" style="margin-top: 12px;">
                                <div class="section-title">Bank / Payment Details</div>
                                <table class="bank-table">
                                    @foreach ($bankDetails as $label => $value)
                                        <tr>
                                            <td style="width: 120px; color: #64748b; padding: 4px 0;">{{ $label }}</td>
                                            <td style="padding: 4px 0;"><strong>{{ $value }}</strong></td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </td>
                        <td style="width: 44%; padding-left: 8px;">
                            <div class="summary-card">
                                <div class="section-title">Commercial Summary</div>
                                <table class="summary-box" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td class="label">Subtotal</td>
                                        <td class="text-right"><strong>{{ $currency }} {{ number_format($subtotal, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Discount @if($discountRate > 0) ({{ number_format($discountRate, 2) }}%) @endif</td>
                                        <td class="text-right"><strong>{{ $currency }} {{ number_format($discount, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Tax / Charges @if($taxRate > 0) ({{ number_format($taxRate, 2) }}%) @endif</td>
                                        <td class="text-right"><strong>{{ $currency }} {{ number_format($tax, 2) }}</strong></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td style="padding-top: 10px;">Grand Total</td>
                                        <td class="text-right" style="padding-top: 10px;"><strong>{{ $currency }} {{ number_format($grandTotal, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>

                            @if (! empty($notes))
                                <div class="note-box">
                                    <strong>Notes:</strong> {{ $notes }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line">Authorized By</div>
                    </td>
                    <td style="text-align: right;">
                        <div class="signature-line" style="margin-left: auto;">Client Approval</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <div>Generated on {{ $generatedAt instanceof \Carbon\CarbonInterface ? $generatedAt->format('d M Y, h:i:s A') : $generatedAt }} ({{ config('app.timezone') }})</div>
                <div>{{ $footerText ?? 'This is a system-ready branded quotation template for client proposals and invoice-style offers.' }}</div>
            </div>
        </div>
    </div>
</body>
</html>