<?php

namespace App\Services\Billing\Adapters;

class StripeBillingWebhookAdapter implements BillingWebhookAdapter
{
    public function eventId(array $payload): ?string
    {
        return isset($payload['id']) && is_string($payload['id']) && $payload['id'] !== ''
            ? $payload['id']
            : null;
    }

    public function eventType(array $payload): ?string
    {
        return isset($payload['type']) && is_string($payload['type']) && $payload['type'] !== ''
            ? $payload['type']
            : null;
    }

    public function normalize(array $payload): array
    {
        $object = $payload['data']['object'] ?? null;
        if (! is_array($object)) {
            return [
                'subscription' => null,
                'invoice' => null,
                'transaction' => null,
            ];
        }

        $type = (string) ($payload['type'] ?? '');

        $subscription = null;
        $invoice = null;
        $transaction = null;

        if (str_starts_with($type, 'customer.subscription')) {
            $subscription = [
                'id' => $object['id'] ?? null,
                'customer_id' => $object['customer'] ?? null,
                'plan_key' => strtolower((string) ($object['metadata']['plan_key'] ?? 'starter')),
                'status' => strtolower((string) ($object['status'] ?? 'pending')),
                'amount' => isset($object['items']['data'][0]['price']['unit_amount']) ? ((float) $object['items']['data'][0]['price']['unit_amount']) / 100 : null,
                'currency' => $object['currency'] ?? null,
                'trial_ends_at' => isset($object['trial_end']) && $object['trial_end'] ? date('c', (int) $object['trial_end']) : null,
                'renews_at' => isset($object['current_period_end']) ? date('c', (int) $object['current_period_end']) : null,
                'canceled_at' => isset($object['canceled_at']) && $object['canceled_at'] ? date('c', (int) $object['canceled_at']) : null,
                'metadata' => $object['metadata'] ?? null,
            ];
        }

        if (str_starts_with($type, 'invoice.')) {
            $invoice = [
                'id' => $object['id'] ?? null,
                'subscription_id' => $object['subscription'] ?? null,
                'invoice_number' => $object['number'] ?? null,
                'amount_due' => isset($object['amount_due']) ? ((float) $object['amount_due']) / 100 : null,
                'amount_paid' => isset($object['amount_paid']) ? ((float) $object['amount_paid']) / 100 : null,
                'currency' => $object['currency'] ?? null,
                'status' => strtolower((string) ($object['status'] ?? 'pending')),
                'period_start' => isset($object['period_start']) ? date('Y-m-d', (int) $object['period_start']) : null,
                'period_end' => isset($object['period_end']) ? date('Y-m-d', (int) $object['period_end']) : null,
                'due_date' => isset($object['due_date']) && $object['due_date'] ? date('Y-m-d', (int) $object['due_date']) : null,
                'paid_at' => isset($object['status_transitions']['paid_at']) && $object['status_transitions']['paid_at'] ? date('c', (int) $object['status_transitions']['paid_at']) : null,
                'raw_payload' => $object,
            ];
        }

        if (str_starts_with($type, 'charge.') || str_starts_with($type, 'payment_intent.')) {
            $transaction = [
                'id' => $object['id'] ?? null,
                'invoice_id' => $object['invoice'] ?? null,
                'gateway' => 'stripe',
                'status' => strtolower((string) ($object['status'] ?? 'pending')),
                'amount' => isset($object['amount']) ? ((float) $object['amount']) / 100 : null,
                'currency' => $object['currency'] ?? null,
                'paid_at' => isset($object['created']) ? date('c', (int) $object['created']) : null,
                'raw_payload' => $object,
            ];
        }

        return [
            'subscription' => $subscription,
            'invoice' => $invoice,
            'transaction' => $transaction,
        ];
    }
}
