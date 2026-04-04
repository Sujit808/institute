<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }
    .page { padding: 40px 48px; }
    .header { text-align: center; border-bottom: 3px double #059669; padding-bottom: 16px; margin-bottom: 24px; }
    .org-name { font-size: 22px; font-weight: 700; color: #065f46; letter-spacing: 1px; }
    .org-sub  { font-size: 12px; color: #475569; margin-top: 2px; }
    .cert-title { text-align: center; margin: 18px 0; }
    .cert-title h2 { font-size: 18px; font-weight: 700; color: #1e293b; text-decoration: underline; text-underline-offset: 4px; letter-spacing: 1px; }
    .cert-no { text-align: right; font-size: 12px; color: #64748b; margin-bottom: 18px; }
    .body-text { font-size: 13.5px; line-height: 2.1; text-align: justify; margin: 18px 0; }
    .highlight { font-weight: 700; border-bottom: 1px dashed #94a3b8; }
    .footer { margin-top: 48px; display: flex; justify-content: space-between; align-items: flex-end; }
    .footer .sign { text-align: center; }
    .footer .sign .line { border-top: 1px solid #1e293b; width: 160px; margin-bottom: 4px; }
    .footer .sign .label { font-size: 11px; color: #475569; }
    .stamp-box { width: 110px; height: 80px; display: flex; align-items: center; justify-content: center; }
    .stamp-box img { max-width: 110px; max-height: 80px; object-fit: contain; }
    .signature-box { min-height: 70px; display: flex; align-items: end; justify-content: center; margin-bottom: 4px; }
    .signature-box img { max-width: 170px; max-height: 65px; object-fit: contain; }
    .watermark { position: fixed; bottom: 80px; right: 40px; font-size: 52px; color: rgba(5,150,105,.06); font-weight: 900; transform: rotate(-30deg); }
    .note { font-size: 11.5px; color: #64748b; border-top: 1px solid #e2e8f0; margin-top: 24px; padding-top: 10px; }
</style>
</head>
<body>
<div class="page">
    <div class="watermark">BONAFIDE</div>

    <div class="header">
        <div class="org-name">{{ strtoupper($org?->name ?? config('app.name')) }}</div>
        @if($org?->address)
            <div class="org-sub">{{ $org->address }}</div>
        @endif
        @if($org?->phone)
            <div class="org-sub">Phone: {{ $org->phone }} {{ $org->email ? '| Email: '.$org->email : '' }}</div>
        @endif
    </div>

    <div class="cert-title"><h2>BONAFIDE CERTIFICATE</h2></div>

    <div class="cert-no">
        Cert No: BF/{{ $student->admission_no }}/{{ now()->format('Y') }} &nbsp;&nbsp;|&nbsp;&nbsp; Date: {{ $generatedOn }}
    </div>

    <p class="body-text">
        This is to certify that <span class="highlight">{{ strtoupper($student->full_name) }}</span>,
        son/daughter of <span class="highlight">{{ $student->guardian_name ?? '_______________' }}</span>,
        bearing Admission No. <span class="highlight">{{ $student->admission_no ?? '-' }}</span>,
        is a bonafide student of <span class="highlight">Class {{ $student->academicClass?->name ?? '___' }}</span>
        @if($student->section?->name)
            Section <span class="highlight">{{ $student->section->name }}</span>
        @endif
        for the current academic session.
    </p>

    <p class="body-text">
        The student's date of birth as per school records is
        <span class="highlight">{{ $student->date_of_birth?->format('d M Y') ?? '_______________' }}</span>.
        This certificate is being issued on request for the purpose stated by the student / guardian.
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

    <p class="note">* This certificate is valid for official purposes only and is subject to verification.</p>
</div>
</body>
</html>
