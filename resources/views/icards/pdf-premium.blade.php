<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Premium V2</title>
    <style>
        @page {
            margin: 12mm;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            background: #e8eff1;
            color: #16363c;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .stage {
            width: 100%;
            padding-top: 14px;
        }

        .cards-table {
            width: 620px;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .cards-table td {
            width: 310px;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }

        .lanyard {
            display: inline-block;
            width: 30px;
            height: 70px;
            background: #6a6a6a;
            border-radius: 0 0 8px 8px;
            position: relative;
            margin-bottom: 10px;
        }

        .ring {
            width: 34px;
            height: 14px;
            border: 2px solid #8a8a8a;
            border-radius: 16px;
            position: absolute;
            left: -2px;
            bottom: -9px;
            background: #d7d7d7;
        }

        .id-card {
            width: 252px;
            height: 392px;
            border-radius: 18px;
            border: 4px solid #c8e6eb;
            background: #ffffff;
            box-shadow: 0 14px 28px rgba(17, 78, 85, 0.2);
            display: block;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            white-space: normal;
            font-size: 12px;
        }

        .top-band {
            height: 90px;
            background: #0f7ea6;
            position: relative;
        }

        .top-accent {
            position: absolute;
            right: -30px;
            top: 12px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 18px solid rgba(255, 255, 255, 0.12);
        }

        .brand {
            position: absolute;
            left: 0;
            right: 0;
            top: 8px;
            text-align: center;
            color: #ffffff;
            z-index: 3;
        }

        .brand-logo {
            width: 24px;
            height: 24px;
            object-fit: contain;
            display: block;
            margin: 0 auto 3px;
        }

        .brand-title {
            margin: 0;
            padding: 0 12px;
            font-size: 11px;
            letter-spacing: 0.7px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .role-chip {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 8px;
            letter-spacing: 0.6px;
            font-weight: 700;
            text-transform: uppercase;
            background: #eaf8ff;
            color: #0a607f;
        }

        .role-chip-student {
            background: #e8f8ff;
            color: #0b6e95;
        }

        .role-chip-staff {
            background: #fff1e7;
            color: #a15500;
        }

        .front-watermark {
            position: absolute;
            top: 116px;
            left: 50%;
            width: 108px;
            height: 108px;
            margin-left: -54px;
            opacity: 0.06;
            z-index: 0;
        }

        .front-watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .avatar-wrap {
            position: relative;
            z-index: 3;
            margin-top: -24px;
        }

        .avatar-box {
            width: 86px;
            height: 98px;
            margin: 0 auto 6px;
            border: 4px solid #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(16, 84, 90, 0.28);
            background: #d7eeef;
        }

        .avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name {
            text-align: center;
            color: #1b9ca8;
            font-size: 17px;
            font-weight: 700;
            margin: 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: -6px;
        }

        .role {
            text-align: center;
            font-size: 12px;
            color: #3d7075;
            margin-bottom: 4px;
            margin-top: -3px;
        }

        .id-strip {
            width: 205px;
            margin: 0 auto 6px;
            border: 1px dashed #9bd3d8;
            border-radius: 8px;
            background: #f2fbfb;
            padding: 3px 7px;
            font-size: 9px;
            font-weight: 700;
            color: #0f6f77;
            letter-spacing: 0.4px;
            text-align: center;
        }

        .meta {
            width: 204px;
            margin: 0 auto;
            font-size: 9px;
            text-align: left;
        }

        .meta-row {
            margin-bottom: 2px;
            border-bottom: 1px dotted #d6eaec;
            padding-bottom: 2px;
        }

        .meta-key {
            display: inline-block;
            width: 58px;
            color: #0b9498;
            font-weight: 700;
            text-transform: uppercase;
        }

        .verify-box {
            width: 204px;
            margin: 8px auto 0;
            border: 1px solid #b6dde1;
            border-radius: 8px;
            background: #f8feff;
            padding: 5px 7px;
            text-align: left;
            font-size: 8px;
            color: #3f6f76;
            line-height: 1.35;
        }

        .verify-title {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f8d91;
            margin-bottom: 2px;
        }

        .back-pad {
            padding: 12px 14px;
            text-align: left;
        }

        .section-title {
            margin: 0 0 4px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.6px;
            color: #0b9498;
        }

        .contact-grid {
            border: 1px solid #c4e2e5;
            border-radius: 8px;
            background: #f8fdfe;
            padding: 6px;
            margin-bottom: 8px;
            font-size: 9px;
            color: #2f666f;
            line-height: 1.35;
        }

        .contact-label {
            display: inline-block;
            width: 44px;
            color: #0b9ca2;
            font-weight: 700;
            text-transform: uppercase;
        }

        .rules {
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 8px;
            color: #46767d;
            line-height: 1.35;
        }

        .rules li {
            margin: 0 0 4px;
            padding-left: 12px;
            position: relative;
        }

        .rules li:before {
            content: "";
            position: absolute;
            left: 1px;
            top: 3px;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #1ba4a9;
        }

        .security-row {
            margin-top: 8px;
            border: 1px solid #b7dde0;
            border-radius: 8px;
            background: #f6fcfd;
            padding: 5px 6px;
            font-size: 8px;
            color: #2f666f;
            line-height: 1.3;
        }

        .security-row strong {
            color: #0b8d92;
        }

        .sign {
            margin-top: 15px;
            text-align: right;
            color: #35575f;
            margin-right: 20px;
        }

        .sign-assets {
            display: block;
            height: 42px;
            position: relative;
            margin-bottom: 2px;
        }

        .signature-img {
            max-width: 82px;
            max-height: 30px;
            object-fit: contain;
            display: inline-block;
            vertical-align: bottom;
        }

        .seal-img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            opacity: 0.85;
            position: absolute;
            right: -8px;
            bottom: -2px;
        }

        .sign-mark {
            font-style: italic;
            font-size: 11px;
            color: #78959b;
            margin-bottom: 1px;
        }

        .sign-line {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-top: -20px;
        }

        .bottom-wave {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 65px;
            background: #0e89ab;
            border-top-left-radius: 104px 48px;
            border-top-right-radius: 42px 24px;
        }

        .serial-ribbon {
            position: absolute;
            right: -116px;
            bottom: 108px;
            background: #0f718f;
            color: #ffffff;
            font-size: 7px;
            font-weight: 700;
            padding: 3px 32px;
            transform: rotate(-90deg);
            letter-spacing: 0.7px;
            z-index: 1;
        }

        .bottom-brand {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 15px;
            text-align: center;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .bottom-logo {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: block;
            margin: 0 auto 3px;
        }

        .bottom-brand small {
            display: block;
            font-size: 7px;
            font-weight: 400;
            margin-top: 1px;
            opacity: 0.92;
            padding: 0 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    @php
        $roleText = strtoupper($roleLine ?? $metaValue ?? 'Member');
        $isStudentCard = str_contains(strtolower($roleText), 'student');
        $serialText = strtoupper(($admissionNo ?? $rollNo ?? $id) . '-' . date('y'));
    @endphp

    <div class="stage">
        <table class="cards-table" role="presentation">
            <tr>
                <td>
                    <div class="lanyard"><span class="ring"></span></div>
                    <section class="id-card">
                        <div class="top-band">
                            <div class="top-accent"></div>
                            <div class="brand">
                                @if (! empty($logoUrl))
                                    <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo">
                                @endif
                                <p class="brand-title">{{ strtoupper($schoolName ?? config('app.name')) }}</p>
                                <!-- <span class="role-chip {{ $isStudentCard ? 'role-chip-student' : 'role-chip-staff' }}">
                                    {{ $roleLine ?? $metaValue }}
                                </span> -->
                            </div>
                        </div>

                        @if (! empty($logoUrl))
                            <div class="front-watermark">
                                <img src="{{ $logoUrl }}" alt="Watermark">
                            </div>
                        @endif

                        <div class="avatar-wrap">
                            <div class="avatar-box">
                                @if ($photoUrl)
                                    <img class="avatar" src="{{ $photoUrl }}" alt="Photo">
                                @endif
                            </div>
                        </div>

                        <div class="name">{{ strtoupper(strlen($name) > 16 ? substr($name, 0, 16) : $name) }}</div>
                        <!-- <div class="role">{{ $roleLine ?? $metaValue }}</div> -->

                        <div class="meta">
                            <div class="meta-row"><span class="meta-key">Roll :</span> {{ $rollNo ?? 'N/A' }}</div>
                            <!-- <div class="meta-row"><span class="meta-key">Adm :</span> {{ $admissionNo ?? $id }}</div> -->
                             <div><span class="contact-label">Phone :</span> {{ $phone ?: 'N/A' }}</div>
                                <div><span class="contact-label">Email :</span> {{ $email ?: 'N/A' }}</div>
                            <div class="meta-row"><span class="meta-key">Blood :</span> {{ $bloodGroup ?? 'N/A' }}</div>
                            <div class="meta-row"><span class="meta-key">I.O.D. :</span> {{ $issueDate ?? now()->format('d-m-Y') }}</div>
                            <div class="meta-row"><span class="meta-key">Expire :</span> {{ $expiryDate ?? now()->addYear()->format('d-m-Y') }}</div>
                        </div>

                        <div class="id-strip">UNIQUE ID: {{ $admissionNo ?? $rollNo ?? $id }}</div>

                        <!-- <div class="verify-box">
                            <div class="verify-title">Verification Hint</div>
                            This card is valid only with institute seal and signature.
                        </div> -->
                        <!-- <p class="section-title">Emergency Contact</p>
                            <div class="contact-grid">
                                <div><span class="contact-label">Phone :</span> {{ $phone ?: 'N/A' }}</div>
                                <div><span class="contact-label">Email :</span> {{ $email ?: 'N/A' }}</div>
                            </div> -->
                    </section>
                </td>

                <td>
                    <div class="lanyard"><span class="ring"></span></div>
                    <section class="id-card">
                        <div class="top-band">
                            <div class="top-accent"></div>
                            <div class="brand">
                                @if (! empty($logoUrl))
                                    <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo">
                                @endif
                                <p class="brand-title">{{ strtoupper($schoolName ?? config('app.name')) }}</p>
                                <span class="role-chip {{ $isStudentCard ? 'role-chip-student' : 'role-chip-staff' }}">
                                    {{ $roleLine ?? $metaValue }}
                                </span>
                            </div>
                        </div>

                        <div class="back-pad">
                            <div class=""></div>
                            <!-- <p class="section-title">Emergency Contact</p>
                            <div class="contact-grid">
                                <div><span class="contact-label">Phone :</span> {{ $phone ?: 'N/A' }}</div>
                                <div><span class="contact-label">Email :</span> {{ $email ?: 'N/A' }}</div>
                            </div> -->

                            <p class="section-title">Card Rules</p>
                            <ul class="rules">
                                <li>Loss or misuse should be reported to the admin office immediately.</li>
                                <li>Card must be carried on campus during all academic activities.</li>
                                <li>Do not fold, punch, or laminate this card.</li>
                            </ul>

                            <div class="security-row">
                                <div><strong>ID No:</strong> {{ $admissionNo ?? $rollNo ?? $id }}</div>
                                <div><strong>Valid Till:</strong> {{ $expiryDate ?? now()->addYear()->format('d-m-Y') }}</div>
                                <div><strong>Issued By:</strong> {{ strtoupper($schoolName ?? config('app.name')) }}</div>
                            </div>

                            <div class="sign">
                                <div class="sign-assets">
                                    @if (! empty($signatureUrl))
                                        <img class="signature-img" src="{{ $signatureUrl }}" alt="Signature">
                                    @else
                                        <div class="sign-mark">Authorized</div>
                                    @endif

                                    @if (! empty($sealUrl))
                                        <img class="seal-img" src="{{ $sealUrl }}" alt="Seal">
                                    @endif
                                </div>
                                <div class="sign-line">AUTHORIZED SIGNATORY</div>
                            </div>
                        </div>

                        <div class="serial-ribbon">SERIAL {{ $serialText }}</div>
                        <div class="bottom-wave"></div>
                        <div class="bottom-brand">
                            @if (! empty($logoUrl))
                                <img class="bottom-logo" src="{{ $logoUrl }}" alt="Logo">
                            @endif
                            {{ strtoupper($schoolName ?? config('app.name')) }}
                            <small>{{ $schoolAddress ?? 'School Address' }}</small>
                        </div>
                    </section>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
