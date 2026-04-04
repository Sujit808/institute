<?php

namespace App\Services\Billing\Adapters;

class GenericBillingWebhookAdapter implements BillingWebhookAdapter
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
        return [
            'subscription' => is_array($payload['data']['subscription'] ?? null) ? $payload['data']['subscription'] : null,
            'invoice' => is_array($payload['data']['invoice'] ?? null) ? $payload['data']['invoice'] : null,
            'transaction' => is_array($payload['data']['transaction'] ?? null) ? $payload['data']['transaction'] : null,
        ];
    }
}
