<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 30px; background: #f8fafc; }
        .card { width: 340px; border: 2px solid #0f172a; border-radius: 18px; background: white; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0f766e, #0f172a); color: white; padding: 18px; text-align: center; }
        .body { padding: 18px; }
        .photo { width: 92px; height: 92px; border-radius: 12px; object-fit: cover; border: 2px solid #cbd5e1; margin: 0 auto 12px; }
        .label { font-size: 11px; color: #64748b; text-transform: uppercase; margin-top: 8px; }
        .value { font-size: 14px; font-weight: bold; color: #0f172a; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div style="font-size: 22px; font-weight: bold;">{{ $schoolName ?? 'SchoolSphere' }}</div>
            <div>School / College Identity Card</div>
        </div>
        <div class="body">
            @if ($photoUrl)
                <img class="photo" src="{{ $photoUrl }}" alt="Photo">
            @endif
            <div class="label">Name</div>
            <div class="value">{{ $name }}</div>
            <div class="label">{{ $metaLabel }}</div>
            <div class="value">{{ $metaValue }}</div>
            <div class="label">School Details</div>
            <div class="value">{{ $schoolName ?? config('app.name') }}<br>School_DB_all</div>
        </div>
    </div>
</body>
</html>
