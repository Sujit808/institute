<?php

namespace App\Services\Billing;

use App\Services\Billing\Adapters\BillingWebhookAdapter;
use App\Services\Billing\Adapters\GenericBillingWebhookAdapter;
use App\Services\Billing\Adapters\RazorpayBillingWebhookAdapter;
use App\Services\Billing\Adapters\StripeBillingWebhookAdapter;

class BillingWebhookAdapterFactory
{
    public function forProvider(string $provider): BillingWebhookAdapter
    {
        return match (strtolower($provider)) {
            'razorpay' => new RazorpayBillingWebhookAdapter,
            'stripe' => new StripeBillingWebhookAdapter,
            default => new GenericBillingWebhookAdapter,
        };
    }
}
