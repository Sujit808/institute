@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" data-module-page data-module="{{ $moduleKey }}">
    @php($questionRecords = collect($records))
    @php($setCodes = ['A', 'B', 'C', 'D', 'E'])
    @php($setCounts = $questionRecords->groupBy('set_code')->map(fn ($items) => $items->count()))
    @php($statusCounts = $questionRecords->groupBy('status')->map(fn ($items) => $items->count()))
    @php($totalMarks = $questionRecords->sum(fn ($item) => (float) ($item->marks ?? 0)))

    <section class="question-bank-hero p-4 p-lg-5 mb-4 text-white">
        <div class="row g-4 align-items-end">
            <div class="col-lg-8">
                <span class="eyebrow text-white-50">Question Authoring</span>
                <h1 class="display-6 fw-bold mb-2">{{ $moduleConfig['title'] }}</h1>
                <p class="mb-0 text-white-50">Build exam set wise MCQ banks with quick editing, mark weights, and answer key control.</p>
            </div>
            <div class="col-lg-4">
                <div class="d-grid gap-2">
                    @unless (! empty($moduleConfig['readonly']))
                        <button class="btn btn-light" type="button" data-open-create-modal>
                            <i class="bi bi-plus-circle"></i>
                            <span>Add {{ $moduleConfig['singular'] }}</span>
                        </button>
                    @endunless
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-light w-100" href="{{ route($moduleKey.'.export.pdf') }}">Export PDF</a>
                        <a class="btn btn-outline-light w-100" href="{{ route($moduleKey.'.export.excel') }}">Export Excel</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="metric-card h-100">
                <div class="metric-label">Questions On Page</div>
                <div class="metric-value">{{ $questionRecords->count() }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card h-100">
                <div class="metric-label">Active Questions</div>
                <div class="metric-value">{{ (int) ($statusCounts['active'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card h-100">
                <div class="metric-label">Set Coverage</div>
                <div class="metric-value">{{ $questionRecords->pluck('set_code')->filter()->unique()->count() }}/5</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-card h-100">
                <div class="metric-label">Total Marks</div>
                <div class="metric-value">{{ $totalMarks }}</div>
            </div>
        </div>
    </section>

    <section class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <span class="eyebrow">Set Planner</span>
                    <h2 class="h5 mb-0">Question Distribution by Set</h2>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($setCodes as $setCode)
                    @php($count = (int) ($setCounts->get($setCode) ?? 0))
                    @php($barWidth = min(100, $count * 25))
                    <div class="col-lg col-md-4 col-6">
                        <div class="set-coverage-tile h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge text-bg-light border">Set {{ $setCode }}</span>
                                <span class="small fw-semibold {{ $count > 0 ? 'text-success' : 'text-body-secondary' }}">{{ $count }} question(s)</span>
                            </div>
                            <div class="coverage-bar-wrap">
                                <div class="coverage-bar coverage-bar--{{ $barWidth }}"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
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
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search questions, exam, set..." id="search-term">
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

    @unless (! empty($moduleConfig['readonly']))
        <div class="modal fade" id="moduleCrudModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="eyebrow">Question Builder</span>
                            <h2 class="h4 mb-0" data-modal-title>Add {{ $moduleConfig['singular'] }}</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="alert d-none" data-form-alert></div>
                        <form data-module-form enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="_method" value="POST" data-form-method>
                            <div class="row g-3">
                                @foreach ($moduleConfig['fields'] as $field)
                                    <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}" data-field="{{ $field['name'] }}">
                                        <label class="form-label" for="field_{{ $field['name'] }}">{{ $field['label'] }}</label>
                                        @if ($field['type'] === 'textarea')
                                            <textarea class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}" rows="3"></textarea>
                                        @elseif ($field['type'] === 'select')
                                            <select class="form-select" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}{{ ! empty($field['multiple']) ? '[]' : '' }}" {{ ! empty($field['multiple']) ? 'multiple' : '' }}>
                                                <option value="">Select {{ $field['label'] }}</option>
                                                @foreach (($field['lookup'] ?? null) ? ($lookups[$field['lookup']] ?? []) : ($field['options'] ?? []) as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input class="form-control" id="field_{{ $field['name'] }}" name="{{ $field['name'] }}{{ ! empty($field['multiple']) ? '[]' : '' }}" type="{{ $field['type'] }}" {{ ! empty($field['multiple']) ? 'multiple' : '' }}>
                                        @endif
                                        @if (! empty($field['help']))
                                            <div class="form-text">{{ $field['help'] }}</div>
                                        @endif
                                        <div class="invalid-feedback d-block small" data-error-for="{{ $field['name'] }}"></div>
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" data-submit-module-form>Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
</div>

<script type="application/json" id="module-config-json">@json($moduleConfig)</script>
<script type="application/json" id="module-lookups-json">@json($lookups)</script>
@endsection
