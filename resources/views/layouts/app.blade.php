<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SchoolSphere') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell d-flex flex-column min-vh-100">
        @include('layouts.header')

        @if (session('status'))
            <div class="container-fluid px-4 pt-3">
                <div class="alert alert-success border-0 shadow-sm mb-0">{{ session('status') }}</div>
            </div>
        @endif

        @if (!empty($licenseWarning['show']) && auth()->check() && !auth()->user()->isStudent())
            @php $dismissKey = 'license-warn-dismissed-' . ($licenseWarning['expires_at'] ?? ''); @endphp
            <div class="container-fluid px-4 pt-3" id="license-warning-banner">
                <div class="alert license-warning-banner border-0 shadow-sm mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>License Expiry Alert:</strong>
                        Your license will expire in
                        <strong>{{ $licenseWarning['days_remaining'] }} day(s)</strong>
                        on <strong>{{ $licenseWarning['expires_at_label'] }}</strong>.
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('license-settings.edit') }}" class="btn btn-sm btn-outline-dark">Renew / Update</a>
                        <button type="button"
                                onclick="document.getElementById('license-warning-banner').style.display='none'; sessionStorage.setItem('{{ $dismissKey }}','1');"
                                style="background:none;border:none;font-size:18px;line-height:1;color:#555;cursor:pointer;padding:0 4px;"
                                aria-label="Close">&times;</button>
                    </div>
                </div>
            </div>
            <script>
                (function () {
                    if (sessionStorage.getItem('{{ $dismissKey }}') === '1') {
                        var el = document.getElementById('license-warning-banner');
                        if (el) el.style.display = 'none';
                    }
                })();
            </script>
        @endif

        @if ($errors->any())
            <div class="container-fluid px-4 pt-3">
                <div class="alert alert-danger border-0 shadow-sm mb-0">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <main class="flex-grow-1">
            @yield('content')
        </main>

        <script type="application/json" id="license-warning-json">@json($licenseWarning)</script>
        @stack('scripts')

        @include('layouts.footer')
    </div>
</body>
</html>
