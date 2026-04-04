@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Sales Documents</span>
            <h1 class="h2 mb-1">Quotation History</h1>
            <p class="text-body-secondary mb-0">Review previously generated quotations, open them in the browser, or download the saved PDF again.</p>
        </div>
        <div>
            <a href="{{ route('quotations.create') }}" class="btn btn-primary">New Quotation</a>
        </div>
    </div>

    <div class="card app-card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('quotations.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Document Type</label>
                    <select name="document_type" class="form-select">
                        <option value="">All</option>
                        @foreach (['Quotation', 'Proposal', 'Invoice'] as $type)
                            <option value="{{ $type }}" {{ ($filters['document_type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Client / Institute</label>
                    <input type="text" name="client" class="form-control" value="{{ $filters['client'] ?? '' }}" placeholder="Search client or institute">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="include_archived" id="include_archived" value="1" {{ !empty($filters['include_archived']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="include_archived">Show archived</label>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card border-0 shadow-sm">
        <div class="card-body p-0">
            @if ($quotations->isEmpty())
                <div class="p-4 text-body-secondary">No quotation history found yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Quotation No</th>
                                <th>Document Type</th>
                                <th>Client</th>
                                <th>Institute</th>
                                <th>Total</th>
                                <th>Generated At</th>
                                <th>Status</th>
                                <th>Last Action</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotations as $quotation)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $quotation->quotation_no }}</td>
                                    <td>{{ $quotation->document_type }}</td>
                                    <td>{{ $quotation->client['name'] ?? 'N/A' }}</td>
                                    <td>{{ $quotation->client['institute_name'] ?? 'N/A' }}</td>
                                    <td>{{ $quotation->currency }} {{ number_format((float) $quotation->grand_total, 2) }}</td>
                                    <td>{{ optional($quotation->generated_at)->timezone(config('app.timezone'))->format('d M Y, h:i:s A') }}</td>
                                    <td>
                                        <span class="badge {{ $quotation->trashed() ? 'text-bg-warning' : 'text-bg-success' }} text-uppercase">
                                            {{ $quotation->trashed() ? 'Archived' : 'Active' }}
                                        </span>
                                    </td>
                                    <td><span class="badge text-bg-light text-uppercase">{{ str_replace('-', ' ', (string) $quotation->last_action) }}</span></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('quotations.show', $quotation->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="{{ route('quotations.download-saved', $quotation->id) }}" class="btn btn-sm btn-primary">Download</a>
                                            <a href="{{ route('quotations.edit', $quotation->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <a href="{{ route('quotations.reuse', $quotation->id) }}" class="btn btn-sm btn-outline-dark">Reuse</a>
                                            @if (! $quotation->trashed())
                                                <form method="POST" action="{{ route('quotations.archive', $quotation->id) }}" onsubmit="return confirm('Archive this quotation?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-top">
                    {{ $quotations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection