<?php

namespace App\Jobs;

use App\Models\BillingInvoice;
use App\Models\BillingSubscription;
use App\Models\BillingTransaction;
use App\Models\BillingWebhookEvent;
use App\Models\LicenseConfig;
use App\Services\Billing\BillingWebhookAdapterFactory;
use App\Services\Billing\SyncLicenseFromSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBillingWebhookEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public int $eventId)
    {
    }

    public function handle(BillingWebhookAdapterFactory $adapterFactory, SyncLicenseFromSubscription $syncService): void
    {
        $event = BillingWebhookEvent::query()->find($this->eventId);
        if (! $event || $event->processing_status !== 'pending') {
            return;
        }

        $payload = json_decode((string) $event->payload, true);
        if (! is_array($payload)) {
            $event->forceFill([
                'processing_status' => 'failed',
                'error_message' => 'Invalid JSON payload for processing.',
            ])->save();

            return;
        }

        $adapter = $adapterFactory->forProvider((string) $event->provider);
        $normalized = $adapter->normalize($payload);

        $license = LicenseConfig::current() ?? LicenseConfig::query()->create([
            'plan_name' => 'Starter',
            'is_active' => true,
        ]);

        $subscription = $this->upsertSubscription($license, (string) $event->provider, $normalized['subscription'] ?? null);
        $invoice = $this->upsertInvoice((string) $event->provider, $normalized['invoice'] ?? null, $subscription);
        $this->upsertTransaction((string) $event->provider, $normalized['transaction'] ?? null, $invoice);

        if ($subscription) {
            $syncService->sync($license, $subscription);
        }

        $event->forceFill([
            'processing_status' => 'processed',
            'processed_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function failed(\Throwable $exception): void
    {
        $event = BillingWebhookEvent::query()->find($this->eventId);
        if (! $event) {
            return;
        }

        $event->forceFill([
            'processing_status' => 'failed',
            'error_message' => $exception->getMessage(),
        ])->save();
    }

    private function upsertSubscription(LicenseConfig $license, string $provider, mixed $subscriptionData): ?BillingSubscription
    {
        if (! is_array($subscriptionData)) {
            return null;
        }

        $providerSubscriptionId = (string) ($subscriptionData['id'] ?? '');
        if ($providerSubscriptionId === '') {
            return null;
        }

        return BillingSubscription::query()->updateOrCreate(
            ['provider_subscription_id' => $providerSubscriptionId],
            [
                'license_config_id' => $license->id,
                'provider' => $provider,
                'provider_customer_id' => $subscriptionData['customer_id'] ?? null,
                'plan_key' => (string) ($subscriptionData['plan_key'] ?? 'starter'),
                'status' => (string) ($subscriptionData['status'] ?? 'pending'),
                'amount' => $subscriptionData['amount'] ?? null,
                'currency' => (string) ($subscriptionData['currency'] ?? config('services.billing.default_currency', 'INR')),
                'trial_ends_at' => $subscriptionData['trial_ends_at'] ?? null,
                'renews_at' => $subscriptionData['renews_at'] ?? null,
                'canceled_at' => $subscriptionData['canceled_at'] ?? null,
                'metadata' => is_array($subscriptionData['metadata'] ?? null) ? $subscriptionData['metadata'] : null,
            ]
        );
    }

    private function upsertInvoice(string $provider, mixed $invoiceData, ?BillingSubscription $subscription): ?BillingInvoice
    {
        if (! is_array($invoiceData)) {
            return null;
        }

        $providerInvoiceId = (string) ($invoiceData['id'] ?? '');
        if ($providerInvoiceId === '') {
            return null;
        }

        if (! $subscription && ! empty($invoiceData['subscription_id'])) {
            $subscription = BillingSubscription::query()
                ->where('provider_subscription_id', (string) $invoiceData['subscription_id'])
                ->first();
        }

        return BillingInvoice::query()->updateOrCreate(
            ['provider_invoice_id' => $providerInvoiceId],
            [
                'subscription_id' => $subscription?->id,
                'provider' => $provider,
                'invoice_number' => $invoiceData['invoice_number'] ?? null,
                'amount_due' => $invoiceData['amount_due'] ?? null,
                'amount_paid' => $invoiceData['amount_paid'] ?? null,
                'currency' => (string) ($invoiceData['currency'] ?? config('services.billing.default_currency', 'INR')),
                'status' => (string) ($invoiceData['status'] ?? 'pending'),
                'period_start' => $invoiceData['period_start'] ?? null,
                'period_end' => $invoiceData['period_end'] ?? null,
                'due_date' => $invoiceData['due_date'] ?? null,
                'paid_at' => $invoiceData['paid_at'] ?? null,
                'raw_payload' => is_array($invoiceData['raw_payload'] ?? null) ? $invoiceData['raw_payload'] : $invoiceData,
            ]
        );
    }

    private function upsertTransaction(string $provider, mixed $transactionData, ?BillingInvoice $invoice): ?BillingTransaction
    {
        if (! is_array($transactionData)) {
            return null;
        }

        $providerTransactionId = (string) ($transactionData['id'] ?? '');
        if ($providerTransactionId === '') {
            return null;
        }

        if (! $invoice && ! empty($transactionData['invoice_id'])) {
            $invoice = BillingInvoice::query()
                ->where('provider_invoice_id', (string) $transactionData['invoice_id'])
                ->first();
        }

        return BillingTransaction::query()->updateOrCreate(
            ['provider_transaction_id' => $providerTransactionId],
            [
                'invoice_id' => $invoice?->id,
                'provider' => $provider,
                'gateway' => $transactionData['gateway'] ?? null,
                'status' => (string) ($transactionData['status'] ?? 'pending'),
                'amount' => $transactionData['amount'] ?? null,
                'currency' => (string) ($transactionData['currency'] ?? config('services.billing.default_currency', 'INR')),
                'paid_at' => $transactionData['paid_at'] ?? null,
                'raw_payload' => is_array($transactionData['raw_payload'] ?? null) ? $transactionData['raw_payload'] : $transactionData,
            ]
        );
    }
}
