@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Billing Control Center</h1>
            <p class="text-body-secondary mb-0">Subscription, invoices, and webhook processing monitor.</p>
        </div>
        <a href="{{ route('license-settings.edit') }}" class="btn btn-outline-primary btn-sm">Back to Master Control</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Subscriptions</div><div class="h5 mb-0">{{ $summary['subscriptions'] }}</div></div></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Active</div><div class="h5 mb-0 text-success">{{ $summary['active_subscriptions'] }}</div></div></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Invoices</div><div class="h5 mb-0">{{ $summary['invoices'] }}</div></div></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Paid Invoices</div><div class="h5 mb-0 text-success">{{ $summary['paid_invoices'] }}</div></div></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Pending Events</div><div class="h5 mb-0 text-warning">{{ $summary['pending_events'] }}</div></div></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Failed Events</div><div class="h5 mb-0 text-danger">{{ $summary['failed_events'] }}</div></div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Current Subscription Snapshot</div>
        <div class="card-body">
            @if($subscription)
                <div class="row g-3">
                    <div class="col-md-4"><strong>Provider:</strong> {{ strtoupper($subscription->provider) }}</div>
                    <div class="col-md-4"><strong>Plan Key:</strong> {{ $subscription->plan_key }}</div>
                    <div class="col-md-4"><strong>Status:</strong> {{ $subscription->status }}</div>
                    <div class="col-md-4"><strong>Renews At:</strong> {{ optional($subscription->renews_at)->format('d M Y H:i') ?? 'N/A' }}</div>
                    <div class="col-md-4"><strong>Amount:</strong> {{ $subscription->amount ? number_format((float)$subscription->amount, 2) : 'N/A' }} {{ $subscription->currency }}</div>
                    <div class="col-md-4"><strong>License Plan:</strong> {{ $license?->plan_name ?? 'N/A' }}</div>
                </div>
            @else
                <p class="mb-0 text-body-secondary">No subscription data received yet.</p>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Recent Invoices</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Invoice</th>
                                <th>Status</th>
                                <th>Due</th>
                                <th>Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->provider_invoice_id ?? '-' }}</td>
                                    <td>{{ $invoice->invoice_number ?? '-' }}</td>
                                    <td>{{ $invoice->status }}</td>
                                    <td>{{ $invoice->amount_due ? number_format((float)$invoice->amount_due, 2) : '-' }} {{ $invoice->currency }}</td>
                                    <td>{{ optional($invoice->paid_at)->format('d M Y') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-3">No invoices yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Recent Webhook Events</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEvents as $event)
                                <tr>
                                    <td>{{ $event->provider_event_id }}</td>
                                    <td>{{ $event->event_type }}</td>
                                    <td>
                                        <span class="badge {{ $event->processing_status === 'processed' ? 'text-bg-success' : ($event->processing_status === 'failed' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                            {{ $event->processing_status }}
                                        </span>
                                    </td>
                                    <td>{{ optional($event->created_at)->format('d M H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-3">No events yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
