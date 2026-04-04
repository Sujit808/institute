<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['documentType'] ?? 'Quotation' }} Share</title>
</head>
<body style="margin:0; padding:24px; background:#f8fafc; font-family:Arial, sans-serif; color:#1f2937;">
    <div style="max-width:680px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="padding:20px 24px; background:#0f172a; color:#ffffff;">
            <h1 style="margin:0; font-size:22px;">{{ $payload['documentType'] ?? 'Quotation' }} Shared</h1>
            <p style="margin:6px 0 0; opacity:0.85;">{{ $payload['company']['name'] ?? config('app.name', 'MEERAHR') }}</p>
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear {{ $payload['client']['contact_person'] ?? $payload['client']['name'] ?? 'Client' }},</p>

            @if (!empty($customMessage))
                <p>{{ $customMessage }}</p>
            @else
                <p>Please find the attached quotation PDF for your review.</p>
            @endif

            <table style="width:100%; border-collapse:collapse; margin:18px 0;">
                <tr>
                    <td style="padding:8px 0; color:#64748b; width:170px;">Document Type</td>
                    <td style="padding:8px 0;"><strong>{{ $payload['documentType'] ?? 'Quotation' }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Quotation No</td>
                    <td style="padding:8px 0;"><strong>{{ $payload['quotationNo'] ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Client</td>
                    <td style="padding:8px 0;">{{ $payload['client']['institute_name'] ?? ($payload['client']['name'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Grand Total</td>
                    <td style="padding:8px 0;"><strong>{{ $payload['currency'] ?? 'PKR' }} {{ number_format((float) ($payload['grandTotal'] ?? 0), 2) }}</strong></td>
                </tr>
            </table>

            <p style="margin-bottom:0;">Regards,<br><strong>{{ $payload['preparedBy'] ?? 'Sales Team' }}</strong><br>{{ $payload['company']['name'] ?? config('app.name', 'MEERAHR') }}</p>
        </div>
    </div>
</body>
</html>