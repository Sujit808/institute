<?php

namespace Tests\Feature;

use App\Models\BillingInvoice;
use App\Models\BillingSubscription;
use App\Models\BillingTransaction;
use App\Models\BillingWebhookEvent;
use App\Models\LicenseConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingWebhookFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_subscription_webhook_creates_subscription_and_syncs_license(): void
    {
        config()->set('services.billing.provider', 'test_provider');
        config()->set('services.billing.webhook_secret', 'secret123');

        $payload = [
            'id' => 'evt_001',
            'type' => 'subscription.updated',
            'data' => [
                'subscription' => [
                    'id' => 'sub_001',
                    'customer_id' => 'cus_001',
                    'plan_key' => 'professional',
                    'status' => 'active',
                    'amount' => 1999.00,
                    'currency' => 'INR',
                    'renews_at' => now()->addMonth()->toIso8601String(),
                    'metadata' => ['source' => 'test'],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'secret123');

        $response = $this->withHeaders([
            'X-Billing-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $payload);

        $response->assertStatus(202);

        $this->assertDatabaseHas('billing_subscriptions', [
            'provider_subscription_id' => 'sub_001',
            'status' => 'active',
            'plan_key' => 'professional',
        ]);

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_001',
            'processing_status' => 'processed',
        ]);

        $license = LicenseConfig::current();
        $this->assertNotNull($license);
        $this->assertSame('Professional', $license->plan_name);
        $this->assertTrue((bool) $license->is_active);
    }

    public function test_invalid_signature_rejects_webhook(): void
    {
        config()->set('services.billing.webhook_secret', 'secret123');

        $payload = [
            'id' => 'evt_002',
            'type' => 'subscription.updated',
            'data' => ['subscription' => ['id' => 'sub_002']],
        ];

        $response = $this->withHeaders([
            'X-Billing-Signature' => 'bad-signature',
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $payload);

        $response->assertStatus(401);

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_002',
            'processing_status' => 'rejected',
        ]);

        $this->assertSame(0, BillingSubscription::query()->count());
        $this->assertSame(1, BillingWebhookEvent::query()->count());
    }

    public function test_generic_invoice_and_transaction_events_are_processed(): void
    {
        config()->set('services.billing.provider', 'generic');
        config()->set('services.billing.webhook_secret', 'secret123');

        $subscriptionPayload = [
            'id' => 'evt_sub_003',
            'type' => 'subscription.updated',
            'data' => [
                'subscription' => [
                    'id' => 'sub_003',
                    'plan_key' => 'starter',
                    'status' => 'active',
                    'currency' => 'INR',
                ],
            ],
        ];
        $subRaw = json_encode($subscriptionPayload, JSON_THROW_ON_ERROR);
        $subSig = hash_hmac('sha256', $subRaw, 'secret123');

        $this->withHeaders([
            'X-Billing-Signature' => $subSig,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $subscriptionPayload)->assertStatus(202);

        $invoicePayload = [
            'id' => 'evt_inv_003',
            'type' => 'invoice.paid',
            'data' => [
                'invoice' => [
                    'id' => 'inv_003',
                    'subscription_id' => 'sub_003',
                    'invoice_number' => 'INV-003',
                    'amount_due' => 1500,
                    'amount_paid' => 1500,
                    'currency' => 'INR',
                    'status' => 'paid',
                    'paid_at' => now()->toIso8601String(),
                ],
            ],
        ];
        $invRaw = json_encode($invoicePayload, JSON_THROW_ON_ERROR);
        $invSig = hash_hmac('sha256', $invRaw, 'secret123');

        $this->withHeaders([
            'X-Billing-Signature' => $invSig,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $invoicePayload)->assertStatus(202);

        $transactionPayload = [
            'id' => 'evt_txn_003',
            'type' => 'transaction.succeeded',
            'data' => [
                'transaction' => [
                    'id' => 'txn_003',
                    'invoice_id' => 'inv_003',
                    'gateway' => 'card',
                    'status' => 'succeeded',
                    'amount' => 1500,
                    'currency' => 'INR',
                    'paid_at' => now()->toIso8601String(),
                ],
            ],
        ];
        $txnRaw = json_encode($transactionPayload, JSON_THROW_ON_ERROR);
        $txnSig = hash_hmac('sha256', $txnRaw, 'secret123');

        $this->withHeaders([
            'X-Billing-Signature' => $txnSig,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $transactionPayload)->assertStatus(202);

        $this->assertDatabaseHas('billing_invoices', [
            'provider_invoice_id' => 'inv_003',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('billing_transactions', [
            'provider_transaction_id' => 'txn_003',
            'status' => 'succeeded',
        ]);

        $invoice = BillingInvoice::query()->where('provider_invoice_id', 'inv_003')->first();
        $transaction = BillingTransaction::query()->where('provider_transaction_id', 'txn_003')->first();

        $this->assertNotNull($invoice);
        $this->assertNotNull($transaction);
        $this->assertSame($invoice?->id, $transaction?->invoice_id);
    }

    public function test_stripe_provider_payload_is_normalized_and_processed(): void
    {
        config()->set('services.billing.stripe_webhook_secret', 'stripe_secret_123');

        $payload = [
            'id' => 'evt_stripe_001',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_stripe_001',
                    'customer' => 'cus_stripe_001',
                    'status' => 'active',
                    'currency' => 'usd',
                    'current_period_end' => now()->addMonth()->timestamp,
                    'items' => [
                        'data' => [[
                            'price' => [
                                'unit_amount' => 4900,
                            ],
                        ]],
                    ],
                    'metadata' => [
                        'plan_key' => 'enterprise',
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $v1 = hash_hmac('sha256', $timestamp.'.'.$raw, 'stripe_secret_123');
        $signature = 't='.$timestamp.',v1='.$v1;

        $this->withHeaders([
            'X-Billing-Provider' => 'stripe',
            'Stripe-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $payload)->assertStatus(202);

        $this->assertDatabaseHas('billing_subscriptions', [
            'provider_subscription_id' => 'sub_stripe_001',
            'provider' => 'stripe',
            'plan_key' => 'enterprise',
            'status' => 'active',
        ]);
    }

    public function test_stripe_signature_with_old_timestamp_is_rejected(): void
    {
        config()->set('services.billing.stripe_webhook_secret', 'stripe_secret_123');
        config()->set('services.billing.webhook_tolerance', 60);

        $payload = [
            'id' => 'evt_stripe_old_001',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_old_001']],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $oldTimestamp = (string) (time() - 3600);
        $v1 = hash_hmac('sha256', $oldTimestamp.'.'.$raw, 'stripe_secret_123');

        $this->withHeaders([
            'X-Billing-Provider' => 'stripe',
            'Stripe-Signature' => 't='.$oldTimestamp.',v1='.$v1,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $payload)->assertStatus(401);

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_stripe_old_001',
            'processing_status' => 'rejected',
        ]);
    }

    public function test_razorpay_signature_header_is_supported(): void
    {
        config()->set('services.billing.razorpay_webhook_secret', 'razorpay_secret_123');

        $payload = [
            'event_id' => 'evt_rzp_001',
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => 'sub_rzp_001',
                        'customer_id' => 'cust_rzp_001',
                        'plan_id' => 'professional',
                        'status' => 'active',
                        'currency' => 'INR',
                        'current_end' => time() + 2592000,
                        'notes' => ['plan_key' => 'professional'],
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'razorpay_secret_123');

        $this->withHeaders([
            'X-Billing-Provider' => 'razorpay',
            'X-Razorpay-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/billing/webhook', $payload)->assertStatus(202);

        $this->assertDatabaseHas('billing_subscriptions', [
            'provider_subscription_id' => 'sub_rzp_001',
            'provider' => 'razorpay',
            'status' => 'active',
        ]);
    }
}
