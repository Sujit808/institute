@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" data-module-page data-module="{{ $moduleKey }}">
    @php($auditRecords = collect($records))

    <section class="audit-hero p-4 p-lg-5 mb-4 text-white">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <span class="eyebrow text-white-50">Security Console</span>
                <h1 class="display-6 fw-bold mb-2">{{ $moduleConfig['title'] }}</h1>
                <p class="mb-0 text-white-50">Track high-impact actions, login activity, and operational trails in one place.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-light" href="{{ route($moduleKey.'.export.pdf') }}">Export PDF</a>
                <a class="btn btn-outline-light" href="{{ route($moduleKey.'.export.excel') }}">Export Excel</a>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="metric-card h-100">
                <div class="metric-label">Logs On This Page</div>
                <div class="metric-value">{{ $auditRecords->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card h-100">
                <div class="metric-label">Unique Users</div>
                <div class="metric-value">{{ $auditRecords->pluck('user.name')->filter()->unique()->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card h-100">
                <div class="metric-label">Unique Modules</div>
                <div class="metric-value">{{ $auditRecords->pluck('module')->filter()->unique()->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card h-100">
                <div class="metric-label">Login Events</div>
                <div class="metric-value">{{ $auditRecords->where('action', 'login')->count() }}</div>
            </div>
        </div>
    </section>

    <section class="card app-card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <form id="module-search-form" class="row gy-2 gx-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search Field</label>
                    <select class="form-select form-select-sm" name="search_field" id="search-field">
                        <option value="">All Fields</option>
                        @foreach ($moduleConfig['table_columns'] as $column)
                            <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search Term</label>
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Type to search..." id="search-term">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Sort By</label>
                    <select class="form-select form-select-sm" name="sort_by" id="sort-by">
                        <option value="">Default</option>
                        @foreach ($moduleConfig['table_columns'] as $column)
                            <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Order</label>
                    <select class="form-select form-select-sm" name="sort_order" id="sort-order">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Per Page</label>
                    <select class="form-select form-select-sm" name="per_page" id="per-page">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
                    <button type="reset" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card app-card border-0 shadow-sm">
        <div class="card-body p-0" data-module-table-wrapper>
            @include('modules.table', ['records' => $records, 'moduleConfig' => $moduleConfig, 'moduleKey' => $moduleKey, 'pagination' => $pagination ?? null])
        </div>
    </section>
</div>

<script type="application/json" id="module-config-json">@json($moduleConfig)</script>
<script type="application/json" id="module-lookups-json">@json($lookups)</script>
@endsection
