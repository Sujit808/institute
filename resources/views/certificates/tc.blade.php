<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }
    .page { padding: 40px 48px; }
    .header { text-align: center; border-bottom: 3px double #1d67c1; padding-bottom: 16px; margin-bottom: 24px; }
    .org-name { font-size: 22px; font-weight: 700; color: #1d4ed8; letter-spacing: 1px; }
    .org-sub  { font-size: 12px; color: #475569; margin-top: 2px; }
    .cert-title { text-align: center; margin: 18px 0; }
    .cert-title h2 { font-size: 18px; font-weight: 700; color: #1e293b; text-decoration: underline; text-underline-offset: 4px; letter-spacing: 1px; }
    .cert-no { text-align: right; font-size: 12px; color: #64748b; margin-bottom: 18px; }
    .body-text { font-size: 13.5px; line-height: 2; text-align: justify; }
    .body-text .highlight { font-weight: 700; color: #1e293b; border-bottom: 1px dashed #94a3b8; }
    .info-table { width: 100%; margin: 18px 0; border-collapse: collapse; }
    .info-table td { padding: 7px 10px; border: 1px solid #e2e8f0; font-size: 12.5px; }
    .info-table td:first-child { font-weight: 600; background: #f8fafc; width: 38%; }
    .footer { margin-top: 48px; display: flex; justify-content: space-between; align-items: flex-end; }
    .footer .sign { text-align: center; }
    .footer .sign .line { border-top: 1px solid #1e293b; width: 160px; margin-bottom: 4px; }
    .footer .sign .label { font-size: 11px; color: #475569; }
    .stamp-box { width: 110px; height: 80px; display: flex; align-items: center; justify-content: center; }
    .stamp-box img { max-width: 110px; max-height: 80px; object-fit: contain; }
    .signature-box { min-height: 70px; display: flex; align-items: end; justify-content: center; margin-bottom: 4px; }
    .signature-box img { max-width: 170px; max-height: 65px; object-fit: contain; }
    .watermark { position: fixed; bottom: 80px; right: 40px; font-size: 60px; color: rgba(29,103,193,.06); font-weight: 900; transform: rotate(-30deg); }
</style>
</head>
<body>
<div class="page">
    <div class="watermark">TC</div>

    <div class="header">
        <div class="org-name">{{ strtoupper($org?->name ?? config('app.name')) }}</div>
        @if($org?->address)
            <div class="org-sub">{{ $org->address }}</div>
        @endif
        @if($org?->phone)
            <div class="org-sub">Phone: {{ $org->phone }} {{ $org->email ? '| Email: '.$org->email : '' }}</div>
        @endif
    </div>

    <div class="cert-title"><h2>TRANSFER CERTIFICATE</h2></div>

    <div class="cert-no">
        TC No: TC/{{ $student->admission_no }}/{{ now()->format('Y') }} &nbsp;&nbsp;|&nbsp;&nbsp; Date: {{ $generatedOn }}
    </div>

    <table class="info-table">
        <tr><td>Student Name</td><td><strong>{{ strtoupper($student->full_name) }}</strong></td></tr>
        <tr><td>Admission No</td><td>{{ $student->admission_no ?? '-' }}</td></tr>
        <tr><td>Date of Birth</td><td>{{ $student->date_of_birth?->format('d M Y') ?? '-' }}</td></tr>
        <tr><td>Gender</td><td>{{ ucfirst($student->gender ?? '-') }}</td></tr>
        <tr><td>Class / Section</td><td>{{ $student->academicClass?->name ?? '-' }} / {{ $student->section?->name ?? '-' }}</td></tr>
        <tr><td>Father / Guardian</td><td>{{ $student->guardian_name ?? '-' }}</td></tr>
        <tr><td>Blood Group</td><td>{{ $student->blood_group ?? '-' }}</td></tr>
        <tr><td>Admission Date</td><td>{{ $student->admission_date?->format('d M Y') ?? '-' }}</td></tr>
        <tr><td>Date of Leaving</td><td>{{ $generatedOn }}</td></tr>
        <tr><td>Reason for Leaving</td><td>As requested by parents / guardian</td></tr>
    </table>

    <p class="body-text" style="margin-top:12px;">
        This is to certify that <span class="highlight">{{ $student->full_name }}</span> was a bonafide student of this institution.
        The student has cleared all dues and no disciplinary action is pending. This certificate is issued on request for the purpose of admission to another institution.
    </p>

    <div class="footer">
        <div class="sign">
            <div class="stamp-box">
                @if(!empty($stampPath))
                    <img src="{{ $stampPath }}" alt="Office Seal">
                @else
                    Office Seal
                @endif
            </div>
        </div>
        <div class="sign" style="text-align:right;">
            <div class="signature-box">
                @if(!empty($signaturePath))
                    <img src="{{ $signaturePath }}" alt="Principal Signature">
                @endif
            </div>
            <div class="line" style="margin-left:auto;"></div>
            <div class="label">Principal / Head of Institution</div>
            <div class="label">{{ $org?->name ?? config('app.name') }}</div>
        </div>
    </div>
</div>
</body>
</html>
