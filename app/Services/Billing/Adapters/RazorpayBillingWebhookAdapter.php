<?php

namespace App\Services\Billing\Adapters;

class RazorpayBillingWebhookAdapter implements BillingWebhookAdapter
{
    public function eventId(array $payload): ?string
    {
        return isset($payload['event_id']) && is_string($payload['event_id']) && $payload['event_id'] !== ''
            ? $payload['event_id']
            : (isset($payload['id']) && is_string($payload['id']) ? $payload['id'] : null);
    }

    public function eventType(array $payload): ?string
    {
        return isset($payload['event']) && is_string($payload['event']) && $payload['event'] !== ''
            ? $payload['event']
            : null;
    }

    public function normalize(array $payload): array
    {
        $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? null;
        $invoiceEntity = $payload['payload']['invoice']['entity'] ?? null;
        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

        return [
            'subscription' => is_array($subscriptionEntity) ? [
                'id' => $subscriptionEntity['id'] ?? null,
                'customer_id' => $subscriptionEntity['customer_id'] ?? null,
                'plan_key' => strtolower((string) ($subscriptionEntity['plan_id'] ?? 'starter')),
                'status' => strtolower((string) ($subscriptionEntity['status'] ?? 'pending')),
                'amount' => isset($subscriptionEntity['plan_id']) ? null : null,
                'currency' => $subscriptionEntity['currency'] ?? null,
                'trial_ends_at' => isset($subscriptionEntity['charge_at']) ? date('c', (int) $subscriptionEntity['charge_at']) : null,
                'renews_at' => isset($subscriptionEntity['current_end']) ? date('c', (int) $subscriptionEntity['current_end']) : null,
                'canceled_at' => isset($subscriptionEntity['ended_at']) && $subscriptionEntity['ended_at'] ? date('c', (int) $subscriptionEntity['ended_at']) : null,
                'metadata' => $subscriptionEntity['notes'] ?? null,
            ] : null,
            'invoice' => is_array($invoiceEntity) ? [
                'id' => $invoiceEntity['id'] ?? null,
                'subscription_id' => $invoiceEntity['subscription_id'] ?? null,
                'invoice_number' => $invoiceEntity['invoice_number'] ?? null,
                'amount_due' => isset($invoiceEntity['amount_due']) ? ((float) $invoiceEntity['amount_due']) / 100 : null,
                'amount_paid' => isset($invoiceEntity['amount_paid']) ? ((float) $invoiceEntity['amount_paid']) / 100 : null,
                'currency' => $invoiceEntity['currency'] ?? null,
                'status' => strtolower((string) ($invoiceEntity['status'] ?? 'pending')),
                'period_start' => isset($invoiceEntity['period_start']) ? date('Y-m-d', (int) $invoiceEntity['period_start']) : null,
                'period_end' => isset($invoiceEntity['period_end']) ? date('Y-m-d', (int) $invoiceEntity['period_end']) : null,
                'due_date' => isset($invoiceEntity['due_date']) ? date('Y-m-d', (int) $invoiceEntity['due_date']) : null,
                'paid_at' => isset($invoiceEntity['paid_at']) && $invoiceEntity['paid_at'] ? date('c', (int) $invoiceEntity['paid_at']) : null,
                'raw_payload' => $invoiceEntity,
            ] : null,
            'transaction' => is_array($paymentEntity) ? [
                'id' => $paymentEntity['id'] ?? null,
                'invoice_id' => $invoiceEntity['id'] ?? null,
                'gateway' => $paymentEntity['method'] ?? null,
                'status' => strtolower((string) ($paymentEntity['status'] ?? 'pending')),
                'amount' => isset($paymentEntity['amount']) ? ((float) $paymentEntity['amount']) / 100 : null,
                'currency' => $paymentEntity['currency'] ?? null,
                'paid_at' => isset($paymentEntity['created_at']) ? date('c', (int) $paymentEntity['created_at']) : null,
                'raw_payload' => $paymentEntity,
            ] : null,
        ];
    }
}
