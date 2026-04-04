<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .logo {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 10px;
        }

        .school-name {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .school-meta {
            margin: 2px 0 0 0;
            color: #4b5563;
            font-size: 11px;
            line-height: 1.45;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            color: #0f4c81;
            margin: 14px 0 8px;
        }

        .student-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 14px;
        }

        .student-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .student-grid td {
            padding: 4px 6px;
            font-size: 11.5px;
            vertical-align: top;
        }

        .label {
            color: #6b7280;
            width: 120px;
        }

        table.marks {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.marks th,
        table.marks td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }

        table.marks th {
            background: #eff6ff;
            color: #0f4c81;
            font-weight: 700;
            font-size: 11.5px;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            margin-top: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            background: #f8fafc;
        }

        .summary-row {
            margin: 3px 0;
            font-size: 12px;
        }

        .footer {
            margin-top: 16px;
            font-size: 10.5px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60px; vertical-align: top;">
                    @if(!empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                    @endif
                </td>
                <td style="vertical-align: top;">
                    <p class="school-name">{{ $schoolName }}</p>
                    <p class="school-meta">
                        @if(!empty($branchName)){{ $branchName }}<br>@endif
                        @if(!empty($branchAddress)){{ $branchAddress }}<br>@elseif(!empty($schoolAddress)){{ $schoolAddress }}<br>@endif
                        @if(!empty($schoolPhone))Phone: {{ $schoolPhone }} @endif
                        @if(!empty($schoolEmail)) | Email: {{ $schoolEmail }}@endif
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="title">Student Result Summary</div>

    <div class="student-box">
        <table class="student-grid">
            <tr>
                <td class="label">Student Name</td>
                <td>{{ $student->name }}</td>
                <td class="label">Roll No</td>
                <td>{{ $student->roll_no ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Class</td>
                <td>{{ optional($student->academicClass)->name ?? '-' }}</td>
                <td class="label">Section</td>
                <td>{{ optional($student->section)->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="marks">
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 40%;">Subject</th>
                <th class="text-right" style="width: 17%;">Marks</th>
                <th class="text-right" style="width: 17%;">Percentage</th>
                <th class="text-right" style="width: 18%;">Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjectRows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['subject'] }}</td>
                    <td class="text-right">{{ number_format((float) $row['marks'], 2) }} / {{ $row['max_marks'] }}</td>
                    <td class="text-right">{{ number_format((float) $row['percentage'], 2) }}%</td>
                    <td class="text-right">{{ $row['grade'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row"><strong>Total Subjects:</strong> {{ $totalSubjects }}</div>
        <div class="summary-row"><strong>Total Marks Obtained:</strong> {{ number_format((float) $totalObtained, 2) }} / {{ number_format((float) $totalMaximum, 2) }}</div>
        <div class="summary-row"><strong>Overall Percentage:</strong> {{ number_format((float) $overallPercentage, 2) }}%</div>
    </div>

    <div class="footer">
        Generated on {{ $generatedAt->format('d M Y, h:i A') }}
    </div>
</body>
</html>
