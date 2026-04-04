<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center px-3" style="background: radial-gradient(circle at top, #eaf2ff, #f4f7fb 45%);">
        <div class="card app-card border-0 shadow-lg" style="max-width: 620px; width: 100%;">
            <div class="card-body p-4 p-lg-5 text-center">
                <div class="mx-auto mb-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 72px; height: 72px; background: #fee2e2; color: #b91c1c; font-size: 1.8rem;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <span class="eyebrow">Error 403</span>
                <h1 class="h3 mt-2 mb-2">Access Denied</h1>
                <p class="text-body-secondary mb-3">
                    {{ $exception->getMessage() ?: 'You do not have permission to open this page.' }}
                </p>
                <p class="small text-body-secondary mb-4">
                    If this page should be available for your role, ask an administrator to review your module permissions.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                    @auth
                        <a class="btn btn-primary" href="{{ auth()->user()->isStudent() ? route('student.dashboard') : route('dashboard') }}">Go to Dashboard</a>
                        @if (auth()->user()->isSuperAdmin())
                            <a class="btn btn-outline-secondary" href="{{ route('settings.access-matrix') }}">Open Access Matrix</a>
                        @endif
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">Go to Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>
</html>