<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\BillingSubscription;
use App\Models\BillingWebhookEvent;
use App\Models\LicenseConfig;
use Illuminate\View\View;

class BillingSettingsController extends Controller
{
    public function index(): View
    {
        $license = LicenseConfig::current();

        $subscription = BillingSubscription::query()
            ->latest('id')
            ->first();

        $summary = [
            'subscriptions' => BillingSubscription::query()->count(),
            'active_subscriptions' => BillingSubscription::query()->whereIn('status', ['trialing', 'active', 'past_due', 'grace_period'])->count(),
            'invoices' => BillingInvoice::query()->count(),
            'paid_invoices' => BillingInvoice::query()->where('status', 'paid')->count(),
            'pending_events' => BillingWebhookEvent::query()->where('processing_status', 'pending')->count(),
            'failed_events' => BillingWebhookEvent::query()->where('processing_status', 'failed')->count(),
        ];

        $recentInvoices = BillingInvoice::query()->latest('id')->limit(10)->get();
        $recentEvents = BillingWebhookEvent::query()->latest('id')->limit(10)->get();

        return view('billing.index', [
            'license' => $license,
            'subscription' => $subscription,
            'summary' => $summary,
            'recentInvoices' => $recentInvoices,
            'recentEvents' => $recentEvents,
        ]);
    }
}
