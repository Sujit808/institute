

<style>
    .module-menu-link.active{color: #ffffff;}
        .navbar-expand-xl .navbar-nav .nav-link {font-size: 13px;}
</style>
@php
    $org = $organizationContext['organization'] ?? null;
    $activeBranch = $organizationContext['active_branch'] ?? null;
    $availableBranches = $organizationContext['branches'] ?? collect();
    $orgName = $org?->name ?: config('app.name', 'SchoolSphere');
    $orgTypeLabel = ucfirst((string) ($org?->type ?: 'school'));
    $brandShort = strtoupper(substr((string) ($org?->short_name ?: $orgName), 0, 2));
@endphp

<nav class="navbar navbar-expand-xl sticky-top app-navbar border-bottom">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 gap-xl-3" href="{{ route('dashboard') }}">
            @if (!empty($org?->logo_path))
                <img src="{{ asset('storage/'.$org->logo_path) }}" alt="{{ $orgName }}" class="rounded-circle border" style="width: 34px; height: 34px; object-fit: cover;">
            @else
                <span class="brand-mark">{{ $brandShort }}</span>
            @endif
            <span class="brand-text-wrap">
                <span class="d-block fw-semibold brand-org-name">{{ $orgName }}</span>
                <small class="text-body-secondary d-none d-lg-block">{{ $orgTypeLabel }}{{ $activeBranch ? ' | '.$activeBranch->name : ' | Management Console' }}</small>
            </span>
        </a>

        @auth
        @php
            $isReader = auth()->user()->role === 'reader';
            $navItems = collect(\App\Support\SchoolModuleRegistry::navigation(auth()->user()))->keyBy('key');
            $menuGroups = [
                ['label' => 'Student', 'icon' => 'bi-people', 'items' => ['students', 'attendance', 'results', 'fees', 'fee-structures', 'study-materials', 'master-calendar', 'student-calendar', 'certificates']],
                ['label' => 'Academic', 'icon' => 'bi-journal-bookmark', 'items' => ['classes', 'sections', 'subjects', 'exams', 'exam-papers', 'timetable']],
                // Show payroll for admin, hr, and super admin
                ['label' => 'Staff ', 'icon' => 'bi-person-badge', 'items' => array_filter(['staff', 'leaves', (in_array(auth()->user()->role, ['admin', 'hr', 'super_admin']) ? 'payroll' : null), 'my-attendance'])],
                ['label' => 'Communication', 'icon' => 'bi-megaphone', 'items' => ['notifications', 'calendar', 'holidays', 'day-wise-customization']],
                ['label' => 'Identity', 'icon' => 'bi-person-vcard', 'items' => ['icards']],
                ['label' => 'Biometrics', 'icon' => 'bi-fingerprint', 'items' => ['biometric-devices', 'biometric-enrollments']],
                ['label' => 'Settings', 'icon' => 'bi-sliders2', 'items' => ['license-settings', 'billing-settings', 'institute-settings', 'access-matrix']],
            ];
            $availableGroups = collect($menuGroups)->map(function ($group) use ($navItems) {
                $items = collect($group['items'])->map(function ($key) use ($navItems) {
                    if ($key === 'master-calendar') {
                        if (! auth()->user()->canAccessModule('attendance')) return null;
                        return ['key' => 'master.calendar', 'title' => 'Master Calendar', 'route' => route('master.calendar')];
                    }
                    if ($key === 'student-calendar') {
                            if (! auth()->user()->canAccessModule('students')) return null;
                            return ['key' => 'students.calendar.index', 'title' => 'Student Calendar', 'route' => route('students.calendar.index')];
                    }
                    if ($key === 'fee-structures') {
                        if (! auth()->user()->canAccessModule('fees')) return null;
                        return ['key' => 'fee-structures.index', 'title' => 'Fee Structure', 'route' => route('fee-structures.index')];
                    }
                    if ($key === 'certificates') {
                        if (! auth()->user()->canAccessModule('students')) return null;
                        return ['key' => 'certificates.index', 'title' => 'Certificates', 'route' => route('certificates.index')];
                    }
                    if ($key === 'day-wise-customization') {
                        if (! auth()->user()->canAccessModule('holidays')) return null;
                        return ['key' => 'day-wise-customization.index', 'title' => 'Day-wise Customization', 'route' => route('day-wise-customization.index')];
                    }
                    if ($key === 'my-attendance') {
                        if (auth()->user()->isStudent() || ! auth()->user()->canAccessModule('attendance')) return null;
                        return ['key' => 'my.attendance', 'title' => 'My Attendance', 'route' => route('my.attendance')];
                    }
                    if ($key === 'icards') {
                        if (! auth()->user()->canAccessModule('icards')) return null;
                        return ['key' => 'icards', 'title' => 'iCards', 'route' => route('icards.index')];
                    }
                    if ($key === 'license-settings' && auth()->user()->isSuperAdmin()) {
                        return ['key' => 'license-settings', 'title' => 'Master Control', 'route' => route('license-settings.edit')];
                    }
                    if ($key === 'institute-settings' && auth()->user()->isSuperAdmin()) {
                        return ['key' => 'institute-settings', 'title' => 'Institute Setup', 'route' => route('institute-settings.edit')];
                    }
                    if ($key === 'billing-settings' && auth()->user()->isSuperAdmin()) {
                        return ['key' => 'billing-settings.index', 'title' => 'Billing Settings', 'route' => route('billing-settings.index')];
                    }
                    if ($key === 'access-matrix' && auth()->user()->isSuperAdmin()) {
                        return ['key' => 'settings.access-matrix', 'title' => 'Access Matrix', 'route' => route('settings.access-matrix')];
                    }
                    if ($key === 'quotations' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) && auth()->user()->canAccessModule('quotations')) {
                        return ['key' => 'quotations', 'title' => 'Quotations', 'route' => route('quotations.create')];
                    }
                    return $navItems->get($key);
                })->filter()->values()->all();
                return array_merge($group, ['items' => $items]);
            })->filter(fn($g) => count($g['items']) > 0)->values();
        @endphp
        @endauth

        

        

        <div class="collapse navbar-collapse" id="mainNavbar">
            @auth

                <div class="nav-layout me-auto w-100">
                    @if (auth()->user()->isStudent())
                        <div class="d-none d-xl-flex align-items-center gap-2 student-portal-nav" style="justify-content: center;">
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}">Dashboard</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.profile') ? 'active' : '' }}" href="{{ route('student.profile') }}">My Details</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.attendance') ? 'active' : '' }}" href="{{ route('student.attendance') }}">Attendance</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.fees') ? 'active' : '' }}" href="{{ route('student.fees') }}">Fees</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.results') ? 'active' : '' }}" href="{{ route('student.results') }}">Results</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.mycalendar') ? 'active' : '' }}" href="{{ route('student.mycalendar') }}">My Calendar</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.exams') ? 'active' : '' }}" href="{{ route('student.exams') }}">My Exams</a>
                            <a class="nav-link module-menu-link {{ request()->routeIs('student.books*') ? 'active' : '' }}" href="{{ route('student.books') }}">Books</a>
                        </div>

                        <div class="d-xl-none mobile-nav-stack mt-3 mt-xl-0">
                            <a class="mobile-dashboard-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                            <div class="mobile-nav-links">
                                <a class="mobile-nav-link {{ request()->routeIs('student.profile') ? 'active' : '' }}" href="{{ route('student.profile') }}">My Details</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.attendance') ? 'active' : '' }}" href="{{ route('student.attendance') }}">Attendance</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.fees') ? 'active' : '' }}" href="{{ route('student.fees') }}">Fees</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.results') ? 'active' : '' }}" href="{{ route('student.results') }}">Results</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.mycalendar') ? 'active' : '' }}" href="{{ route('student.mycalendar') }}">My Calendar</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.exams') ? 'active' : '' }}" href="{{ route('student.exams') }}">My Exams</a>
                                <a class="mobile-nav-link {{ request()->routeIs('student.books*') ? 'active' : '' }}" href="{{ route('student.books') }}">Books & Materials</a>
                            </div>
                        </div>
                    @else
                    <div class="d-none d-xl-flex align-items-center gap-2">
                        <ul class="navbar-nav align-items-xl-center gap-xl-2 m-auto">
                            <li class="nav-item">
                                <a class="nav-link {{ $isReader ? 'reader-nav-link' : 'module-menu-link' }} {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                            </li>

                            @if ($isReader)
                                @php
                                    $readerMenuActive = $availableGroups->contains(function ($group) {
                                        return collect($group['items'])->contains(function ($item) {
                                            return request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']);
                                        });
                                    });
                                @endphp
                                <li class="nav-item dropdown">
                                    <button class="nav-link reader-nav-link dropdown-toggle {{ $readerMenuActive ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-grid-1x2-fill me-1"></i>Reader Modules
                                        <span class="module-count-badge">{{ $availableGroups->count() }}</span>
                                    </button>
                                    <div class="dropdown-menu module-submenu reader-mega-submenu shadow-sm border-0">
                                        <div class="mega-menu-grid reader-mega-grid">
                                            @foreach ($availableGroups as $group)
                                                <div class="mega-menu-section">
                                                    <div class="mega-menu-title d-flex align-items-center justify-content-between gap-2">
                                                        <span><i class="bi {{ $group['icon'] }} me-1"></i>{{ $group['label'] }}</span>
                                                        <span class="module-count-badge">{{ count($group['items']) }}</span>
                                                    </div>
                                                    <div class="mega-quick-actions">
                                                        @foreach (array_slice($group['items'], 0, 2) as $quickItem)
                                                            <a class="quick-action-chip" href="{{ $quickItem['route'] }}">{{ $quickItem['title'] }}</a>
                                                        @endforeach
                                                    </div>
                                                    <div class="mega-menu-links single-column">
                                                        @foreach ($group['items'] as $item)
                                                            <a class="dropdown-item {{ request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']) ? 'active' : '' }}" href="{{ $item['route'] }}">{{ $item['title'] }}</a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </li>
                            @else
                                @foreach ($availableGroups as $group)
                                    @php
                                        $groupActive = collect($group['items'])->contains(function ($item) {
                                            return request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']);
                                        });
                                        $alignMenuEnd = $loop->last || $loop->remaining < 2;
                                        $itemChunks = array_chunk($group['items'], max(1, (int) ceil(count($group['items']) / 2)));
                                    @endphp
                                    <li class="nav-item dropdown">
                                        <button class="nav-link module-menu-link dropdown-toggle {{ $groupActive ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi {{ $group['icon'] }} me-1"></i>{{ $group['label'] }}
                                            <span class="module-count-badge">{{ count($group['items']) }}</span>
                                        </button>
                                        <div class="dropdown-menu module-submenu mega-dropdown shadow-sm border-0 {{ $alignMenuEnd ? 'dropdown-menu-end' : '' }}">
                                            <div class="mega-menu-header">
                                                <div class="mega-menu-title mb-0"><i class="bi {{ $group['icon'] }} me-1"></i>{{ $group['label'] }}</div>
                                                <div class="mega-quick-actions justify-content-end">
                                                    @foreach (array_slice($group['items'], 0, 2) as $quickItem)
                                                        <a class="quick-action-chip" href="{{ $quickItem['route'] }}">{{ $quickItem['title'] }}</a>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mega-menu-grid">
                                                @foreach ($itemChunks as $chunk)
                                                    <div class="mega-menu-links">
                                                        @foreach ($chunk as $item)
                                                            <a class="dropdown-item {{ request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']) ? 'active' : '' }}" href="{{ $item['route'] }}">{{ $item['title'] }}</a>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    <div class="d-xl-none mobile-nav-stack mt-3 mt-xl-0">
                        <a class="mobile-dashboard-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>

                        <div class="accordion mobile-nav-accordion" id="mobileNavAccordion">
                            @foreach ($availableGroups as $group)
                                @php
                                    $groupActive = collect($group['items'])->contains(function ($item) {
                                        return request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']);
                                    });
                                @endphp
                                <div class="accordion-item mobile-nav-item">
                                    <h2 class="accordion-header" id="heading-{{ \Illuminate\Support\Str::slug($group['label']) }}">
                                        <button class="accordion-button {{ $groupActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ \Illuminate\Support\Str::slug($group['label']) }}" aria-expanded="{{ $groupActive ? 'true' : 'false' }}" aria-controls="collapse-{{ \Illuminate\Support\Str::slug($group['label']) }}">
                                            <i class="bi {{ $group['icon'] }} me-2"></i>{{ $isReader ? str_replace(' Module', '', $group['label']) : $group['label'] }}
                                            <span class="module-count-badge ms-auto me-2">{{ count($group['items']) }}</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ \Illuminate\Support\Str::slug($group['label']) }}" class="accordion-collapse collapse {{ $groupActive ? 'show' : '' }}" aria-labelledby="heading-{{ \Illuminate\Support\Str::slug($group['label']) }}" data-bs-parent="#mobileNavAccordion">
                                        <div class="accordion-body">
                                            <div class="mobile-nav-links">
                                                @foreach ($group['items'] as $item)
                                                    <a class="mobile-nav-link {{ request()->routeIs($item['key'].'.*') || request()->routeIs($item['key']) ? 'active' : '' }}" href="{{ $item['route'] }}">{{ $item['title'] }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            @endauth

        </div>
        <div class="header-user-controls d-flex align-items-center gap-1 gap-sm-2 ms-auto me-0 flex-shrink-0">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        @auth
                @if (($availableBranches->count() ?? 0) > 1)
                    <div class="dropdown d-none d-sm-block">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{ $activeBranch?->name ?? 'Select Branch' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            @foreach ($availableBranches as $branch)
                                <li>
                                    <form method="POST" action="{{ route('institute-settings.switch-branch') }}">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <button type="submit" class="dropdown-item {{ (int) ($activeBranch?->id ?? 0) === (int) $branch->id ? 'active' : '' }}">
                                            {{ $branch->name }}
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" type="button" style="padding: 0.35rem 0.75rem;">
                        <?php
                            $headerUser = \Illuminate\Support\Facades\Auth::user();
                            $headerNameParts = preg_split('/\s+/', trim((string) $headerUser->name)) ?: [];
                            $headerFirst = $headerNameParts[0] ?? '';
                            $headerLast = $headerNameParts[count($headerNameParts) - 1] ?? '';
                            $headerInitials = strtoupper(substr($headerFirst, 0, 1) . substr($headerLast ?: $headerFirst, 0, 1));
                        ?>
                        <?php if (!empty($headerUser->photo)): ?>
                            <img src="{{ asset('storage/' . $headerUser->photo) }}" alt="{{ $headerUser->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width: 32px; height: 32px; background: linear-gradient(135deg, #1167b1, #0f766e); font-size: 12px;">
                                {{ $headerInitials }}
                            </div>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 220px;">
                        <li><span class="dropdown-item-text fw-semibold" style="font-size: 0.82rem;">{{ $headerUser->name }}</span></li>
                        <li><span class="dropdown-item-text text-body-secondary" style="font-size: 0.78rem; padding-top: 0;">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-item-text text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em; color: var(--app-muted); padding-bottom: 0.1rem;"><i class="bi bi-circle-half me-1"></i>Theme</span></li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="light">
                                <i class="bi bi-sun me-2 text-body-secondary"></i>
                                <span>Light Mode</span>
                                <i class="bi bi-check2 ms-auto text-primary d-none" data-theme-check></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="dark">
                                <i class="bi bi-moon-stars me-2 text-body-secondary"></i>
                                <span>Dark Mode</span>
                                <i class="bi bi-check2 ms-auto text-primary d-none" data-theme-check></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        @if (!auth()->user()->isStudent())
                            @php
                                $dropdownAdminItems = [];
                                if (auth()->user()->isSuperAdmin()) {
                                    $dropdownAdminItems[] = ['icon' => 'bi-journal-text',     'title' => 'Audit Logs',       'route' => route('audit-logs.index')];
                                    $dropdownAdminItems[] = ['icon' => 'bi-grid-3x3-gap',    'title' => 'Access Matrix',    'route' => route('settings.access-matrix')];
                                    $dropdownAdminItems[] = ['icon' => 'bi-sliders',          'title' => 'Master Control',  'route' => route('license-settings.edit')];
                                    $dropdownAdminItems[] = ['icon' => 'bi-building',          'title' => 'Institute Setup',  'route' => route('institute-settings.edit')];
                                }
                                if (auth()->user()->canAccessModule('quotations')) {
                                    $dropdownAdminItems[] = ['icon' => 'bi-file-earmark-text', 'title' => 'Quotations',       'route' => route('quotations.create')];
                                }
                            @endphp
                            @if (!empty($dropdownAdminItems))
                                <li><span class="dropdown-item-text text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em; color: var(--app-muted); padding-bottom: 0.1rem;"><i class="bi bi-shield-lock me-1"></i>Admin</span></li>
                                @foreach ($dropdownAdminItems as $dai)
                                    <li><a class="dropdown-item" href="{{ $dai['route'] }}"><i class="bi {{ $dai['icon'] }} me-2 text-body-secondary"></i>{{ $dai['title'] }}</a></li>
                                @endforeach
                                <li><hr class="dropdown-divider"></li>
                            @endif
                        @endif
                        <li><a class="dropdown-item" href="{{ auth()->user()->isStudent() ? route('student.password.edit') : route('password.change.edit') }}"><i class="bi bi-key me-2 text-body-secondary"></i>Change Password</a></li>
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('password.hash-check.form') }}"><i class="bi bi-shield-check me-2 text-body-secondary"></i>Password Reset Utility</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li>
                    </ul>
                </div>
            @endauth
            @guest
                <a class="btn btn-sm btn-primary" href="{{ route('login') }}">Login</a>
            @endguest
        </div>
    </div>
</nav>
