<?php

namespace App\Services\Billing\Adapters;

interface BillingWebhookAdapter
{
    public function eventId(array $payload): ?string;

    public function eventType(array $payload): ?string;

    public function normalize(array $payload): array;
}
