<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Branded</title>
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
            background: #f2f8f8;
            color: #08363d;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html {
            background: #f2f8f8;
        }

        .stage {
            width: 100%;
            padding-top: 18px;
        }

        .cards-table {
            width: 610px;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .cards-table td {
            width: 305px;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }

        .lanyard {
            display: inline-block;
            width: 32px;
            height: 72px;
            background: #696969;
            border-radius: 0 0 8px 8px;
            position: relative;
            margin-bottom: 12px;
        }

        .ring {
            width: 34px;
            height: 14px;
            border: 2px solid #8a8a8a;
            border-radius: 16px;
            position: absolute;
            left: -1px;
            bottom: -9px;
            background: #d7d7d7;
        }

        .id-card {
            width: 255px;
            height: 390px;
            border-radius: 18px;
            border: 5px solid #d9eff0;
            background: #ffffff;
            box-shadow: 0 16px 30px rgba(12, 83, 91, 0.20);
            display: block;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            white-space: normal;
            font-size: 12px;
        }

        .top-wave {
            height: 108px;
            background: #0f7ea6;
            background-color: #0f7ea6;
            border-bottom-left-radius: 100px 46px;
            border-bottom-right-radius: 100px 46px;
            position: relative;
        }

        .brand {
            position: absolute;
            width: 100%;
            text-align: center;
            top: 20px;
            color: #ffffff;
        }

        .brand-logo {
            width: 30px;
            height: 30px;
            object-fit: contain;
            display: block;
            margin: 0 auto 6px;
        }

        .brand-title {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 12px;
        }

        .front-watermark {
            position: absolute;
            top: 136px;
            left: 50%;
            width: 116px;
            height: 116px;
            margin-left: -58px;
            opacity: 0.07;
        }

        .front-watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .avatar-box {
            width: 92px;
            height: 108px;
            margin: -28px auto 8px;
            border: 4px solid #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(22, 90, 94, 0.28);
            background: #d7eeef;
            position: relative;
        }

        .avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name {
            text-align: center;
            color: #1ba4a9;
            font-size: 27px;
            line-height: 1;
            font-weight: 700;
            margin: 8px 10px 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .role {
            text-align: center;
            font-size: 15px;
            color: #3d7075;
            margin-bottom: 8px;
        }

        .id-pill {
            display: inline-block;
            margin: 0 auto 10px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #9fd5d9;
            background: #f2fbfb;
            font-size: 10px;
            font-weight: 700;
            color: #11656f;
            letter-spacing: 0.6px;
        }

        .meta {
            width: 198px;
            margin: 0 auto;
            font-size: 12px;
            text-align: start;
        }

        .meta-row {
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 1px dotted #d7ecee;
        }

        .meta-key {
            display: inline-block;
            width: 62px;
            font-weight: 700;
            color: #0f8d91;
            text-transform: uppercase;
        }

        .barcode {
            width: 128px;
            height: 42px;
            margin: 14px auto 0;
            border: 1px solid #9bcfd1;
            background:
                repeating-linear-gradient(
                    90deg,
                    #1a6067 0,
                    #1a6067 2px,
                    transparent 2px,
                    transparent 4px,
                    #1a6067 4px,
                    #1a6067 5px,
                    transparent 5px,
                    transparent 7px
                );
        }

        .barcode-no {
            text-align: center;
            margin-top: 3px;
            font-size: 10px;
            letter-spacing: 1.1px;
            color: #1f5860;
        }

        .security-box {
            margin-top: 14px;
            padding: 8px 10px;
            border: 1px solid #b8dde0;
            border-radius: 8px;
            background: #f8feff;
            font-size: 10px;
            color: #2f666f;
            line-height: 1.45;
        }

        .security-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0b9ca2;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .back-pad {
            padding: 18px 20px;
        }

        .contact-row {
            margin-bottom: 10px;
            font-size: 12px;
            color: #2f666f;
        }

        .contact-label {
            display: inline-block;
            width: 60px;
            color: #0b9ca2;
            font-weight: 700;
            text-transform: uppercase;
        }

        .rules {
            margin: 12px 0 0 0;
            padding: 0;
            list-style: none;
            font-size: 11px;
            color: #46767d;
            line-height: 1.45;
        }

        .rules li {
            margin: 0 0 8px 0;
            padding-left: 16px;
            position: relative;
        }

        .rules li:before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #1ba4a9;
            position: absolute;
            left: 2px;
            top: 4px;
        }

        .sign {
            margin-top: 10px;
            text-align: right;
            color: #35575f;
        }

        .sign-mark {
            font-style: italic;
            font-size: 16px;
            margin-bottom: 1px;
            color: #75939a;
        }

        .sign-line {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .bottom-wave {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 86px;
            background: #0e89ab;
            border-top-left-radius: 110px 52px;
            border-top-right-radius: 46px 28px;
        }

        .bottom-brand {
            position: absolute;
            bottom: 16px;
            width: 100%;
            text-align: center;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .bottom-brand small {
            display: block;
            font-size: 8px;
            opacity: 0.9;
            font-weight: 400;
            margin-top: 2px;
        }

        .bottom-logo {
            width: 20px;
            height: 20px;
            object-fit: contain;
            display: block;
            margin: 0 auto 4px;
        }
    </style>
</head>
<body>
    <div class="stage">
        <table class="cards-table" role="presentation">
            <tr>
                <td>
                <div class="lanyard"><span class="ring"></span></div>
                <section class="id-card">
                    <div class="top-wave">
                        <div class="brand">
                            @if (! empty($logoUrl))
                                <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo">
                            @endif
                            <p class="brand-title">{{ strtoupper($schoolName ?? config('app.name')) }}</p>
                        </div>
                    </div>

                    @if (! empty($logoUrl))
                        <div class="front-watermark">
                            <img src="{{ $logoUrl }}" alt="Watermark">
                        </div>
                    @endif

                    <div class="avatar-box">
                        @if ($photoUrl)
                            <img class="avatar" src="{{ $photoUrl }}" alt="Photo">
                        @endif
                    </div>

                    <div class="name">{{ strtoupper(
                        strlen($name) > 16 ? substr($name, 0, 16) : $name
                    ) }}</div>
                    <div class="role">{{ $roleLine ?? $metaValue }}</div>
                    <div style="text-align:center;">
                        <span class="id-pill">ID: {{ $admissionNo ?? $rollNo ?? $id }}</span>
                    </div>

                    <div class="meta">
                        <div class="meta-row"><span class="meta-key">Roll :</span> {{ $rollNo ?? 'N/A' }}</div>
                        <div class="meta-row"><span class="meta-key">Adm :</span> {{ $admissionNo ?? $id }}</div>
                        <div class="meta-row"><span class="meta-key">Blood :</span> {{ $bloodGroup ?? 'N/A' }}</div>
                        <div class="meta-row"><span class="meta-key">I.O.D. :</span> {{ $issueDate ?? now()->format('d-m-Y') }}</div>
                        <div class="meta-row"><span class="meta-key">Expire :</span> {{ $expiryDate ?? now()->addYear()->format('d-m-Y') }}</div>
                    </div>

                    <div class="barcode"></div>
                    <div class="barcode-no">{{ $rollNo ?? $id }}</div>
                </section>
                </td>

                <td>
                <div class="lanyard"><span class="ring"></span></div>
                <section class="id-card">
                    <div class="top-wave">
                        <div class="brand">
                            @if (! empty($logoUrl))
                                <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo">
                            @endif
                            <p class="brand-title">{{ strtoupper($schoolName ?? config('app.name')) }}</p>
                        </div>
                    </div>

                    <div class="back-pad">
                        <div class="contact-row">
                            <span class="contact-label">Phone :</span>
                            {{ $phone ?: 'N/A' }}
                        </div>
                        <div class="contact-row">
                            <span class="contact-label">Email :</span>
                            {{ $email ?: 'N/A' }}
                        </div>

                        <ul class="rules">
                            <li>Loss or misuse should be reported to the admin office immediately.</li>
                            <li>Carry this ID card while on campus and during academic activities.</li>
                        </ul>

                        <div class="security-box">
                            <div class="security-title">Emergency / Verification</div>
                            <div><strong>ID No:</strong> {{ $admissionNo ?? $rollNo ?? $id }}</div>
                            <div><strong>Valid Till:</strong> {{ $expiryDate ?? now()->addYear()->format('d-m-Y') }}</div>
                            <div><strong>Support:</strong> {{ $phone ?: 'School Office' }}</div>
                        </div>

                        <div class="sign">
                            <div class="sign-mark">Authorized</div>
                            <div class="sign-line">AUTHORIZED SIGNATORY</div>
                        </div>
                    </div>

                    <div class="bottom-wave"></div>
                    <div class="bottom-brand">
                        @if (! empty($logoUrl))
                            <img class="bottom-logo" src="{{ $logoUrl }}" alt="Logo">
                        @endif
                        {{ strtoupper($schoolName ?? config('app.name')) }}
                        <small>Campus Management System</small>
                    </div>
                </section>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
