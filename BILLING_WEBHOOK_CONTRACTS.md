# Billing Webhook Contracts

Date: 2026-03-29

This document defines webhook headers, signature rules, and payload contract expectations for billing providers.

## 1. Common Endpoint

- Method: `POST`
- URL: `/api/billing/webhook`
- Rate limit: `120 req/min`
- Provider override header: `X-Billing-Provider` (optional)

Provider priority:
- If `X-Billing-Provider` is present, it is used.
- Else falls back to `BILLING_PROVIDER` from config.

## 2. Signature Verification Rules

### Generic
- Header: `X-Billing-Signature`
- Signature: `hex(hmac_sha256(raw_body, BILLING_WEBHOOK_SECRET))`

### Stripe
- Header: `Stripe-Signature`
- Format: `t=<unix_timestamp>,v1=<hex_signature>[,v1=<alt_signature>]`
- Signed payload: `<t>.<raw_body>`
- Signature: `hex(hmac_sha256(signed_payload, STRIPE_WEBHOOK_SECRET))`
- Timestamp tolerance: `BILLING_WEBHOOK_TOLERANCE` seconds (default 300)

### Razorpay
- Header: `X-Razorpay-Signature`
- Signature: `hex(hmac_sha256(raw_body, RAZORPAY_WEBHOOK_SECRET))`

## 3. Supported Payload Shapes

### Generic contract
```json
{
  "id": "evt_001",
  "type": "subscription.updated",
  "data": {
    "subscription": {
      "id": "sub_001",
      "customer_id": "cus_001",
      "plan_key": "professional",
      "status": "active",
      "amount": 1999,
      "currency": "INR",
      "trial_ends_at": "2026-04-10T00:00:00Z",
      "renews_at": "2026-05-01T00:00:00Z",
      "canceled_at": null,
      "metadata": {"source": "portal"}
    },
    "invoice": {
      "id": "inv_001",
      "subscription_id": "sub_001",
      "invoice_number": "INV-001",
      "amount_due": 1999,
      "amount_paid": 1999,
      "currency": "INR",
      "status": "paid",
      "period_start": "2026-04-01",
      "period_end": "2026-04-30",
      "due_date": "2026-04-05",
      "paid_at": "2026-04-02T10:20:00Z"
    },
    "transaction": {
      "id": "txn_001",
      "invoice_id": "inv_001",
      "gateway": "card",
      "status": "succeeded",
      "amount": 1999,
      "currency": "INR",
      "paid_at": "2026-04-02T10:20:00Z"
    }
  }
}
```

### Stripe contract notes
- Event id: `id`
- Event type: `type`
- Data object: `data.object`
- Adapter extracts subscription/invoice/charge fields into the generic normalized structure.

### Razorpay contract notes
- Event id: `event_id` (fallback `id`)
- Event type: `event`
- Data objects:
  - `payload.subscription.entity`
  - `payload.invoice.entity`
  - `payload.payment.entity`
- Amount conversion: paise to major units (`/100`).

## 4. Processing Behavior

- Webhook event is stored idempotently by `provider_event_id`.
- Valid signatures are accepted with `202` and processed via queue job `ProcessBillingWebhookEvent`.
- Invalid signatures are rejected with `401` and logged as `rejected`.
- Event status lifecycle: `pending` -> `processed|failed|rejected`.

## 5. Required Environment Variables

- `BILLING_PROVIDER=generic|stripe|razorpay`
- `BILLING_WEBHOOK_SECRET=...`
- `STRIPE_WEBHOOK_SECRET=...`
- `RAZORPAY_WEBHOOK_SECRET=...`
- `BILLING_WEBHOOK_TOLERANCE=300`
- `BILLING_DEFAULT_CURRENCY=INR`

## 6. Example Signature Generation

### Generic / Razorpay (PHP)
```php
$signature = hash_hmac('sha256', $rawBody, $secret);
```

### Stripe (PHP)
```php
$timestamp = time();
$signedPayload = $timestamp.'.'.$rawBody;
$v1 = hash_hmac('sha256', $signedPayload, $stripeSecret);
$header = 't='.$timestamp.',v1='.$v1;
```

## 7. QA Helper Command

Use the built-in Artisan command to generate test headers quickly.

Command
- php artisan billing:webhook:sign generic --payload-file=payload.json --secret=YOUR_SECRET
- php artisan billing:webhook:sign razorpay --payload-file=payload.json --secret=YOUR_SECRET
- php artisan billing:webhook:sign stripe --payload-file=payload.json --secret=YOUR_SECRET

Optional flags
- --timestamp=UNIX_TS (Stripe only)
- --event-id=evt_custom_001 (used by default sample payload)

If no payload is passed, the command emits a minimal sample payload and matching signature.

## 8. Sample Payload Generator

Generate ready-to-use payload files by provider:

- php artisan billing:webhook:sample generic --output=payload.json --pretty
- php artisan billing:webhook:sample stripe --event=customer.subscription.updated --output=stripe_payload.json --pretty
- php artisan billing:webhook:sample razorpay --event=subscription.charged --output=razorpay_payload.json --pretty

Then generate signature for that file:

- php artisan billing:webhook:sign generic --payload-file=payload.json --secret=YOUR_SECRET
- php artisan billing:webhook:sign stripe --payload-file=stripe_payload.json --secret=YOUR_SECRET
- php artisan billing:webhook:sign razorpay --payload-file=razorpay_payload.json --secret=YOUR_SECRET

## 9. One-Click Webhook Simulation

Send a signed request directly to your local or remote endpoint:

- php artisan billing:webhook:fire generic --payload-file=payload.json --secret=YOUR_SECRET --show-body
- php artisan billing:webhook:fire stripe --payload-file=stripe_payload.json --secret=YOUR_SECRET --timestamp=1710000000 --show-body
- php artisan billing:webhook:fire razorpay --payload-file=razorpay_payload.json --secret=YOUR_SECRET --show-body

Useful options:

- --url=http://127.0.0.1:8000/api/billing/webhook (override target endpoint)
- --timeout=15
- --dry-run (prints signed request details without sending)
- --force (skip interactive confirmation; required in non-interactive shells)
- --allow-host=example.com (one-time temporary host allow, can be repeated)

Safety guard:

- `billing:webhook:fire` only sends to allowlisted hosts by default.
- Default allowlist: `localhost`, `127.0.0.1`, `::1`
- Override via env: `BILLING_FIRE_ALLOWED_HOSTS=localhost,127.0.0.1,::1`
- One-time override: `--allow-host=HOST` (without changing env)
- Non-allowlisted host requires explicit `--force`
- Strict mode: `--allow-host` is intended for `--dry-run`; live send with `--allow-host` requires `--force`

Fast shortcut:

- php artisan billing:webhook:fire stripe --from-sample --event=customer.subscription.updated --secret=YOUR_SECRET --dry-run
- php artisan billing:webhook:fire razorpay --from-sample --event=subscription.charged --secret=YOUR_SECRET --dry-run
- php artisan billing:webhook:fire generic --from-sample --sample-output=storage/app/qa/fire_payload.json --secret=YOUR_SECRET --dry-run
