@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Super Admin Tools</span>
            <h1 class="h3 mb-1">Role Access Matrix</h1>
            <p class="text-body-secondary mb-0">Review default module access by role and verify which permission bundles are automatically applied.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Back to Dashboard</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach ($presets as $role => $permissions)
            <div class="col-lg-4">
                <div class="card app-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h2 class="h5 mb-0 text-capitalize">{{ str_replace('_', ' ', $role) }}</h2>
                            <span class="badge text-bg-primary">{{ count($permissions) }} modules</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($permissions as $permission)
                                <span class="badge rounded-pill text-bg-light border">{{ $permission }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card app-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Permission</th>
                            <th>Label</th>
                            <th class="text-center">Super Admin</th>
                            <th class="text-center">Admin</th>
                            <th class="text-center">HR</th>
                            <th class="text-center">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matrix as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['permission'] }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-center">@if ($row['super_admin'])<span class="badge text-bg-success">Yes</span>@else<span class="badge text-bg-secondary">No</span>@endif</td>
                                <td class="text-center">@if ($row['admin'])<span class="badge text-bg-success">Yes</span>@else<span class="badge text-bg-secondary">No</span>@endif</td>
                                <td class="text-center">@if ($row['hr'])<span class="badge text-bg-success">Yes</span>@else<span class="badge text-bg-secondary">No</span>@endif</td>
                                <td class="text-center">@if ($row['teacher'])<span class="badge text-bg-success">Yes</span>@else<span class="badge text-bg-secondary">No</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-card mt-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Special Routes Mapping</h2>
            <p class="text-body-secondary small mb-3">These routes do not directly map one-to-one with dynamic module URLs, so this table helps verify guard expectations.</p>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Label</th>
                            <th>Route Name</th>
                            <th>Required Permission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($specialRoutes as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item['label'] }}</td>
                                <td><code>{{ $item['route'] }}</code></td>
                                <td><span class="badge text-bg-light border">{{ $item['permission'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection